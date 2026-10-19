<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo seeding is disabled in production. Import authorized institutional data instead.');
        }
        if (DB::table('roles')->exists()) {
            return;
        }
        require_once database_path('seeders/demo.php');
        seed_database(db(), ['demo_password' => env('DEMO_PASSWORD', 'DemoPass123!')]);
        DB::table('system_settings')->where('key_name', 'assessment_frequency')->update(['value' => 'Quarterly']);
    }
}
