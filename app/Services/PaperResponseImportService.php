<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaperResponseImportService
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const MAX_ROWS = 10000;

    private const MAX_ERRORS = 100;

    public function import(string $file, int $periodId, bool $commit = false): array
    {
        $resolved = realpath($file);
        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            throw new RuntimeException('The CSV file must exist and be readable on this computer.');
        }
        $size = filesize($resolved);
        if ($size === false || $size > self::MAX_BYTES) {
            throw new RuntimeException('The CSV must be no larger than 5 MB.');
        }
        $contents = file_get_contents($resolved, false, null, 0, self::MAX_BYTES + 1);
        if ($contents === false || strlen($contents) > self::MAX_BYTES) {
            throw new RuntimeException('The CSV could not be read within the 5 MB limit.');
        }
        if (! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            throw new RuntimeException('Use a UTF-8 CSV file without null bytes.');
        }
        $sourceHash = hash('sha256', $contents);
        $operation = function () use ($contents, $periodId, $sourceHash, $commit): array {
            $target = $this->target($periodId, $commit);
            $rows = $this->validatedRows($contents, $target, $periodId);
            if ($commit) {
                foreach ($rows as $row) {
                    $responseId = DB::table('survey_responses')->insertGetId([
                        'period_id' => $periodId, 'submitted_at' => $row['submitted_date'].' 00:00:00',
                        'snapshot_department_id' => $row['department_id'], 'snapshot_category_id' => $row['category_id'],
                        'snapshot_employment_type_id' => $row['employment_type_id'],
                    ]);
                    $items = [];
                    foreach (array_values($target['questions']) as $index => $questionId) {
                        $items[] = ['response_id' => $responseId, 'question_id' => $questionId, 'response_value' => $row['answers'][$index]];
                    }
                    DB::table('survey_response_items')->insert($items);
                    DB::table('paper_response_imports')->insert([
                        'period_id' => $periodId, 'response_id' => $responseId, 'code_hash' => $row['code_hash'],
                        'source_file_sha256' => $sourceHash, 'submitted_date' => $row['submitted_date'], 'imported_at' => now(),
                    ]);
                }
                DB::table('audit_logs')->insert([
                    'user_id' => null, 'action' => 'import', 'entity' => 'paper_responses', 'entity_id' => $periodId,
                    'summary' => count($rows).' historical paper response sets imported; source SHA-256 '.$sourceHash,
                    'ip_address' => null, 'created_at' => now(),
                ]);
            }

            return ['period_id' => $periodId, 'responses' => count($rows), 'answers' => count($rows) * 56,
                'source_file_sha256' => $sourceHash, 'committed' => $commit];
        };

        // Dry runs perform SELECTs only, including no audit or provenance writes.
        return $commit ? DB::transaction($operation) : $operation();
    }

    private function target(int $periodId, bool $lock): array
    {
        if (! Schema::hasTable('paper_response_imports')) {
            throw new RuntimeException('Run the pending migrations on the intended database before using the paper importer.');
        }
        $query = DB::table('assessment_periods')->where('id', $periodId);
        $period = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $period || $period->status !== 'closed' || (int) $period->scale_min !== 1 || (int) $period->scale_max !== 4) {
            throw new RuntimeException('Choose an existing CLOSED historical assessment with scale minimum 1 and maximum 4.');
        }
        if (! $this->isDate($period->start_date) || ! $this->isDate($period->end_date)
            || $period->start_date > $period->end_date || $period->end_date > today()->toDateString()) {
            throw new RuntimeException('The historical assessment needs valid collection start/end dates, ending no later than today.');
        }
        $expectedLabels = [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Agree', 4 => 'Strongly Agree'];
        $labels = parse_scale_labels($period->scale_labels, 1, 4);
        foreach ($expectedLabels as $value => $label) {
            if (mb_strtolower(trim($labels[$value])) !== mb_strtolower($label)) {
                throw new RuntimeException('The historical assessment must use the approved 1=Strongly Disagree, 2=Disagree, 3=Agree, 4=Strongly Agree coding.');
            }
        }
        if (DB::table('survey_participation')->where('period_id', $periodId)->exists()) {
            throw new RuntimeException('Paper import refused: this assessment contains employee-linked online participation. Use a separate historical paper assessment.');
        }
        $untracked = DB::table('survey_responses as r')->leftJoin('paper_response_imports as p', function ($join): void {
            $join->on('p.response_id', '=', 'r.id')->on('p.period_id', '=', 'r.period_id');
        })->where('r.period_id', $periodId)->whereNull('p.id')->exists();
        if ($untracked) {
            throw new RuntimeException('Paper import refused: this assessment already contains responses without paper-import provenance. Use a separate historical paper assessment.');
        }
        $bank = config('instrument.questions');
        if (! is_array($bank) || count($bank) !== 9 || count(array_filter($bank, 'is_array')) !== 9
            || array_sum(array_map('count', $bank)) !== 56) {
            throw new RuntimeException('The approved instrument configuration must contain nine measures and 56 questions.');
        }
        $questions = DB::table('survey_questions as q')->join('hrm_factors as f', 'f.id', '=', 'q.factor_id')
            ->where('q.period_id', $periodId)->get(['q.id', 'q.question_text', 'q.is_required', 'f.name as factor', 'f.is_outcome', 'f.status']);
        if ($questions->count() !== 56) {
            throw new RuntimeException('The historical assessment must contain exactly the 56 approved questions. Install and review the approved questionnaire first.');
        }
        $mapping = [];
        $order = 1;
        foreach ($bank as $factor => $texts) {
            if (count($texts) !== ($factor === 'Employee Retention' ? 8 : 6)) {
                throw new RuntimeException('The approved questionnaire requires eight retention items and six items per HRM factor.');
            }
            foreach ($texts as $text) {
                $matches = $questions->filter(fn ($question) => $question->factor === $factor && $question->question_text === $text
                    && $question->status === 'active' && (int) $question->is_outcome === (int) ($factor === 'Employee Retention')
                    && (int) $question->is_required === 1);
                if ($matches->count() !== 1) {
                    throw new RuntimeException('The historical questionnaire wording or factor mapping differs from the approved 56-item instrument. No answers can be imported.');
                }
                $mapping['q'.str_pad((string) $order++, 3, '0', STR_PAD_LEFT)] = $matches->first()->id;
            }
        }

        return ['start_date' => $period->start_date, 'end_date' => $period->end_date, 'questions' => $mapping];
    }

    private function validatedRows(string $contents, array $target, int $periodId): array
    {
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $contents);
        rewind($stream);
        try {
            $rawHeaders = fgetcsv($stream, 0, ',', '"', '');
            if (! $rawHeaders) {
                throw ValidationException::withMessages(['file' => 'The CSV is empty.']);
            }
            $headers = array_map(fn ($header) => mb_strtolower(trim(ltrim((string) $header, "\xEF\xBB\xBF"))), $rawHeaders);
            $expected = ['respondent_code', 'submitted_date', 'department', 'category', 'employment_type', ...array_keys($target['questions'])];
            if (count($headers) !== count($expected) || count($headers) !== count(array_unique($headers))
                || array_diff($expected, $headers) || array_diff($headers, $expected)) {
                throw ValidationException::withMessages(['file' => 'Use exactly these unique CSV headers: respondent_code, submitted_date, department, category, employment_type, Q001 through Q056. Header case and column order may vary.']);
            }
            $masters = [];
            foreach (['department' => 'departments', 'category' => 'employee_categories', 'employment_type' => 'employment_types'] as $key => $table) {
                $records = DB::table($table)->where('status', 'active')->get(['id', 'name']);
                if ($records->groupBy(fn ($master) => mb_strtolower(trim($master->name)))->contains(fn ($group) => $group->count() > 1)) {
                    throw new RuntimeException('Organization setup has ambiguous active '.$key.' names. Resolve duplicate names before importing paper responses.');
                }
                $masters[$key] = $records->mapWithKeys(fn ($master) => [mb_strtolower(trim($master->name)) => $master->id])->all();
            }
            $knownCodes = DB::table('paper_response_imports')->where('period_id', $periodId)->pluck('code_hash')->flip()->all();
            $employeeNumbers = DB::table('employees')->pluck('employee_no')->mapWithKeys(fn ($number) => [mb_strtoupper(trim($number)) => true])->all();
            $seen = $rows = $errors = [];
            $errorCount = $record = $dataRows = 0;
            $addError = function (string $column, string $message) use (&$errors, &$errorCount, &$record): void {
                $errorCount++;
                if ($errorCount <= self::MAX_ERRORS) {
                    $errors['row_'.($record + 1).'_'.$column] = 'Row '.($record + 1).', '.$column.': '.$message;
                }
            };
            while (($raw = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $record++;
                if (count(array_filter($raw, fn ($cell) => trim((string) $cell) !== '')) === 0) {
                    continue;
                }
                if (++$dataRows > self::MAX_ROWS) {
                    throw ValidationException::withMessages(['file' => 'Import at most 10,000 respondent rows per CSV file.']);
                }
                if (count($raw) !== count($headers)) {
                    $addError('columns', 'the number of cells must match the 61-column header.');

                    continue;
                }
                $row = array_combine($headers, array_map(fn ($cell) => trim((string) $cell), $raw));
                $before = $errorCount;
                $code = mb_strtoupper($row['respondent_code']);
                $codeHash = hash('sha256', $code);
                if (! preg_match('/^PAPER-[0-9]{1,12}$/D', $code) || isset($employeeNumbers[$code])) {
                    $addError('respondent_code', 'use an assigned anonymous paper code such as PAPER-0001, never a name, email address or employee number.');
                } elseif (isset($seen[$codeHash]) || isset($knownCodes[$codeHash])) {
                    $addError('respondent_code', 'this paper code is duplicated in this CSV or has already been imported into this assessment.');
                }
                $seen[$codeHash] = true;
                if (! $this->isDate($row['submitted_date']) || $row['submitted_date'] < $target['start_date'] || $row['submitted_date'] > $target['end_date']) {
                    $addError('submitted_date', 'enter the confirmed collection date as YYYY-MM-DD within the historical assessment dates; do not guess a missing date.');
                }
                $ids = [];
                foreach ($masters as $column => $lookup) {
                    $ids[$column.'_id'] = $lookup[mb_strtolower($row[$column])] ?? null;
                    if ($ids[$column.'_id'] === null) {
                        $addError($column, 'must match an active organization master-data name.');
                    }
                }
                $answers = [];
                foreach ($target['questions'] as $column => $questionId) {
                    if (! preg_match('/^[1-4]$/D', $row[$column])) {
                        $addError(strtoupper($column), 'enter the single recorded rating 1, 2, 3 or 4; blanks, decimals, multiple marks and imputed answers are not accepted.');
                    } else {
                        $answers[] = (int) $row[$column];
                    }
                }
                if ($errorCount === $before) {
                    $rows[] = [...$ids, 'code_hash' => $codeHash, 'submitted_date' => $row['submitted_date'], 'answers' => $answers];
                }
            }
            if ($errorCount > 0) {
                if ($errorCount > self::MAX_ERRORS) {
                    $errors['more_errors'] = ($errorCount - self::MAX_ERRORS).' further validation errors were omitted. Correct the file and run another dry run.';
                }
                throw ValidationException::withMessages($errors);
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'The CSV must contain at least one complete, verified respondent row.']);
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
