<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\ModuleController;
use Illuminate\Http\Request;

class ProfileController extends ModuleController
{
    public function handle(Request $request)
    {
        $db = db();
        $query = $request->query();
        $input = $request->all();

        return $this->render('profile', 'My Profile', get_defined_vars());
    }
}
