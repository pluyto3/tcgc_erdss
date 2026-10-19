<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    public function handle(Request $request, DashboardService $dashboard)
    {
        if (role() === 'Employee') {
            $period = active_period(db());
            $completed = $period && DB::table('survey_participation')->where('period_id', $period['id'])->where('employee_id', current_user()['employee_id'])->exists();

            return view('dashboard.employee', compact('period', 'completed'));
        }

        return view('dashboard.overview', $dashboard->data($request));
    }
}
