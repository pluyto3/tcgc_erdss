<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use App\Services\InterventionService;
use Illuminate\Http\Request;

class InterventionsController extends ModuleController
{
    public function handle(Request $request, InterventionService $interventions)
    {
        if ($request->isMethod('POST')) {
            flash('success', $interventions->apply($request));

            return redirect()->route('interventions');
        }
        $title = match (role()) {
            'Department Head' => 'My Interventions', 'Executive' => 'Intervention Approvals', default => 'Interventions'
        };

        return $this->render('interventions', $title, ['db' => db(), 'query' => $request->query()]);
    }
}
