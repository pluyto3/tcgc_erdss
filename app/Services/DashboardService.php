<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private SettingsService $settings) {}

    public function data(Request $request): array
    {
        $validated = $request->validate([
            'dataset' => ['nullable', 'in:baseline,live'], 'period_id' => ['nullable', 'integer', 'exists:assessment_periods,id'],
            'department_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $unitScope = role() === 'Department Head';
        $dataset = $unitScope ? 'live' : ($validated['dataset'] ?? 'baseline');
        $departmentId = $unitScope ? (int) current_user()['department_id'] : ((int) ($validated['department_id'] ?? 0) ?: null);
        if ($departmentId) {
            abort_unless(DB::table('departments')->where('id', $departmentId)->exists(), 422, 'Choose an existing operational unit.');
        }
        $periods = DB::table('assessment_periods')->whereIn('status', ['active', 'closed'])->orderByDesc('start_date')->orderByDesc('id')->get();
        $period = isset($validated['period_id']) ? $periods->firstWhere('id', (int) $validated['period_id']) : ($periods->firstWhere('status', 'active') ?? $periods->first());
        $minimum = max(2, (int) $this->settings->get('minimum_group_size', 5));
        $demo = $this->settings->get('demo_mode', '1') === '1';
        $baseline = $dataset === 'baseline';
        $factors = [];
        $respondents = 0;
        $retention = null;
        $participation = null;
        $trend = [];
        $retentionRate = $turnoverRate = null;
        $cohorts = [];

        if ($baseline) {
            foreach (config('research.factors') as $name => $values) {
                $factors[] = ['name' => $name, 'mean_score' => $values['mean'], 'respondents' => null, 'previous' => null];
            }
            $retention = config('research.retention');
            $headcount = config('research.headcount');
            $units = collect(config('research.units'))->map(fn ($count, $name) => ['name' => $name, 'count' => $count])->values()->all();
        } else {
            $employees = DB::table('employees')->whereNull('archived_at')->when($departmentId, fn ($q) => $q->where('department_id', $departmentId));
            $headcount = (clone $employees)->count();
            $active = (clone $employees)->where('employment_status', 'active')->count();
            $separated = (clone $employees)->where('employment_status', 'separated')->count();
            $retentionRate = $headcount ? $active / $headcount * 100 : null;
            $turnoverRate = $headcount ? $separated / $headcount * 100 : null;
            $units = DB::table('departments as d')->leftJoin('employees as e', function ($join): void {
                $join->on('e.department_id', '=', 'd.id')->whereNull('e.archived_at');
            })->when($departmentId, fn ($q) => $q->where('d.id', $departmentId))
                ->where('d.status', 'active')->groupBy('d.id', 'd.name')->selectRaw('d.name, COUNT(e.id) as count')->orderByDesc('count')->get()->map(fn ($r) => (array) $r)->all();
            if ($period) {
                $responses = DB::table('survey_responses')->where('period_id', $period->id)->when($departmentId, fn ($q) => $q->where('snapshot_department_id', $departmentId));
                $respondents = $responses->count();
                $participation = participation_rate(db(), $period->id, $departmentId);
                $scores = $this->scores($period->id, $departmentId);
                $previousId = previous_period_id(db(), $period->id);
                $previous = $previousId ? $this->scores($previousId, $departmentId)->keyBy('id') : collect();
                foreach ($scores as $score) {
                    $mean = $score->respondents >= $minimum ? (float) $score->mean_score : null;
                    if ($score->is_outcome) {
                        $retention = $mean;

                        continue;
                    }
                    $old = $previous->get($score->id);
                    $factors[] = ['name' => $score->name, 'mean_score' => $mean, 'respondents' => $score->respondents,
                        'previous' => $old && $old->respondents >= $minimum ? (float) $old->mean_score : null];
                }
                $cohorts = $this->cohorts($period->id, $previousId, $departmentId, $minimum);
                $trend = DB::table('survey_responses as sr')->join('survey_response_items as sri', 'sri.response_id', '=', 'sr.id')
                    ->join('survey_questions as q', 'q.id', '=', 'sri.question_id')->join('hrm_factors as f', 'f.id', '=', 'q.factor_id')
                    ->join('assessment_periods as p', 'p.id', '=', 'sr.period_id')->where('f.is_outcome', 1)
                    ->when($departmentId, fn ($q) => $q->where('sr.snapshot_department_id', $departmentId))
                    ->whereIn('p.status', ['active', 'closed'])->groupBy('p.id', 'p.title', 'p.start_date')
                    ->selectRaw('p.title, p.start_date, COUNT(DISTINCT sr.id) as respondents, AVG(sri.response_value) as score')
                    ->orderByDesc('p.start_date')->limit(4)->get()->reverse()->map(fn ($r) => [
                        'label' => $r->title, 'score' => $r->respondents >= $minimum ? (float) $r->score : null,
                    ])->values()->all();
            }
        }
        usort($factors, fn ($a, $b) => ($b['mean_score'] ?? -1) <=> ($a['mean_score'] ?? -1));
        $available = array_values(array_filter($factors, fn ($f) => $f['mean_score'] !== null));
        $lowest = $available ? $available[count($available) - 1] : null;
        $drivers = collect(config('research.factors'))->map(fn ($values, $name) => ['name' => $name, ...$values])->sortByDesc('beta')->values()->all();
        $interventions = DB::table('interventions')->when($departmentId, fn ($q) => $q->where('responsible_department_id', $departmentId));
        $activeInterventions = (clone $interventions)->whereIn('status', ['Planned', 'Approved', 'In Progress'])->count();
        $pendingApprovals = (clone $interventions)->where('approval_status', 'Pending')->count();
        $themes = [];
        if (! $baseline && $period && $respondents >= $minimum) {
            $themes = DB::table('feedback_theme_links as l')->join('feedback_themes as t', 't.id', '=', 'l.theme_id')
                ->join('feedback as f', 'f.id', '=', 'l.feedback_id')->where('f.period_id', $period->id)
                ->when($departmentId, fn ($q) => $q->where('f.snapshot_department_id', $departmentId))
                ->groupBy('t.id', 't.name')->selectRaw('t.name, COUNT(DISTINCT f.id) as count')->get()
                ->map(fn ($r) => ['name' => $r->name, 'count' => $r->count >= $minimum ? $r->count : null])->all();
        }
        $departments = DB::table('departments')->where('status', 'active')->orderBy('name')->get();
        $scope = $departmentId ? ($departments->firstWhere('id', $departmentId)?->name ?? 'Assigned unit') : 'All operational units';
        $source = $baseline ? 'Research baseline' : ($demo ? 'Demonstration data' : 'Operational data');

        return compact('dataset', 'baseline', 'demo', 'unitScope', 'departmentId', 'periods', 'period', 'minimum', 'factors', 'retention', 'headcount', 'units', 'respondents', 'participation', 'retentionRate', 'turnoverRate', 'trend', 'lowest', 'drivers', 'activeInterventions', 'pendingApprovals', 'themes', 'departments', 'scope', 'source', 'cohorts');
    }

    private function cohorts(int $periodId, ?int $previousId, ?int $departmentId, int $minimum): array
    {
        $result = [];
        foreach (['departments' => 'snapshot_department_id', 'employee_categories' => 'snapshot_category_id'] as $table => $column) {
            $rows = DB::table('survey_responses as r')->join('survey_response_items as i', 'i.response_id', '=', 'r.id')
                ->join('survey_questions as q', 'q.id', '=', 'i.question_id')->join('hrm_factors as f', 'f.id', '=', 'q.factor_id')
                ->join($table.' as c', 'c.id', '=', 'r.'.$column)->where('f.is_outcome', 1)
                ->whereIn('r.period_id', array_filter([$periodId, $previousId]))
                ->when($departmentId, fn ($q) => $q->where('r.snapshot_department_id', $departmentId))
                ->groupBy('c.id', 'c.name', 'r.period_id')->selectRaw('c.id, c.name, r.period_id, COUNT(DISTINCT r.id) as total, AVG(i.response_value) as score')->get()->groupBy('id');
            foreach ($rows as $points) {
                $current = $points->firstWhere('period_id', $periodId);
                $previous = $points->firstWhere('period_id', $previousId);
                $score = $current && $current->total >= $minimum ? (float) $current->score : null;
                $old = $previous && $previous->total >= $minimum ? (float) $previous->score : null;
                $result[] = ['name' => $points->first()->name, 'type' => $table === 'departments' ? 'Operational unit' : 'Employee category',
                    'current' => $score, 'previous' => $old, 'trend' => trend_label($score, $old)];
            }
        }

        return $result;
    }

    private function scores(int $periodId, ?int $departmentId)
    {
        return DB::table('hrm_factors as f')->leftJoin('survey_questions as q', function ($join) use ($periodId): void {
            $join->on('q.factor_id', '=', 'f.id')->where('q.period_id', $periodId);
        })->leftJoin('survey_response_items as i', 'i.question_id', '=', 'q.id')
            ->leftJoin('survey_responses as r', function ($join) use ($periodId, $departmentId): void {
                $join->on('r.id', '=', 'i.response_id')->where('r.period_id', $periodId);
                if ($departmentId) {
                    $join->where('r.snapshot_department_id', $departmentId);
                }
            })->where('f.status', 'active')->groupBy('f.id', 'f.name', 'f.is_outcome')
            ->selectRaw('f.id, f.name, f.is_outcome, COUNT(DISTINCT r.id) as respondents, AVG(CASE WHEN r.id IS NOT NULL THEN i.response_value END) as mean_score')->get();
    }
}
