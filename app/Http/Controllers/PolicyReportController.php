<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class PolicyReportController
{
    public function __invoke(Request $request, DashboardService $dashboard)
    {
        $data = $dashboard->data($request);
        audit(db(), user_id(), 'export', 'policy_report', null, $data['source'].' · '.$data['scope']);

        return view('reports.policy', $data);
    }
}
