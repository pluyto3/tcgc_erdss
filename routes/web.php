<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\PolicyReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
// Keep bookmarked links from the original application working.
Route::match(['get', 'post'], '/{entry?}', function (Request $request) {
    $module = $request->query('page', $request->user() ? 'dashboard' : 'login');
    abort_unless(is_string($module) && (isset(config('modules')[$module]) || in_array($module, ['login', 'logout'], true)), 404);
    if ($module === 'logout') {
        return redirect()->route('dashboard');
    }
    $query = $request->query();
    unset($query['page']);

    return redirect()->route($module === 'login' && $request->isMethod('post') ? 'login.store' : $module, $query, $request->isMethod('post') ? 307 : 302);
})->where('entry', 'index\.php')->name('legacy');

Route::middleware('auth')->group(function (): void {
    foreach (config('modules') as $module => $entry) {
        $controller = match ($module) {
            'dashboard' => DashboardController::class,
            'drivers', 'employee_perspective' => InsightController::class,
            default => 'App\\Http\\Controllers\\Modules\\'.Str::studly($module).'Controller',
        };
        Route::match(isset($entry['write_roles']) ? ['get', 'post'] : ['get'], '/'.str_replace('_', '-', $module), [$controller, 'handle'])
            ->defaults('module', $module)->middleware('module')->name($module);
    }
    Route::get('/policy-report', PolicyReportController::class)->defaults('module', 'reports')->middleware('module')->name('policy-report');
});
