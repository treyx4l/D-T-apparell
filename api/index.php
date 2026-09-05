<?php
declare(strict_types=1);

// Force text/html charset utf-8 content type header so browser renders HTML instead of downloading
header('Content-Type: text/html; charset=utf-8');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode($uri);

if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

// Map requested URI to the corresponding PHP view/file
if ($uri === '/' || $uri === '/index' || $uri === '/index.php' || $uri === '') {
    require __DIR__ . '/../views/index.php';
    exit;
}

if ($uri === '/store' || $uri === '/store.php') {
    require __DIR__ . '/../views/store.php';
    exit;
}

if (str_starts_with($uri, '/auth/')) {
    $file = __DIR__ . '/..' . $uri;
    if (!str_ends_with($file, '.php')) {
        $file .= '.php';
    }
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

if (str_starts_with($uri, '/dt-admin')) {
    $file = __DIR__ . '/..' . $uri;
    if ($uri === '/dt-admin' || $uri === '/dt-admin/') {
        $file = __DIR__ . '/../dt-admin/index.php';
    } elseif (!str_ends_with($file, '.php')) {
        $file .= '.php';
    }
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

if (str_starts_with($uri, '/api/')) {
    $file = __DIR__ . '/..' . $uri;
    if (!str_ends_with($file, '.php')) {
        $file .= '.php';
    }
    if (file_exists($file) && $file !== __FILE__) {
        header('Content-Type: application/json; charset=utf-8');
        require $file;
        exit;
    }
}

// Default fallback to index view
require __DIR__ . '/../views/index.php';
