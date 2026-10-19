<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentsController extends ModuleController
{
    public function handle(Request $request)
    {
        if ($request->isMethod('POST')) {
            $action = $request->validate(['action' => ['required', 'in:save_period,add_question,delete_question']])['action'];
            if ($action === 'save_period') {
                $data = $request->validate(['id' => ['nullable', 'integer', 'exists:assessment_periods,id'], 'title' => ['required', 'string', 'max:200'],
                    'description' => ['nullable', 'string', 'max:10000'], 'start_date' => ['required', 'date_format:Y-m-d'],
                    'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'], 'status' => ['required', 'in:draft,active,closed'],
                    'scale_min' => ['required', 'integer', 'min:1', 'max:9'], 'scale_max' => ['required', 'integer', 'gt:scale_min', 'max:10'],
                    'scale_labels' => ['required', 'string', 'max:2000']]);
                $id = $data['id'] ?? null;
                unset($data['id']);
                if ($id && DB::table('survey_responses')->where('period_id', $id)->exists()) {
                    $period = DB::table('assessment_periods')->find($id);
                    if ($period->scale_min != $data['scale_min'] || $period->scale_max != $data['scale_max']) {
                        throw ValidationException::withMessages(['scale_min' => 'The rating scale cannot change after responses have been collected.']);
                    }
                }
                if ($id) {
                    DB::table('assessment_periods')->where('id', $id)->update([...$data, 'updated_at' => now()]);
                } else {
                    $id = DB::table('assessment_periods')->insertGetId([...$data, 'created_at' => now(), 'updated_at' => now()]);
                }
                $periodId = $id;
                audit(db(), user_id(), 'save', 'assessment', $id, $data['title']);
            } else {
                $request->validate(['period_id' => ['required', 'integer', 'exists:assessment_periods,id']]);
                $periodId = $request->integer('period_id');
                if ($action === 'add_question') {
                    $data = $request->validate(['factor_id' => ['required', 'integer', 'exists:hrm_factors,id'],
                        'question_text' => ['required', 'string', 'max:3000'], 'display_order' => ['required', 'integer', 'min:0']]);
                    $id = DB::table('survey_questions')->insertGetId([...$data, 'period_id' => $periodId, 'is_required' => 1, 'created_at' => now()]);
                    audit(db(), user_id(), 'create', 'survey_question', $id, 'Question added');
                } else {
                    $request->validate(['id' => ['required', 'integer', 'exists:survey_questions,id']]);
                    $id = $request->integer('id');
                    if (DB::table('survey_response_items')->where('question_id', $id)->exists()) {
                        throw ValidationException::withMessages(['id' => 'Questions with collected answers must be retained.']);
                    }
                    DB::table('survey_questions')->where('id', $id)->where('period_id', $periodId)->delete();
                    audit(db(), user_id(), 'delete', 'survey_question', $id, 'Unused question removed');
                }
            }
            flash('success', 'Assessment updated.');

            return redirect()->route('assessments', ['view' => $periodId]);
        }

        return $this->render('assessments', 'Assessments', ['db' => db(), 'query' => $request->query()]);
    }
}
