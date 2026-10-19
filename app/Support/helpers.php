<?php

use App\Services\SettingsService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function db(): PDO
{
    $pdo = DB::connection()->getPdo();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}
function current_user(): ?array
{
    return Auth::user()?->context();
}
function role(): string
{
    return Auth::user()?->role?->name ?? '';
}
function user_id(): ?int
{
    return Auth::id();
}
function post(string $key, mixed $default = ''): string
{
    $value = request()->input($key, $default);

    return is_scalar($value) ? trim((string) $value) : '';
}
function flash(string $type, string $message): void
{
    session()->push('messages', compact('type', 'message'));
}
function setting(PDO $db, string $key, mixed $default = null): mixed
{
    return app(SettingsService::class)->get($key, $default);
}
function set_setting(PDO $db, string $key, string $value, int $userId): void
{
    app(SettingsService::class)->set($key, $value, $userId);
}
function module_redirect(string $url): never
{
    parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);
    $module = $query['page'] ?? 'dashboard';
    unset($query['page']);
    throw new HttpResponseException(redirect()->route($module, $query));
}
function audit(PDO $db, ?int $userId, string $action, string $entity = '', ?int $entityId = null, string $summary = ''): void
{
    DB::table('audit_logs')->insert(['user_id' => $userId, 'action' => $action, 'entity' => $entity, 'entity_id' => $entityId,
        'summary' => $summary, 'ip_address' => request()->ip(), 'created_at' => now()]);
}
function badge(string $text, string $tone = 'neutral'): string
{
    return '<span class="badge badge-'.e($tone).'">'.e($text).'</span>';
}
function fmt_num(mixed $n, int $d = 2): string
{
    return $n === null ? '—' : number_format((float) $n, $d);
}
function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}
function checked(mixed $a, mixed $b = true): string
{
    return $a == $b ? ' checked' : '';
}
function query_url(array $replace = []): string
{
    return '?'.http_build_query(array_merge(request()->query(), $replace));
}
function card(string $label, mixed $value, string $hint = ''): string
{
    return '<div class="kpi-card"><div class="kpi-label">'.e($label).'</div><div class="kpi-value">'.e($value).'</div><div class="kpi-hint">'.e($hint).'</div></div>';
}
function section_title(string $title, string $desc = ''): string
{
    return '<div class="section-title"><div><h2>'.e($title).'</h2><p>'.e($desc).'</p></div></div>';
}
function parse_scale_labels(?string $raw, int $min = 1, int $max = 4): array
{
    $out = [];
    foreach (explode('|', (string) $raw) as $part) {
        if (str_contains($part, '=')) {
            [$key, $value] = array_map('trim', explode('=', $part, 2));
            if (is_numeric($key)) {
                $out[(int) $key] = $value;
            }
        }
    }
    for ($i = $min; $i <= $max; $i++) {
        $out[$i] ??= (string) $i;
    }
    ksort($out);

    return $out;
}
function report_csv(string $filename, array $headers, array $rows): never
{
    $response = response()->streamDownload(function () use ($headers, $rows): void {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        $safe = static fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value;
        fputcsv($out, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, array_map($safe, $row), ',', '"', '');
        }
        fclose($out);
    }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    throw new HttpResponseException($response);
}
require_once __DIR__.'/analytics.php';
