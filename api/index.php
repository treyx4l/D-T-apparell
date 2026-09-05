<?php
declare(strict_types=1);

// Force text/html charset utf-8 content type header so browser renders HTML instead of downloading
header('Content-Type: text/html; charset=utf-8');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode($uri);

if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

// 1. Home page
if ($uri === '/' || $uri === '/index' || $uri === '/index.php' || $uri === '') {
    require __DIR__ . '/../views/index.php';
    exit;
}

// 2. Store page
if ($uri === '/store' || $uri === '/store.php') {
    require __DIR__ . '/../views/store.php';
    exit;
}

// 3. Auth pages (/auth/*)
if (str_starts_with($uri, '/auth/')) {
    $path = str_replace('/auth/', '', $uri);
    if (str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }
    $target = __DIR__ . '/../views/auth/' . $path . '.php';
    if (file_exists($target)) {
        require $target;
        exit;
    }
}

// 4. Admin pages (/dt-admin/*)
if (str_starts_with($uri, '/dt-admin')) {
    $path = str_replace('/dt-admin', '', $uri);
    $path = ltrim($path, '/');
    if ($path === '' || $path === 'index.php') {
        $path = 'index';
    }
    if (str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }
    $target = __DIR__ . '/../views/dt-admin/' . $path . '.php';
    if (file_exists($target)) {
        require $target;
        exit;
    }
}

// 5. API Endpoints (/api/*)
if (str_starts_with($uri, '/api/')) {
    $path = str_replace('/api/', '', $uri);
    if (!str_ends_with($path, '.php')) {
        $path .= '.php';
    }
    $target = __DIR__ . '/' . $path;
    if (file_exists($target) && $target !== __FILE__) {
        header('Content-Type: application/json; charset=utf-8');
        require $target;
        exit;
    }
}

// Fallback to index view
require __DIR__ . '/../views/index.php';
