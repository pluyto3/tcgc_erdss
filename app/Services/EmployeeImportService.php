<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeImportService
{
    public function import(UploadedFile $file): array
    {
        $stream = fopen($file->getRealPath(), 'r');
        try {
            $rawHeaders = fgetcsv($stream, 0, ',', '"', '');
            if (! $rawHeaders) {
                throw ValidationException::withMessages(['employee_csv' => 'The CSV is empty.']);
            }
            $headers = array_map(fn ($value) => strtolower(trim(ltrim((string) $value, "\xEF\xBB\xBF"))), $rawHeaders);
            $required = ['employee_no', 'first_name', 'last_name', 'department', 'position', 'category', 'employment_type'];
            if (array_diff($required, $headers) || count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['employee_csv' => 'Use the CSV template with all required columns and no duplicate headers.']);
            }
            $masters = [];
            foreach (['department' => 'departments', 'position' => 'positions', 'category' => 'employee_categories', 'employment_type' => 'employment_types'] as $key => $table) {
                $masters[$key] = DB::table($table)->where('status', 'active')->get(['id', 'name'])->mapWithKeys(fn ($row) => [mb_strtolower($row->name) => $row->id])->all();
            }
            $known = DB::table('employees')->pluck('employee_no')->flip()->all();
            $imported = $skipped = $line = 0;
            DB::transaction(function () use ($stream, $headers, $masters, &$known, &$imported, &$skipped, &$line): void {
                while (($raw = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                    if (++$line > 10000) {
                        throw ValidationException::withMessages(['employee_csv' => 'Import at most 10,000 rows per file.']);
                    }
                    if (count(array_filter($raw, fn ($value) => trim((string) $value) !== '')) === 0) {
                        continue;
                    }
                    if (count($raw) !== count($headers)) {
                        $skipped++;

                        continue;
                    }
                    $row = array_combine($headers, array_map(fn ($value) => trim((string) $value), $raw));
                    $row['employment_status'] = strtolower($row['employment_status'] ?? 'active') ?: 'active';
                    $validator = Validator::make($row, ['employee_no' => ['required', 'string', 'max:100'], 'first_name' => ['required', 'string', 'max:150'],
                        'last_name' => ['required', 'string', 'max:150'], 'middle_name' => ['nullable', 'string', 'max:150'],
                        'email' => ['nullable', 'email', 'max:254'], 'date_hired' => ['nullable', 'date_format:Y-m-d'], 'employment_status' => ['in:active,separated']]);
                    if ($validator->fails() || isset($known[$row['employee_no']])) {
                        $skipped++;

                        continue;
                    }
                    $ids = [];
                    foreach ($masters as $key => $lookup) {
                        $ids[$key.'_id'] = $lookup[mb_strtolower($row[$key] ?? '')] ?? null;
                    }
                    if (in_array(null, $ids, true)) {
                        $skipped++;

                        continue;
                    }
                    // The column is employment_type_id; the master lookup uses its readable CSV header.
                    DB::table('employees')->insert([...$ids, 'employee_no' => $row['employee_no'], 'first_name' => $row['first_name'],
                        'middle_name' => ($row['middle_name'] ?? '') ?: null, 'last_name' => $row['last_name'], 'email' => ($row['email'] ?? '') ?: null,
                        'date_hired' => ($row['date_hired'] ?? '') ?: null, 'employment_status' => $row['employment_status'], 'created_at' => now(), 'updated_at' => now()]);
                    $known[$row['employee_no']] = true;
                    $imported++;
                }
            });

            return compact('imported', 'skipped');
        } finally {
            fclose($stream);
        }
    }
}
