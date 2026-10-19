<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $minutes = max(5, (int) app(SettingsService::class)->get('session_timeout', 60));
            $expired = time() - (int) $request->session()->get('last_activity', time()) > $minutes * 60;
            $unlinked = in_array($user->role?->name, ['Employee', 'Department Head'], true) && ! $user->employee;
            if ($user->status !== 'active' || $expired || $unlinked) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', $expired ? 'Your session expired. Please sign in again.' : 'Your account is unavailable. Please contact your HR administrator.');
            }
            $request->session()->put('last_activity', time());
        }

        return $next($request);
    }
}
