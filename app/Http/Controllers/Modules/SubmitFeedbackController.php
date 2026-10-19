<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubmitFeedbackController extends ModuleController
{
    public function handle(Request $request, SettingsService $settings)
    {
        if ($request->isMethod('POST')) {
            $data = $request->validate([
                'body' => ['required', 'string', 'max:10000'],
                'category_id' => ['required', 'integer', Rule::exists('feedback_categories', 'id')->where('status', 'active')],
                'period_id' => ['nullable', 'integer', Rule::exists('assessment_periods', 'id')->whereIn('status', ['active', 'closed'])],
                'is_anonymous' => ['required', 'boolean'],
            ]);
            $anonymous = $settings->get('allow_named_feedback', '1') !== '1' || (bool) $data['is_anonymous'];
            DB::table('feedback')->insert([
                'period_id' => $data['period_id'] ?? null, 'category_id' => $data['category_id'], 'body' => $data['body'],
                'is_anonymous' => $anonymous, 'submitter_employee_id' => $anonymous ? null : $request->user()->employee_id,
                'snapshot_department_id' => $request->user()->employee->department_id, 'created_at' => now(),
            ]);
            flash('success', $anonymous ? 'Anonymous feedback submitted. No employee identity was stored with the comment.' : 'Your feedback was submitted.');

            return redirect()->route('submit_feedback');
        }

        return $this->render('submit_feedback', 'Submit Feedback', ['db' => db(), 'query' => $request->query()]);
    }
}
