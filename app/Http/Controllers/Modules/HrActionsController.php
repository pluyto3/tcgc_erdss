<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;

class HrActionsController extends ModuleController
{
    public function handle(Request $request)
    {
        $db = db();
        $query = $request->query();
        $input = $request->all();

        return $this->render('hr_actions', 'HR Actions', get_defined_vars());
    }
}
