<?php

namespace App\Http\Controllers\Modules;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportsController
{
    public function handle(Request $request, ReportService $reports)
    {
        $data = $request->validate(['type' => ['nullable', Rule::in(array_keys(ReportService::TYPES))],
            'period_id' => ['nullable', 'integer', 'exists:assessment_periods,id'], 'before' => ['nullable', 'integer', 'exists:assessment_periods,id'],
            'after' => ['nullable', 'integer', 'exists:assessment_periods,id'], 'export' => ['nullable', 'in:csv']]);
        $type = $data['type'] ?? 'retention';
        $departmentId = role() === 'Department Head' ? (int) current_user()['department_id'] : null;
        abort_if($type === 'priorities' && $departmentId !== null, 403);
        $periods = DB::table('assessment_periods')->whereIn('status', ['active', 'closed'])->orderByDesc('start_date')->orderByDesc('id')->get();
        $periodId = $data['period_id'] ?? ($periods->firstWhere('status', 'active')?->id ?? $periods->first()?->id);
        $beforeId = $data['before'] ?? $periods->get(1)?->id;
        $afterId = $data['after'] ?? $periods->first()?->id;
        $report = $reports->build($type, $periodId, $departmentId, $beforeId, $afterId);
        audit(db(), user_id(), isset($data['export']) ? 'export' : 'view', 'report', null, ReportService::TYPES[$type]);
        if (isset($data['export'])) {
            report_csv('tcgc_'.$type.'.csv', $report['headers'], $report['rows']);
        }
        $tabs = ReportService::TYPES;
        if ($departmentId) {
            unset($tabs['priorities']);
        }

        return view('reports.index', [...$report, ...compact('type', 'tabs', 'periods', 'periodId', 'beforeId', 'afterId', 'departmentId')]);
    }
}
