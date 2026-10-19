<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        DB::unprepared(file_get_contents(database_path('schema/'.$driver.'.sql')));
        if (DB::table('roles')->exists()) {
            require_once database_path('support/upgrade.php');
            apply_schema_upgrades(db());
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This migration adopts existing employee data. Restore a database backup to roll it back.');
    }
};
