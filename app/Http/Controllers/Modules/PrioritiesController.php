<?php

namespace App\Http\Controllers\Modules;

use App\Services\PriorityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrioritiesController
{
    public function handle(Request $request, PriorityService $priorities)
    {
        if ($request->isMethod('POST')) {
            $data = $request->validate(['period_id' => ['required', 'integer', 'exists:assessment_periods,id'],
                'factor_id' => ['required', 'integer', 'exists:hrm_factors,id'], 'status' => ['required', 'in:pending,confirmed,dismissed']]);
            $priorities->update($data['period_id'], $data['factor_id'], $data['status']);
            audit(db(), user_id(), 'update', 'priority_area', null, 'Status: '.$data['status']);
            flash('success', 'Priority status updated.');

            return redirect()->route('priorities', ['period_id' => $data['period_id']]);
        }
        $request->validate(['period_id' => ['nullable', 'integer', 'exists:assessment_periods,id']]);
        $periods = DB::table('assessment_periods')->whereIn('status', ['active', 'closed'])->orderByDesc('start_date')->orderByDesc('id')->get();
        $periodId = $request->integer('period_id') ?: ($periods->firstWhere('status', 'active')?->id ?? $periods->first()?->id);
        $rows = $periodId ? $priorities->suggestions($periodId) : [];
        $rules = decision_thresholds(db());

        return view('dashboard.priorities', compact('rows', 'periods', 'periodId', 'rules'));
    }
}
