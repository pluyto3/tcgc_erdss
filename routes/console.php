<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('retention:refresh-priorities {period?}', function (): void {
    $period = $this->argument('period') ?: (active_period(db())['id'] ?? null);
    if (! $period) {
        $this->warn('No active assessment period.');

        return;
    }
    refresh_priorities(db(), (int) $period);
    $this->info('Priority suggestions refreshed.');
})->purpose('Refresh management suggestions after assessment data changes');
