<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/vendor/autoload.php')) {
    require_once $rootPath . '/vendor/autoload.php';
    if (file_exists($rootPath . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($rootPath);
        $dotenv->load();
    }
} elseif (file_exists($rootPath . '/.env')) {
    $lines = file($rootPath . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

define('APP_NAME', $_ENV['APP_NAME'] ?? 'D&T Apparels');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('BASE_URL', rtrim($_ENV['BASE_URL'] ?? '', '/'));
define('ADMIN_PATH', $_ENV['ADMIN_PATH'] ?? 'dt-admin');
define('SUPABASE_URL', $_ENV['SUPABASE_URL'] ?? '');
define('SUPABASE_ANON_KEY', $_ENV['SUPABASE_ANON_KEY'] ?? '');
define('SUPABASE_SERVICE_ROLE_KEY', $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '');
define('WHATSAPP_NUMBER', preg_replace('/[^0-9]/', '', $_ENV['WHATSAPP_NUMBER'] ?? '233544570238'));
define('STORAGE_BUCKET', $_ENV['STORAGE_BUCKET'] ?? 'product-images');

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if (BASE_URL !== '') {
        return BASE_URL . ($path !== '' ? '/' . $path : '');
    }
    return $path !== '' ? '/' . $path : '/';
}

function asset(string $path): string
{
    return url($path);
}

function logo_path(): string
{
    $root = dirname(__DIR__);
    if (file_exists($root . '/assets/logo.png')) {
        return 'assets/logo.png';
    }
    if (file_exists($root . '/logo.png')) {
        return 'logo.png';
    }
    return 'assets/logo.png';
}

function logo_url(): string
{
    return asset(logo_path());
}

function favicon_url(): string
{
    return logo_url();
}

function isProduction(): bool
{
    return APP_ENV === 'production';
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('dt_apparels_session');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isProduction(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

if (isProduction()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$csp = "default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
    . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
    . "img-src 'self' data: https: blob:; "
    . "connect-src 'self' " . SUPABASE_URL . " https://*.supabase.co; "
    . "frame-ancestors 'none';";
header('Content-Security-Policy: ' . $csp);

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
