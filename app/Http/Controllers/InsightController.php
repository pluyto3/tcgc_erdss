<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class InsightController
{
    public function handle(Request $request, DashboardService $dashboard)
    {
        $module = $request->route('module');

        return view($module === 'drivers' ? 'dashboard.drivers' : 'dashboard.experience', $dashboard->data($request));
    }
}
