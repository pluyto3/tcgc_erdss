<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettingsController extends ModuleController
{
    public function handle(Request $request, SettingsService $settings)
    {
        if ($request->isMethod('POST')) {
            if ($request->input('action') === 'decision_rules') {
                $data = $request->validate(['threshold_low' => ['required', 'numeric', 'min:1', 'lt:threshold_high'],
                    'threshold_high' => ['required', 'numeric', 'max:4'], 'stability_band' => ['required', 'numeric', 'between:0,1']]);
                DB::transaction(function () use ($data): void {
                    DB::table('decision_rules')->update(['active' => 0]);
                    DB::table('decision_rules')->insert(['name' => 'Management action thresholds', ...$data,
                        'active' => 1, 'is_prototype' => 0, 'created_at' => now()]);
                });
            } else {
                $rules = ['institution_name' => ['required', 'string', 'max:200'], 'system_name' => ['required', 'string', 'max:200'],
                    'dashboard_title' => ['required', 'string', 'max:200'], 'minimum_group_size' => ['required', 'integer', 'between:2,100'],
                    'session_timeout' => ['required', 'integer', 'between:5,1440'], 'allow_named_feedback' => ['required', 'boolean'],
                    'demo_mode' => ['required', 'boolean'], 'default_scale' => ['nullable', 'string', 'max:80'],
                    'assessment_frequency' => ['required', 'string', 'max:50'], 'primary_color' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
                    'accent_color' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'], 'report_footer' => ['nullable', 'string', 'max:300'],
                    'logo' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg,image/webp', 'max:2048']];
                $data = $request->validate($rules);
                if ($request->hasFile('logo') && ! @getimagesize($request->file('logo')->getRealPath())) {
                    throw ValidationException::withMessages(['logo' => 'Please upload a valid image.']);
                }
                unset($data['logo']);
                DB::transaction(function () use ($data, $settings): void {
                    foreach ($data as $key => $value) {
                        $settings->set($key, (string) $value, user_id());
                    }
                });
                if ($request->hasFile('logo')) {
                    $path = $request->file('logo')->store('', 'public');
                    $settings->set('logo_path', 'uploads/'.$path, user_id());
                }
            }
            audit(db(), user_id(), 'update', 'system_settings', null, 'System configuration updated');
            flash('success', 'Settings saved.');

            return redirect()->route('settings');
        }

        return $this->render('settings', 'Settings', ['db' => db(), 'query' => $request->query()]);
    }
}
