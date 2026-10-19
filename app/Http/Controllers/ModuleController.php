<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

abstract class ModuleController extends Controller
{
    protected function render(string $module, string $title, array $variables)
    {
        unset($variables['this']);
        $content = view('modules.'.$module, $variables)->render();
        $content = preg_replace_callback('/href="(?:index\.php)?\?page=([^"\s]+)"/', function ($match) {
            parse_str(html_entity_decode('page='.$match[1]), $query);
            $page = $query['page'];
            unset($query['page']);

            return 'href="'.e(route($page, $query)).'"';
        }, $content);

        return response()->view('layouts.app', compact('module', 'title', 'content'));
    }
}
