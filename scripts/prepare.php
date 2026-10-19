<?php

$root = dirname(__DIR__);
foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/app/private', 'public/uploads'] as $directory) {
    $path = $root.'/'.$directory;
    if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
        throw new RuntimeException('Unable to create runtime directory: '.$path);
    }
}
