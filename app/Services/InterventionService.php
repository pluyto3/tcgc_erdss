<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InterventionService
{
    public function apply(Request $request): string
    {
        $action = $request->validate(['action' => ['required', 'in:save_intervention,approve,update_progress']])['action'];

        return DB::transaction(function () use ($action, $request): string {
            return match ($action) {
                'save_intervention' => $this->save($request),
                'approve' => $this->approve($request),
                'update_progress' => $this->progress($request),
            };
        });
    }

    private function save(Request $request): string
    {
        abort_unless(role() === 'HR Administrator', 403);
        $data = $request->validate(['id' => ['nullable', 'integer', 'exists:interventions,id'], 'title' => ['required', 'string', 'max:250'],
            'factor_id' => ['required', 'integer', 'exists:hrm_factors,id'], 'priority_area_id' => ['nullable', 'integer', 'exists:priority_areas,id'],
            'recommendation_id' => ['nullable', 'integer', 'exists:recommendation_library,id'], 'issue' => ['nullable', 'string', 'max:10000'],
            'description' => ['nullable', 'string', 'max:10000'], 'objective' => ['nullable', 'string', 'max:10000'],
            'responsible_department_id' => ['nullable', 'integer', 'exists:departments,id'], 'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_date' => ['nullable', 'date_format:Y-m-d'], 'target_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'budget_amount' => ['required', 'numeric', 'between:0,9999999999.99'], 'expected_outcome' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:10000'], 'is_public' => ['required', 'boolean']]);
        $id = $data['id'] ?? null;
        unset($data['id']);
        $record = [...$data, 'status' => 'Planned', 'approval_status' => 'Pending', 'approved_by' => null,
            'approved_at' => null, 'approval_notes' => null, 'updated_at' => now()];
        if ($id) {
            DB::table('interventions')->where('id', $id)->update($record);
        } else {
            $id = DB::table('interventions')->insertGetId([...$record, 'code' => 'INT-'.now()->year.'-'.Str::upper(Str::random(8)), 'created_at' => now()]);
        }
        audit(db(), user_id(), 'save', 'intervention', $id, 'Submitted for executive approval');

        return 'Intervention saved and submitted for executive approval.';
    }

    private function approve(Request $request): string
    {
        abort_unless(role() === 'Executive', 403);
        $data = $request->validate(['intervention_id' => ['required', 'integer', 'exists:interventions,id'],
            'decision' => ['required', 'in:Approved,Rejected'], 'approval_notes' => ['nullable', 'string', 'max:10000']]);
        $intervention = DB::table('interventions')->where('id', $data['intervention_id'])->lockForUpdate()->first();
        abort_unless($intervention->approval_status === 'Pending', 422, 'This intervention has already been reviewed.');
        DB::table('interventions')->where('id', $intervention->id)->update(['approval_status' => $data['decision'], 'approved_by' => user_id(),
            'approved_at' => now(), 'approval_notes' => $data['approval_notes'] ?? null, 'status' => $data['decision'] === 'Approved' ? 'Approved' : 'Planned', 'updated_at' => now()]);
        audit(db(), user_id(), strtolower($data['decision']), 'intervention', $intervention->id, $data['approval_notes'] ?? $data['decision']);

        return 'Intervention marked '.$data['decision'].'.';
    }

    private function progress(Request $request): string
    {
        abort_unless(in_array(role(), ['HR Administrator', 'Department Head'], true), 403);
        $data = $request->validate(['intervention_id' => ['required', 'integer', 'exists:interventions,id'],
            'status' => ['required', 'in:Approved,In Progress,Completed,On Hold,Cancelled'], 'note' => ['required', 'string', 'max:10000']]);
        $intervention = DB::table('interventions')->where('id', $data['intervention_id'])->lockForUpdate()->first();
        if (role() === 'Department Head') {
            abort_unless((int) $intervention->responsible_department_id === (int) current_user()['department_id'], 403);
        }
        abort_unless($intervention->approval_status === 'Approved', 422, 'Only approved interventions can receive progress updates.');
        DB::table('intervention_updates')->insert(['intervention_id' => $intervention->id, 'user_id' => user_id(), 'status' => $data['status'], 'note' => $data['note'], 'created_at' => now()]);
        DB::table('interventions')->where('id', $intervention->id)->update(['status' => $data['status'], 'updated_at' => now()]);
        audit(db(), user_id(), 'progress', 'intervention', $intervention->id, $data['status']);

        return 'Intervention progress updated.';
    }
}
