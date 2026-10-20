<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    public function submit(int $periodId, int $employeeId, array $answers): void
    {
        DB::transaction(function () use ($periodId, $employeeId, $answers): void {
            $period = DB::table('assessment_periods')->where('id', $periodId)->where('status', 'active')->lockForUpdate()->first();
            if (! $period || ($period->start_date && $period->start_date > today()->toDateString()) || ($period->end_date && $period->end_date < today()->toDateString())) {
                throw ValidationException::withMessages(['period_id' => 'This assessment is not currently open.']);
            }
            if (period_has_paper_responses(db(), $periodId)) {
                throw ValidationException::withMessages(['period_id' => 'This assessment contains imported paper responses and cannot accept online submissions. Use a separate assessment period.']);
            }
            $employee = Employee::query()->whereNull('archived_at')->where('employment_status', 'active')->findOrFail($employeeId);
            if (DB::table('survey_participation')->where('period_id', $periodId)->where('employee_id', $employeeId)->exists()) {
                throw ValidationException::withMessages(['period_id' => 'You have already completed this assessment.']);
            }
            $questions = DB::table('survey_questions')->where('period_id', $periodId)->get()->keyBy('id');
            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['q' => 'No questions are available for this assessment.']);
            }
            foreach ($questions as $question) {
                if ($question->is_required && ! array_key_exists($question->id, $answers)) {
                    throw ValidationException::withMessages(['q' => 'Please answer every required question.']);
                }
            }
            foreach ($answers as $questionId => $value) {
                if (! $questions->has($questionId) || filter_var($value, FILTER_VALIDATE_INT) === false || $value < $period->scale_min || $value > $period->scale_max) {
                    throw ValidationException::withMessages(['q' => 'Every answer must match a question in this assessment and its rating scale.']);
                }
            }
            // Participation and answer sets intentionally have no shared employee identifier.
            $responseId = DB::table('survey_responses')->insertGetId([
                'period_id' => $periodId, 'submitted_at' => now(), 'snapshot_department_id' => $employee->department_id,
                'snapshot_category_id' => $employee->category_id, 'snapshot_employment_type_id' => $employee->employment_type_id,
            ]);
            DB::table('survey_response_items')->insert(collect($answers)->map(fn ($value, $questionId) => [
                'response_id' => $responseId, 'question_id' => (int) $questionId, 'response_value' => (int) $value,
            ])->values()->all());
            DB::table('survey_participation')->insert(['period_id' => $periodId, 'employee_id' => $employeeId, 'completed_at' => now()]);
        });
    }
}
