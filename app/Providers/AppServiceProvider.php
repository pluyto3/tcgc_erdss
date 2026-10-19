<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SettingsService::class);
        $this->app->bind(PDO::class, fn () => DB::connection()->getPdo());
    }

    public function boot(): void
    {
        View::composer(['layouts.app', 'auth.login', 'reports.policy', 'reports.index', 'dashboard.overview'], function ($view): void {
            $view->with('settings', app(SettingsService::class));
        });
    }
}
