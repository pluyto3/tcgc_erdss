<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use App\Services\AssessmentService;
use Illuminate\Http\Request;

class MyAssessmentsController extends ModuleController
{
    public function handle(Request $request, AssessmentService $assessments)
    {
        $db = db();
        $query = $request->query();
        $eid = current_user()['employee_id'];
        if ($request->isMethod('POST')) {
            $data = $request->validate(['period_id' => ['required', 'integer'], 'q' => ['required', 'array'], 'q.*' => ['required', 'integer']]);
            $assessments->submit($data['period_id'], $eid, $data['q']);
            flash('success', 'Assessment submitted. Your answers were stored separately from your identity.');

            return redirect()->route('my_assessments');
        }

        return $this->render('my_assessments', 'My Assessments', compact('db', 'query', 'eid'));
    }
}
