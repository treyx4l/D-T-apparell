<?php

declare(strict_types=1);

function csrf_secret(): string
{
    $secret = $_ENV['SESSION_SECRET'] ?? 'dt-apparels-secret-csrf-key-2026';
    return $secret !== '' ? $secret : 'dt-apparels-secret-csrf-key-2026';
}

function csrf_token(): string
{
    if (!empty($_SESSION['csrf_token']) && verify_csrf($_SESSION['csrf_token'])) {
        return $_SESSION['csrf_token'];
    }

    $nonce = bin2hex(random_bytes(16));
    $time = (string) time();
    $sig = hash_hmac('sha256', $nonce . ':' . $time, csrf_secret());
    $token = $nonce . '.' . $time . '.' . $sig;

    $_SESSION['csrf_token'] = $token;
    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . escape_output(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (empty($token) || !is_string($token)) {
        return false;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    [$nonce, $timeStr, $sig] = $parts;
    $time = (int) $timeStr;

    // Check expiration (8 hours)
    if (time() - $time > 28800 || $time > time() + 300) {
        return false;
    }

    $expectedSig = hash_hmac('sha256', $nonce . ':' . $timeStr, csrf_secret());
    return hash_equals($expectedSig, $sig);
}

function require_csrf(): void
{
    if (!verify_csrf()) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function regenerate_csrf_token(): void
{
    $nonce = bin2hex(random_bytes(16));
    $time = (string) time();
    $sig = hash_hmac('sha256', $nonce . ':' . $time, csrf_secret());
    $_SESSION['csrf_token'] = $nonce . '.' . $time . '.' . $sig;
}
