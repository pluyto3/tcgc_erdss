<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterDataController extends ModuleController
{
    public const TABLES = ['departments' => ['Departments', 'name', 'code'], 'positions' => ['Positions', 'name', null],
        'employee_categories' => ['Employee Categories', 'name', null], 'employment_types' => ['Employment Types', 'name', null]];

    public function handle(Request $request)
    {
        if ($request->isMethod('POST')) {
            $table = $request->validate(['table' => ['required', Rule::in(array_keys(self::TABLES))]])['table'];
            $id = $request->integer('id') ?: null;
            $rules = ['id' => ['nullable', 'integer', Rule::exists($table, 'id')], 'name' => ['required', 'string', 'max:200', Rule::unique($table)->ignore($id)], 'status' => ['required', 'in:active,inactive']];
            if ($table === 'departments') {
                $rules['code'] = ['nullable', 'string', 'max:30', Rule::unique($table)->ignore($id)];
            }
            $data = $request->validate($rules);
            unset($data['id']);
            if ($id) {
                DB::table($table)->where('id', $id)->update([...$data, 'updated_at' => now()]);
            } else {
                $id = DB::table($table)->insertGetId([...$data, 'created_at' => now(), 'updated_at' => now()]);
            }
            audit(db(), user_id(), 'save', $table, $id, $data['name']);
            flash('success', self::TABLES[$table][0].' updated.');

            return redirect()->route('master_data', ['tab' => $table]);
        }

        return $this->render('master_data', 'Organization Setup', ['db' => db(), 'query' => $request->query(), 'tables' => self::TABLES]);
    }
}
