<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;

class AnalyticsController extends ModuleController
{
    public function handle(Request $request)
    {
        $db = db();
        $query = $request->query();
        $input = $request->all();

        return $this->render('analytics', role() === 'Department Head' ? 'Unit Analytics' : 'HRM Analytics', get_defined_vars());
    }
}
