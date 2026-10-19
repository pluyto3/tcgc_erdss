<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;

class MonitoringController extends ModuleController
{
    public function handle(Request $request)
    {
        $db = db();
        $query = $request->query();
        $input = $request->all();

        $periods = latest_periods($db, 20);
        if ($request->isMethod('POST') && role() === 'HR Administrator') {
            $action = post('action');
            if ($action === 'save_evaluation') {
                $request->validate(['intervention_id' => ['required', 'integer', 'exists:interventions,id'], 'before_period_id' => ['required', 'integer', 'exists:assessment_periods,id'], 'after_period_id' => ['required', 'integer', 'exists:assessment_periods,id', 'different:before_period_id'], 'evaluator_note' => ['nullable', 'string', 'max:10000']]);
                $interventionId = (int) post('intervention_id');
                $beforeId = (int) post('before_period_id');
                $afterId = (int) post('after_period_id');
                $s = $db->prepare('SELECT factor_id FROM interventions WHERE id=? AND approval_status="Approved"');
                $s->execute([$interventionId]);
                $factorId = (int) $s->fetchColumn();
                if (! $factorId || ! $beforeId || ! $afterId) {
                    flash('error', 'Select an approved intervention and two assessment periods.');
                    module_redirect('index.php?page=monitoring');
                }
                $bn = respondent_count($db, $beforeId);
                $an = respondent_count($db, $afterId);
                if (! min_group_ok($db, $bn) || ! min_group_ok($db, $an)) {
                    flash('error', 'Evaluation cannot be saved because one comparison group is below the minimum respondent threshold.');
                    module_redirect('index.php?page=monitoring');
                }
                $b = factor_mean($db, $beforeId, $factorId);
                $a = factor_mean($db, $afterId, $factorId);
                $d = ($a !== null && $b !== null) ? $a - $b : null;
                $label = trend_label($a, $b);
                $db->prepare('INSERT INTO intervention_evaluations(intervention_id,factor_id,before_period_id,after_period_id,before_score,after_score,difference,result_label,evaluator_note) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$interventionId, $factorId, $beforeId, $afterId, $b, $a, $d, $label, post('evaluator_note')]);
                audit($db, user_id(), 'create', 'intervention_evaluation', (int) $db->lastInsertId(), 'Pre/post evaluation saved');
                flash('success', 'Intervention evaluation saved.');
                module_redirect('index.php?page=monitoring');
            }
        }

        return $this->render('monitoring', 'Monitoring & Evaluation', get_defined_vars());
    }
}
