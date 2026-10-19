<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PriorityService
{
    public function suggestions(int $periodId): array
    {
        $db = db();
        $previousId = previous_period_id($db, $periodId);
        $previous = $previousId ? collect(factor_means($db, $previousId))->keyBy('id') : collect();
        $rules = decision_thresholds($db);
        $stored = DB::table('priority_areas')->where('period_id', $periodId)->whereNull('department_id')->get()->keyBy('factor_id');
        $recommendations = DB::table('recommendation_factors as rf')->join('recommendation_library as r', 'r.id', '=', 'rf.recommendation_id')
            ->where('r.status', 'active')->orderBy('r.id')->get(['rf.factor_id', 'r.title'])->groupBy('factor_id');
        $feedback = DB::table('feedback as f')->join('feedback_categories as c', 'c.id', '=', 'f.category_id')
            ->where('f.period_id', $periodId)->groupBy('c.factor_id')->selectRaw('c.factor_id, COUNT(*) as total')->pluck('total', 'factor_id');
        $result = [];
        foreach (factor_means($db, $periodId) as $factor) {
            if ($factor['mean_score'] === null) {
                continue;
            }
            $score = (float) $factor['mean_score'];
            $old = $previous->get($factor['id'])['mean_score'] ?? null;
            $trend = trend_label($score, $old === null ? null : (float) $old, $rules['band']);
            $action = $score < $rules['low'] ? 'Improve' : ($score < $rules['high'] ? 'Monitor' : 'Strengthen');
            $priority = $action === 'Improve' ? ($trend === 'Declining' ? 'High' : 'Medium') : ($action === 'Monitor' ? 'Normal' : 'Positive');
            $saved = $stored->get($factor['id']);
            $count = (int) $feedback->get($factor['id'], 0);
            $result[] = ['id' => $saved?->id, 'period_id' => $periodId, 'factor_id' => $factor['id'], 'factor_name' => $factor['name'],
                'current_score' => $score, 'previous_score' => $old, 'trend' => $trend, 'priority_level' => $priority,
                'action_category' => $action, 'theme_count' => min_group_ok($db, $count) ? $count : null,
                'suggested_recommendation' => $recommendations->get($factor['id'])?->first()?->title ?? 'Review factor-specific actions',
                'status' => $saved?->status ?? 'pending'];
        }

        return $result;
    }

    public function update(int $periodId, int $factorId, string $status): void
    {
        $suggestion = collect($this->suggestions($periodId))->firstWhere('factor_id', $factorId);
        abort_unless($suggestion, 422, 'No publishable priority suggestion exists for this factor.');
        unset($suggestion['id'], $suggestion['factor_name']);
        $suggestion['theme_count'] ??= 0;
        DB::table('priority_areas')->updateOrInsert(['period_id' => $periodId, 'factor_id' => $factorId, 'department_id' => null], [...$suggestion, 'status' => $status]);
    }
}
