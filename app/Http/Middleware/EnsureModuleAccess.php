<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $module = $request->route('module');
        $entry = config('modules.'.$module);
        abort_unless($entry && in_array(role(), $entry['roles'], true), 403);
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort_unless(in_array(role(), $entry['write_roles'] ?? [], true), 403);
        }
        if (role() === 'Department Head') {
            abort_unless(current_user()['department_id'], 403, 'Your account needs an assigned department.');
        }

        return $next($request);
    }
}
