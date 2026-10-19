<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsersController extends ModuleController
{
    public function handle(Request $request)
    {
        if ($request->isMethod('POST')) {
            $id = $request->integer('id') ?: null;
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
            $data = $request->validate(['id' => ['nullable', 'integer', 'exists:users,id'], 'name' => ['required', 'string', 'max:200'],
                'email' => ['required', 'email', 'max:254', Rule::unique('users')->ignore($id)], 'role_id' => ['required', 'integer', 'exists:roles,id'],
                'employee_id' => ['nullable', 'integer', 'exists:employees,id', Rule::unique('users')->ignore($id)], 'status' => ['required', 'in:active,inactive'],
                'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'max:1024']]);
            $roleName = DB::table('roles')->where('id', $data['role_id'])->value('name');
            if (in_array($roleName, ['Employee', 'Department Head'], true) && empty($data['employee_id'])) {
                throw ValidationException::withMessages(['employee_id' => 'Employees and department heads need a linked employee record.']);
            }
            DB::transaction(function () use (&$id, $data, $roleName): void {
                $old = $id ? DB::table('users')->where('id', $id)->lockForUpdate()->first() : null;
                if ($old && DB::table('roles')->where('id', $old->role_id)->value('name') === 'HR Administrator'
                    && ($roleName !== 'HR Administrator' || $data['status'] !== 'active')
                    && DB::table('users')->where('role_id', $old->role_id)->where('status', 'active')->count() <= 1) {
                    throw ValidationException::withMessages(['role_id' => 'Keep at least one active HR administrator.']);
                }
                $record = collect($data)->except(['id', 'password'])->all();
                if (! empty($data['password'])) {
                    $record['password_hash'] = Hash::make($data['password']);
                }
                if ($id) {
                    DB::table('users')->where('id', $id)->update([...$record, 'updated_at' => now()]);
                } else {
                    $id = DB::table('users')->insertGetId([...$record, 'created_at' => now(), 'updated_at' => now()]);
                }
                audit(db(), user_id(), 'save', 'user', $id, $data['email']);
            });
            flash('success', 'User account saved.');

            return redirect()->route('users');
        }

        return $this->render('users', 'User Management', ['db' => db(), 'query' => $request->query()]);
    }
}
