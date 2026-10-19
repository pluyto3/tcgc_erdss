<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FactorsController extends ModuleController
{
    public function handle(Request $request)
    {
        if ($request->isMethod('POST')) {
            $id = $request->integer('id') ?: null;
            $data = $request->validate(['id' => ['nullable', 'integer', 'exists:hrm_factors,id'],
                'name' => ['required', 'string', 'max:200', Rule::unique('hrm_factors')->ignore($id)], 'description' => ['nullable', 'string', 'max:5000'],
                'display_order' => ['required', 'integer', 'between:0,1000'], 'status' => ['required', 'in:active,inactive']]);
            unset($data['id']);
            if ($id) {
                if (DB::table('hrm_factors')->where('id', $id)->value('is_outcome')) {
                    $data['status'] = 'active';
                }
                DB::table('hrm_factors')->where('id', $id)->update([...$data, 'updated_at' => now()]);
            } else {
                $id = DB::table('hrm_factors')->insertGetId([...$data, 'is_outcome' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
            audit(db(), user_id(), 'save', 'hrm_factor', $id, $data['name']);
            flash('success', 'Measure or factor saved.');

            return redirect()->route('factors');
        }

        return $this->render('factors', 'HRM Factors', ['db' => db(), 'query' => $request->query()]);
    }
}
