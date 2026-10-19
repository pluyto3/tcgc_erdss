<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationsController extends ModuleController
{
    public function handle(Request $request)
    {
        if ($request->isMethod('POST')) {
            $data = $request->validate(['id' => ['nullable', 'integer', 'exists:recommendation_library,id'], 'title' => ['required', 'string', 'max:250'],
                'description' => ['nullable', 'string', 'max:10000'], 'factor_id' => ['required', 'integer', 'exists:hrm_factors,id'], 'status' => ['required', 'in:active,inactive']]);
            DB::transaction(function () use ($data): void {
                $record = ['title' => $data['title'], 'description' => $data['description'] ?? null, 'status' => $data['status'], 'updated_at' => now()];
                $id = $data['id'] ?? null;
                if ($id) {
                    DB::table('recommendation_library')->where('id', $id)->update($record);
                    DB::table('recommendation_factors')->where('recommendation_id', $id)->delete();
                } else {
                    $id = DB::table('recommendation_library')->insertGetId([...$record, 'created_at' => now()]);
                }
                DB::table('recommendation_factors')->insert(['recommendation_id' => $id, 'factor_id' => $data['factor_id']]);
                audit(db(), user_id(), 'save', 'recommendation', $id, $data['title']);
            });
            flash('success', 'Recommendation saved.');

            return redirect()->route('recommendations');
        }
        $links = DB::table('recommendation_factors as rf')->join('hrm_factors as f', 'f.id', '=', 'rf.factor_id')->get(['rf.recommendation_id', 'f.name'])->groupBy('recommendation_id');
        $rows = DB::table('recommendation_library')->orderBy('title')->get()->map(fn ($row) => [...(array) $row, 'factors' => $links->get($row->id)?->pluck('name')->implode(', ') ?? ''])->all();

        return $this->render('recommendations', 'Recommendations', ['db' => db(), 'query' => $request->query(), 'rows' => $rows]);
    }
}
