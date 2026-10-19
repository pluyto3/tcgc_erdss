<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use App\Services\EmployeeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeesController extends ModuleController
{
    public function handle(Request $request, EmployeeImportService $imports)
    {
        if ($request->query('template') === 'csv') {
            report_csv('tcgc_employee_import_template.csv',
                ['employee_no', 'first_name', 'middle_name', 'last_name', 'email', 'department', 'position', 'category', 'employment_type', 'date_hired', 'employment_status'], []);
        }
        if ($request->isMethod('POST')) {
            $action = $request->validate(['action' => ['required', 'in:save,archive,separate,import_csv']])['action'];
            if ($action === 'import_csv') {
                $request->validate(['employee_csv' => ['required', 'file', 'max:5120', 'extensions:csv']]);
                $result = $imports->import($request->file('employee_csv'));
                $summary = "Employee import complete: {$result['imported']} imported, {$result['skipped']} skipped.";
                audit(db(), user_id(), 'import', 'employees', null, $summary);
            } elseif ($action === 'save') {
                $id = $request->integer('id') ?: null;
                $data = $request->validate(['id' => ['nullable', 'integer', 'exists:employees,id'],
                    'employee_no' => ['required', 'string', 'max:100', Rule::unique('employees')->ignore($id)],
                    'first_name' => ['required', 'string', 'max:150'], 'middle_name' => ['nullable', 'string', 'max:150'], 'last_name' => ['required', 'string', 'max:150'],
                    'email' => ['nullable', 'email', 'max:254'], 'department_id' => ['required', 'integer', 'exists:departments,id'],
                    'position_id' => ['required', 'integer', 'exists:positions,id'], 'category_id' => ['required', 'integer', 'exists:employee_categories,id'],
                    'employment_type_id' => ['required', 'integer', 'exists:employment_types,id'], 'date_hired' => ['nullable', 'date_format:Y-m-d'],
                    'employment_status' => ['required', 'in:active,separated']]);
                unset($data['id']);
                if ($id) {
                    DB::table('employees')->where('id', $id)->update([...$data, 'updated_at' => now()]);
                } else {
                    $id = DB::table('employees')->insertGetId([...$data, 'created_at' => now(), 'updated_at' => now()]);
                }
                audit(db(), user_id(), 'save', 'employee', $id, 'Employee record saved');
                $summary = 'Employee record saved.';
            } else {
                $data = $request->validate(['id' => ['required', 'integer', 'exists:employees,id'],
                    'separation_date' => [Rule::requiredIf($action === 'separate'), 'nullable', 'date_format:Y-m-d'],
                    'separation_type' => [Rule::requiredIf($action === 'separate'), 'nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:5000']]);
                DB::transaction(function () use ($data, $action): void {
                    if ($action === 'archive') {
                        DB::table('employees')->where('id', $data['id'])->update(['archived_at' => now()]);
                    } else {
                        $employee = DB::table('employees')->where('id', $data['id'])->lockForUpdate()->first();
                        abort_if($employee->employment_status === 'separated', 422, 'This employee is already separated.');
                        DB::table('employee_separations')->insert(['employee_id' => $data['id'], 'separation_date' => $data['separation_date'],
                            'separation_type' => $data['separation_type'], 'notes' => $data['notes'] ?? null, 'created_at' => now()]);
                        DB::table('employees')->where('id', $data['id'])->update(['employment_status' => 'separated', 'updated_at' => now()]);
                    }
                    audit(db(), user_id(), $action, 'employee', $data['id'], 'Employee '.$action);
                });
                $summary = $action === 'archive' ? 'Employee archived.' : 'Separation recorded.';
            }
            flash('success', $summary);

            return redirect()->route('employees');
        }

        return $this->render('employees', 'Employees', ['db' => db(), 'query' => $request->query()]);
    }
}
