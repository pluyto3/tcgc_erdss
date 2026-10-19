<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;

class PrivacyController extends ModuleController
{
    public function handle(Request $request)
    {
        $db = db();
        $query = $request->query();
        $input = $request->all();

        return $this->render('privacy', 'Privacy', get_defined_vars());
    }
}
