<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PaperResponseImportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class PaperResponseImportTest extends TestCase
{
    private function historicalPeriod(): int
    {
        $periodId = DB::table('assessment_periods')->insertGetId([
            'title' => 'Verified historical paper assessment', 'start_date' => '2025-02-01', 'end_date' => '2025-02-28',
            'status' => 'closed', 'scale_min' => 1, 'scale_max' => 4,
            'scale_labels' => '1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree',
        ]);
        // Deliberately reverse storage order: Q001..Q056 must follow the approved bank, never database IDs.
        foreach (array_reverse(config('instrument.questions'), true) as $factor => $questions) {
            $factorId = DB::table('hrm_factors')->where('name', $factor)->value('id');
            foreach (array_reverse($questions) as $question) {
                DB::table('survey_questions')->insert([
                    'period_id' => $periodId, 'factor_id' => $factorId, 'question_text' => $question, 'display_order' => 0, 'is_required' => 1,
                ]);
            }
        }

        return $periodId;
    }

    private function row(string $code = 'PAPER-0001'): array
    {
        $row = ['respondent_code' => $code, 'submitted_date' => '2025-02-14', 'department' => 'Academic Affairs',
            'category' => 'Academic', 'employment_type' => 'Regular'];
        for ($index = 1; $index <= 56; $index++) {
            $row['Q'.str_pad((string) $index, 3, '0', STR_PAD_LEFT)] = (string) (($index - 1) % 4 + 1);
        }

        return $row;
    }

    private function csv(array $rows, ?array $headers = null): UploadedFile
    {
        $headers ??= array_keys($this->row());
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn ($header) => $row[$header] ?? '', $headers), ',', '"', '');
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return UploadedFile::fake()->createWithContent('paper-responses.csv', $contents);
    }

    private function counts(): array
    {
        $counts = [];
        foreach (['survey_responses', 'survey_response_items', 'paper_response_imports', 'survey_participation', 'employees', 'users', 'audit_logs'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    public function test_default_command_dry_run_validates_bom_csv_without_any_database_writes(): void
    {
        $periodId = $this->historicalPeriod();
        $file = $this->csv([$this->row()]);
        $before = $this->counts();
        $writes = [];
        DB::listen(function ($query) use (&$writes): void {
            if (preg_match('/^\s*(insert|update|delete|replace|create|alter|drop|truncate)\b/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });
        $this->artisan('retention:import-paper-responses', ['file' => $file->getRealPath(), '--period' => $periodId])
            ->expectsOutputToContain('Dry run passed: 1 response sets (56 answers) validated for closed assessment '.$periodId.'. No database records were written.')->assertExitCode(0);
        $this->assertSame($before, $this->counts());
        $this->assertSame([], $writes);
    }

    public function test_commit_maps_approved_question_order_and_records_only_hashed_paper_provenance(): void
    {
        $periodId = $this->historicalPeriod();
        $first = $this->row('paper-0001');
        $second = $this->row('PAPER-0002');
        $second['submitted_date'] = '2025-02-20';
        $headers = array_reverse(array_keys($first));
        $file = $this->csv([$first, $second], $headers);
        // Accept case-insensitive column names without losing the original column-to-question mapping.
        $contents = file_get_contents($file->getRealPath());
        file_put_contents($file->getRealPath(), str_replace('Q001', 'q001', $contents));
        $before = $this->counts();
        $result = app(PaperResponseImportService::class)->import($file->getRealPath(), $periodId, true);
        $this->assertTrue($result['committed']);
        $this->assertSame(2, $result['responses']);
        $this->assertSame(112, $result['answers']);
        $after = $this->counts();
        $this->assertSame($before['survey_responses'] + 2, $after['survey_responses']);
        $this->assertSame($before['survey_response_items'] + 112, $after['survey_response_items']);
        $this->assertSame(2, $after['paper_response_imports']);
        foreach (['survey_participation', 'employees', 'users'] as $table) {
            $this->assertSame($before[$table], $after[$table]);
        }
        $this->assertSame($before['audit_logs'] + 1, $after['audit_logs']);
        $provenance = DB::table('paper_response_imports')->where('code_hash', hash('sha256', 'PAPER-0001'))->first();
        $this->assertNotNull($provenance);
        $this->assertSame(hash_file('sha256', $file->getRealPath()), $provenance->source_file_sha256);
        $this->assertSame('2025-02-14', $provenance->submitted_date);
        $response = DB::table('survey_responses')->find($provenance->response_id);
        $this->assertSame('2025-02-14 00:00:00', $response->submitted_at);
        $this->assertSame(DB::table('departments')->where('name', 'Academic Affairs')->value('id'), $response->snapshot_department_id);
        $this->assertFalse(Schema::hasColumn('survey_responses', 'employee_id'));
        $this->assertFalse(Schema::hasColumn('paper_response_imports', 'respondent_code'));
        $this->assertFalse(Schema::hasColumn('paper_response_imports', 'employee_id'));
        $this->assertFalse(Schema::hasColumn('paper_response_imports', 'file_path'));
        $items = DB::table('survey_response_items as i')->join('survey_questions as q', 'q.id', '=', 'i.question_id')
            ->join('hrm_factors as f', 'f.id', '=', 'q.factor_id')->where('i.response_id', $response->id)
            ->get(['q.question_text', 'f.name as factor', 'i.response_value']);
        $index = 1;
        foreach (config('instrument.questions') as $factor => $questions) {
            foreach ($questions as $text) {
                $item = $items->first(fn ($item) => $item->question_text === $text && $item->factor === $factor);
                $this->assertNotNull($item);
                $this->assertSame(($index++ - 1) % 4 + 1, $item->response_value);
            }
        }
        $summary = DB::table('audit_logs')->where('entity', 'paper_responses')->value('summary');
        $this->assertStringContainsString($result['source_file_sha256'], $summary);
        $this->assertStringNotContainsString('PAPER-0001', $summary);
        $this->assertStringNotContainsString($file->getRealPath(), $summary);
    }

    public function test_invalid_later_row_aborts_the_entire_commit_with_row_and_column_errors(): void
    {
        $periodId = $this->historicalPeriod();
        $invalid = $this->row('PAPER-0002');
        $invalid['Q007'] = '';
        $invalid['Q008'] = '2.5';
        $invalid['submitted_date'] = '';
        $invalid['department'] = 'Unknown unit';
        $file = $this->csv([$this->row(), $invalid]);
        $before = $this->counts();
        try {
            app(PaperResponseImportService::class)->import($file->getRealPath(), $periodId, true);
            $this->fail('An incomplete paper form must stop the entire import.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('row_3_Q007', $exception->errors());
            $this->assertArrayHasKey('row_3_Q008', $exception->errors());
            $this->assertArrayHasKey('row_3_submitted_date', $exception->errors());
            $this->assertArrayHasKey('row_3_department', $exception->errors());
        }
        $this->assertSame($before, $this->counts());
    }

    public function test_commit_command_imports_paper_data_and_hr_cannot_reopen_that_period(): void
    {
        $periodId = $this->historicalPeriod();
        $file = $this->csv([$this->row()]);
        $this->artisan('retention:import-paper-responses', ['file' => $file->getRealPath(), '--period' => $periodId, '--commit' => true])
            ->expectsOutputToContain('Imported 1 paper response sets and 56 answers')->assertExitCode(0);
        $this->assertSame(1, DB::table('paper_response_imports')->where('period_id', $periodId)->count());
        $this->actingAs(User::whereHas('role', fn ($query) => $query->where('name', 'HR Administrator'))->firstOrFail());
        $data = ['action' => 'save_period', 'id' => $periodId, 'title' => 'Historical collection', 'start_date' => '2025-02-01',
            'end_date' => '2025-02-28', 'scale_min' => 1, 'scale_max' => 4,
            'scale_labels' => '1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree'];
        foreach (['active', 'draft'] as $status) {
            $this->post('/assessments', [...$data, 'status' => $status])->assertSessionHasErrors('status');
            $this->assertDatabaseHas('assessment_periods', ['id' => $periodId, 'status' => 'closed']);
        }
        $this->post('/assessments', [...$data, 'status' => 'closed'])->assertRedirect('/assessments?view='.$periodId);
    }

    public function test_duplicate_codes_in_file_or_existing_period_are_rejected_case_insensitively(): void
    {
        $periodId = $this->historicalPeriod();
        $duplicateFile = $this->csv([$this->row(), $this->row('paper-0001')]);
        try {
            app(PaperResponseImportService::class)->import($duplicateFile->getRealPath(), $periodId, true);
            $this->fail('Duplicate codes in one file must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('row_3_respondent_code', $exception->errors());
        }
        $this->assertSame(0, DB::table('paper_response_imports')->count());
        $firstFile = $this->csv([$this->row()]);
        app(PaperResponseImportService::class)->import($firstFile->getRealPath(), $periodId, true);
        $before = $this->counts();
        $repeatFile = $this->csv([$this->row('PAPER-0002'), $this->row('paper-0001')]);
        try {
            app(PaperResponseImportService::class)->import($repeatFile->getRealPath(), $periodId, true);
            $this->fail('An existing paper code must reject the complete batch.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('row_3_respondent_code', $exception->errors());
        }
        $this->assertSame($before, $this->counts());
        $nextFile = $this->csv([$this->row('PAPER-0002')]);
        $this->assertSame(1, app(PaperResponseImportService::class)->import($nextFile->getRealPath(), $periodId, true)['responses']);
        $this->assertSame(2, DB::table('paper_response_imports')->count());
    }

    public function test_target_must_be_closed_four_point_approved_instrument_without_live_or_untracked_responses(): void
    {
        $cases = [
            ['status', fn ($period) => DB::table('assessment_periods')->where('id', $period)->update(['status' => 'active']), 'CLOSED'],
            ['scale', fn ($period) => DB::table('assessment_periods')->where('id', $period)->update(['scale_max' => 5]), 'scale minimum 1 and maximum 4'],
            ['labels', fn ($period) => DB::table('assessment_periods')->where('id', $period)->update(['scale_labels' => '1=Never|2=Rarely|3=Sometimes|4=Always']), 'approved 1=Strongly Disagree'],
            ['dates', fn ($period) => DB::table('assessment_periods')->where('id', $period)->update(['end_date' => today()->addYear()->toDateString()]), 'ending no later than today'],
            ['wording', fn ($period) => DB::table('survey_questions')->where('period_id', $period)->limit(1)->update(['question_text' => 'An unapproved replacement.']), 'wording or factor mapping'],
            ['factor mapping', fn ($period) => DB::table('survey_questions')->where('period_id', $period)
                ->where('question_text', config('instrument.questions')['Employee Retention'][0])
                ->update(['factor_id' => DB::table('hrm_factors')->where('name', 'Job Satisfaction')->value('id')]), 'wording or factor mapping'],
            ['question count', fn ($period) => DB::table('survey_questions')->where('period_id', $period)->limit(1)->delete(), 'exactly the 56 approved questions'],
            ['participation', fn ($period) => DB::table('survey_participation')->insert(['period_id' => $period, 'employee_id' => 1]), 'online participation'],
            ['untracked response', fn ($period) => DB::table('survey_responses')->insert(['period_id' => $period]), 'without paper-import provenance'],
        ];
        $file = $this->csv([$this->row()]);
        foreach ($cases as [$label, $mutate, $message]) {
            $periodId = $this->historicalPeriod();
            $mutate($periodId);
            $before = $this->counts();
            try {
                app(PaperResponseImportService::class)->import($file->getRealPath(), $periodId, true);
                $this->fail('Target guard must reject '.$label.'.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
            $this->assertSame($before, $this->counts());
        }
    }

    public function test_employee_identifiers_invalid_dates_inactive_masters_and_header_duplicates_stop_import(): void
    {
        $periodId = $this->historicalPeriod();
        $invalid = $this->row('SYN-001');
        $invalid['submitted_date'] = '2025-02-30';
        DB::table('employee_categories')->where('name', 'Academic')->update(['status' => 'inactive']);
        $file = $this->csv([$invalid]);
        try {
            app(PaperResponseImportService::class)->import($file->getRealPath(), $periodId);
            $this->fail('Identity codes, impossible dates and inactive groups must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('row_2_respondent_code', $exception->errors());
            $this->assertArrayHasKey('row_2_submitted_date', $exception->errors());
            $this->assertArrayHasKey('row_2_category', $exception->errors());
        }
        DB::table('employee_categories')->where('name', 'Academic')->update(['status' => 'active']);
        DB::table('employees')->where('id', 1)->update(['employee_no' => 'PAPER-0001']);
        $employeeCode = $this->csv([$this->row()]);
        try {
            app(PaperResponseImportService::class)->import($employeeCode->getRealPath(), $periodId);
            $this->fail('A code matching an employee number must be rejected even when it has the paper-code format.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('row_2_respondent_code', $exception->errors());
        }
        $headers = array_keys($this->row());
        $headers[array_key_last($headers)] = 'q001';
        $duplicateHeaders = $this->csv([$this->row()], $headers);
        try {
            app(PaperResponseImportService::class)->import($duplicateHeaders->getRealPath(), $periodId);
            $this->fail('Case-insensitive duplicate CSV headers must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
        $this->assertSame(0, DB::table('paper_response_imports')->count());
    }

    public function test_database_failure_rolls_back_response_answers_provenance_and_audit_together(): void
    {
        $periodId = $this->historicalPeriod();
        $file = $this->csv([$this->row()]);
        $before = $this->counts();
        DB::statement("CREATE TRIGGER reject_paper_fixture BEFORE INSERT ON survey_response_items WHEN NEW.response_value=4 BEGIN SELECT RAISE(ABORT, 'Fixture insertion failure'); END");
        try {
            app(PaperResponseImportService::class)->import($file->getRealPath(), $periodId, true);
            $this->fail('The fixture must fail after a response has started inserting.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Fixture insertion failure', $exception->getMessage());
        }
        $this->assertSame($before, $this->counts());
    }

    public function test_limits_empty_template_and_missing_period_are_rejected_without_writes(): void
    {
        $periodId = $this->historicalPeriod();
        $empty = $this->csv([]);
        $before = $this->counts();
        $this->artisan('retention:import-paper-responses', ['file' => $empty->getRealPath()])
            ->expectsOutputToContain('Supply --period')->assertExitCode(1);
        $this->artisan('retention:import-paper-responses', ['file' => $empty->getRealPath(), '--period' => $periodId])
            ->expectsOutputToContain('at least one complete, verified respondent row')->assertExitCode(1);
        $oversized = UploadedFile::fake()->createWithContent('too-large.csv', str_repeat('x', 5 * 1024 * 1024 + 1));
        try {
            app(PaperResponseImportService::class)->import($oversized->getRealPath(), $periodId);
            $this->fail('Files over 5 MB must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('5 MB', $exception->getMessage());
        }
        $rows = [];
        for ($index = 1; $index <= 10001; $index++) {
            $rows[] = $this->row('PAPER-'.$index);
        }
        $tooMany = $this->csv($rows);
        unset($rows);
        try {
            app(PaperResponseImportService::class)->import($tooMany->getRealPath(), $periodId);
            $this->fail('More than 10,000 respondent rows must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('10,000', $exception->errors()['file'][0]);
        }
        $this->assertSame($before, $this->counts());
    }
}
