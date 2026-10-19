<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SettingsService
{
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->values ??= DB::table('system_settings')->pluck('value', 'key_name')->all();

        return $this->values[$key] ?? $default;
    }

    public function set(string $key, string $value, ?int $userId): void
    {
        DB::table('system_settings')->updateOrInsert(['key_name' => $key], ['value' => $value, 'updated_by' => $userId, 'updated_at' => now()]);
        $this->values = null;
    }
}
