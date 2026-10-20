<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends ModuleController
{
    public function handle(Request $request)
    {
        if ($request->input('period_id') !== 'general') {
            $request->validate(['period_id' => ['nullable', 'integer', 'exists:assessment_periods,id']]);
        }
        if ($request->isMethod('POST')) {
            $action = $request->validate(['action' => ['required', 'in:tag,new_theme']])['action'];
            if ($action === 'tag') {
                $data = $request->validate(['feedback_id' => ['required', 'integer', 'exists:feedback,id'], 'theme_id' => ['required', 'integer', 'exists:feedback_themes,id']]);
                DB::transaction(function () use ($data): void {
                    DB::table('feedback_theme_links')->insertOrIgnore($data);
                    DB::table('feedback')->where('id', $data['feedback_id'])->update(['review_status' => 'reviewed']);
                    audit(db(), user_id(), 'tag', 'feedback', $data['feedback_id'], 'Feedback theme assigned');
                });
            } else {
                $data = $request->validate(['name' => ['required', 'string', 'max:200', 'unique:feedback_themes']]);
                DB::table('feedback_themes')->insert([...$data, 'status' => 'active']);
            }
            flash('success', 'Feedback themes updated.');

            return redirect()->route('feedback', $request->filled('period_id') ? ['period_id' => $request->input('period_id')] : []);
        }
        $tags = DB::table('feedback_theme_links as l')->join('feedback_themes as t', 't.id', '=', 'l.theme_id')->get(['l.feedback_id', 't.name'])->groupBy('feedback_id');

        return $this->render('feedback', 'Employee Feedback', ['db' => db(), 'query' => $request->query(), 'tagsByFeedback' => $tags]);
    }
}
