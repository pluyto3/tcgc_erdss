<?php

use Symfony\Component\Process\Process;

// Prepare a local demonstration installation without copying another machine's secrets.
$root = dirname(__DIR__);
if (PHP_VERSION_ID < 80401) {
    fwrite(STDERR, "PHP 8.4.1 or newer is required by the included dependency versions.\n");
    exit(1);
}
foreach (['pdo_sqlite', 'mbstring', 'openssl', 'fileinfo'] as $extension) {
    if (! extension_loaded($extension)) {
        fwrite(STDERR, "Enable the {$extension} PHP extension before continuing.\n");
        exit(1);
    }
}
if (! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Dependencies are missing. Run composer install first.\n");
    exit(1);
}
require $root.'/vendor/autoload.php';
require __DIR__.'/prepare.php';
if (! is_file($root.'/.env') && ! copy($root.'/.env.example', $root.'/.env')) {
    throw new RuntimeException('Unable to create the local .env file.');
}
Dotenv\Dotenv::createImmutable($root)->safeLoad();
if (($_ENV['APP_ENV'] ?? 'production') !== 'local' || ($_ENV['DB_CONNECTION'] ?? 'sqlite') !== 'sqlite') {
    fwrite(STDERR, "This helper is for a local SQLite demonstration installation. Your existing environment was preserved.\n");
    exit(1);
}
$database = $_ENV['DB_DATABASE'] ?? $root.'/database/database.sqlite';
if (! is_file($database) && ! touch($database)) {
    throw new RuntimeException('Unable to create the SQLite database: '.$database);
}
$commands = [['config:clear'], ['view:clear']];
if (empty($_ENV['APP_KEY'])) {
    $commands[] = ['key:generate'];
}
$commands[] = ['migrate', '--seed', '--no-interaction'];
foreach ($commands as $arguments) {
    $process = new Process([PHP_BINARY, $root.'/artisan', ...$arguments], $root);
    $process->setTimeout(180);
    $process->run(static function (string $type, string $output): void {
        echo $output;
    });
    if (! $process->isSuccessful()) {
        exit($process->getExitCode() ?: 1);
    }
}
echo "\nLocal setup complete. Run: php artisan serve\n";
echo "Open http://127.0.0.1:8000\n";
echo "Demo HR account: hr.admin@tcgc-demo.local / DemoPass123!\n";
