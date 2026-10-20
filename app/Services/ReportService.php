<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportService
{
    public const TYPES = ['retention' => 'Retention Summary', 'factors' => 'HRM Factors', 'feedback' => 'Feedback Themes',
        'priorities' => 'Priority Areas', 'interventions' => 'Intervention Progress', 'prepost' => 'Pre/Post Comparison'];

    public function build(string $type, ?int $periodId, ?int $departmentId, ?int $beforeId, ?int $afterId): array
    {
        $db = db();
        $rows = [];
        if ($type === 'retention') {
            $counts = employee_counts($db, $departmentId);
            $headers = ['Metric', 'Value'];
            $rows = [['Total employees', $counts['total']], ['Active employees', $counts['active']], ['Separated employees', $counts['sep']],
                ['Active share of recorded workforce', fmt_num(retention_rate($db, $departmentId), 1).'%'],
                ['Separated share of recorded workforce', fmt_num(turnover_rate($db, $departmentId), 1).'%']];
            if ($periodId) {
                $participation = participation_rate($db, $periodId, $departmentId);
                $rows = [...$rows, ['Assessment respondents', respondent_count($db, $periodId, $departmentId)],
                    ['Employee retention mean', $this->score(retention_mean($db, $periodId, $departmentId))],
                    ['Overall HRM mean', $this->score(overall_mean($db, $periodId, $departmentId))],
                    ['Participation', $participation === null ? 'Not tracked for imported paper responses' : fmt_num($participation, 1).'%']];
            }
        } elseif ($type === 'factors') {
            $headers = ['HRM factor', 'Mean / 4', 'Interpretation'];
            if ($periodId) {
                foreach (factor_means($db, $periodId, $departmentId) as $factor) {
                    $value = $factor['mean_score'] === null ? null : (float) $factor['mean_score'];
                    $rows[] = [$factor['name'], $this->score($value), instrument_interpretation($value)];
                }
            }
        } elseif ($type === 'feedback') {
            $headers = ['Feedback theme', 'Comment count'];
            if ($periodId) {
                $themes = DB::table('feedback_theme_links as l')->join('feedback_themes as t', 't.id', '=', 'l.theme_id')
                    ->join('feedback as f', 'f.id', '=', 'l.feedback_id')->where('f.period_id', $periodId)
                    ->when($departmentId, fn ($q) => $q->where('f.snapshot_department_id', $departmentId))
                    ->groupBy('t.id', 't.name')->selectRaw('t.name, COUNT(DISTINCT f.id) as total')->orderByDesc('total')->get();
                foreach ($themes as $theme) {
                    $rows[] = [$theme->name, min_group_ok($db, $theme->total) ? $theme->total : 'Suppressed'];
                }
            }
        } elseif ($type === 'priorities') {
            abort_if($departmentId !== null, 403);
            $headers = ['HRM factor', 'Current', 'Previous', 'Trend', 'Priority', 'Action', 'Status'];
            if ($periodId) {
                foreach (app(PriorityService::class)->suggestions($periodId) as $priority) {
                    $rows[] = [
                        $priority['factor_name'], $this->score($priority['current_score']), $this->score($priority['previous_score']),
                        $priority['trend'], $priority['priority_level'], $priority['action_category'], $priority['status'],
                    ];
                }
            }
        } elseif ($type === 'interventions') {
            $headers = ['Code', 'Intervention', 'HRM factor', 'Responsible unit', 'Budget PHP', 'Approval', 'Status', 'Start', 'Target'];
            $interventions = DB::table('interventions as i')->leftJoin('hrm_factors as f', 'f.id', '=', 'i.factor_id')
                ->leftJoin('departments as d', 'd.id', '=', 'i.responsible_department_id')
                ->when($departmentId, fn ($q) => $q->where('i.responsible_department_id', $departmentId))
                ->orderByDesc('i.updated_at')->get(['i.*', 'f.name as factor', 'd.name as unit']);
            foreach ($interventions as $item) {
                $rows[] = [$item->code, $item->title, $item->factor, $item->unit,
                    number_format((float) $item->budget_amount, 2, '.', ''), $item->approval_status, $item->status, $item->start_date, $item->target_date];
            }
        } else {
            $headers = ['HRM factor', 'Before', 'After', 'Difference', 'Trend'];
            if ($beforeId && $afterId) {
                $factors = DB::table('hrm_factors')->where('status', 'active')->orderBy('display_order')->get();
                foreach ($factors as $factor) {
                    $before = factor_mean($db, $beforeId, $factor->id, $departmentId);
                    $after = factor_mean($db, $afterId, $factor->id, $departmentId);
                    $difference = $before !== null && $after !== null ? $after - $before : null;
                    $rows[] = [$factor->name, $this->score($before), $this->score($after),
                        $difference === null ? 'Suppressed / unavailable' : sprintf('%+.2f', $difference), trend_label($after, $before)];
                }
            }
        }

        return compact('headers', 'rows');
    }

    private function score(?float $score): string
    {
        return $score === null ? 'Suppressed / unavailable' : number_format($score, 3);
    }
}
