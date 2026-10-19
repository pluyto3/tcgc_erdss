<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;

class OrgResultsController extends ModuleController
{
    public function handle(Request $request)
    {
        $db = db();
        $query = $request->query();
        $input = $request->all();

        return $this->render('org_results', 'Organizational Results', get_defined_vars());
    }
}
