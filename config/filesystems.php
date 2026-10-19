<?php

return [
    'default' => 'local',
    'disks' => [
        'local' => ['driver' => 'local', 'root' => storage_path('app/private'), 'throw' => true],
        'public' => ['driver' => 'local', 'root' => public_path('uploads'), 'url' => env('APP_URL').'/uploads', 'visibility' => 'public', 'throw' => true],
    ],
];
