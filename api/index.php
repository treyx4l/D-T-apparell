<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode($uri);

// Strip trailing slash except for root
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

// Map requested URI to the corresponding PHP file
$fileMap = [
    '/' => __DIR__ . '/../index.php',
    '/index.php' => __DIR__ . '/../index.php',
    '/store' => __DIR__ . '/../store.php',
    '/store.php' => __DIR__ . '/../store.php',
    '/auth/account' => __DIR__ . '/../auth/account.php',
    '/auth/account.php' => __DIR__ . '/../auth/account.php',
    '/auth/callback' => __DIR__ . '/../auth/callback.php',
    '/auth/callback.php' => __DIR__ . '/../auth/callback.php',
    '/auth/check-email' => __DIR__ . '/../auth/check-email.php',
    '/auth/check-email.php' => __DIR__ . '/../auth/check-email.php',
    '/auth/login' => __DIR__ . '/../auth/login.php',
    '/auth/login.php' => __DIR__ . '/../auth/login.php',
    '/auth/logout' => __DIR__ . '/../auth/logout.php',
    '/auth/logout.php' => __DIR__ . '/../auth/logout.php',
    '/auth/signup' => __DIR__ . '/../auth/signup.php',
    '/auth/signup.php' => __DIR__ . '/../auth/signup.php',
    '/auth/verify-code' => __DIR__ . '/../auth/verify-code.php',
    '/auth/verify-code.php' => __DIR__ . '/../auth/verify-code.php',
    '/auth/verify-email' => __DIR__ . '/../auth/verify-email.php',
    '/auth/verify-email.php' => __DIR__ . '/../auth/verify-email.php',
    '/dt-admin' => __DIR__ . '/../dt-admin/index.php',
    '/dt-admin/index.php' => __DIR__ . '/../dt-admin/index.php',
    '/dt-admin/dashboard' => __DIR__ . '/../dt-admin/dashboard.php',
    '/dt-admin/dashboard.php' => __DIR__ . '/../dt-admin/dashboard.php',
    '/dt-admin/orders' => __DIR__ . '/../dt-admin/orders.php',
    '/dt-admin/orders.php' => __DIR__ . '/../dt-admin/orders.php',
    '/dt-admin/logout' => __DIR__ . '/../dt-admin/logout.php',
    '/dt-admin/logout.php' => __DIR__ . '/../dt-admin/logout.php',
    '/api/session' => __DIR__ . '/../api/session.php',
    '/api/session.php' => __DIR__ . '/../api/session.php',
    '/api/session-status' => __DIR__ . '/../api/session-status.php',
    '/api/session-status.php' => __DIR__ . '/../api/session-status.php',
    '/api/place-order' => __DIR__ . '/../api/place-order.php',
    '/api/place-order.php' => __DIR__ . '/../api/place-order.php',
    '/api/logout' => __DIR__ . '/../api/logout.php',
    '/api/logout.php' => __DIR__ . '/../api/logout.php',
];

if (isset($fileMap[$uri]) && file_exists($fileMap[$uri])) {
    require $fileMap[$uri];
    exit;
}

// Fallback: check if file directly exists in workspace root/subdirectories
$target = __DIR__ . '/..' . $uri;
if (file_exists($target) && !is_dir($target) && str_ends_with($target, '.php')) {
    require $target;
    exit;
}

// Default fallback to index.php
require __DIR__ . '/../index.php';
