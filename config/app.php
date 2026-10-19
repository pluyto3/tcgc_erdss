<?php

return [
    'name' => env('APP_NAME', 'TCGC Retention DSS'), 'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false), 'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'Asia/Manila', 'locale' => 'en', 'fallback_locale' => 'en', 'faker_locale' => 'en_PH',
    'cipher' => 'AES-256-CBC', 'key' => env('APP_KEY'), 'previous_keys' => [], 'maintenance' => ['driver' => 'file'],
];
