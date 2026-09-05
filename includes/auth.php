<?php

declare(strict_types=1);

function escape_output(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize_input(?string $value): string
{
    return trim(strip_tags($value ?? ''));
}

function sanitize_email(?string $value): string
{
    $email = filter_var(trim($value ?? ''), FILTER_SANITIZE_EMAIL);
    return is_string($email) ? $email : '';
}

function sanitize_float(mixed $value): float
{
    return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['access_token']);
}

function current_user_role(): string
{
    return $_SESSION['user_role'] ?? 'guest';
}

function is_admin(): bool
{
    return current_user_role() === 'admin';
}

function get_auth_cookie_name(): string
{
    return 'dt_apparels_auth_payload';
}

function save_auth_cookie(array $user, string $accessToken, string $refreshToken): void
{
    $payload = json_encode([
        'id' => $user['id'] ?? '',
        'email' => $user['email'] ?? '',
        'name' => $user['user_metadata']['full_name'] ?? $user['user_metadata']['name'] ?? ($user['email'] ?? ''),
        'role' => $user['user_metadata']['role'] ?? ($user['role'] ?? 'customer'),
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'time' => time(),
    ]);

    $secret = $_ENV['SESSION_SECRET'] ?? 'dt-apparels-secret-key-2026';
    $sig = hash_hmac('sha256', $payload, $secret);
    $cookieValue = base64_encode($payload) . '.' . $sig;

    setcookie(get_auth_cookie_name(), $cookieValue, [
        'expires' => time() + (30 * 86400),
        'path' => '/',
        'domain' => '',
        'secure' => isProduction(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function read_auth_cookie(): ?array
{
    if (empty($_COOKIE[get_auth_cookie_name()])) {
        return null;
    }

    $parts = explode('.', $_COOKIE[get_auth_cookie_name()], 2);
    if (count($parts) !== 2) {
        return null;
    }

    [$encoded, $sig] = $parts;
    $payload = base64_decode($encoded, true);
    if (!is_string($payload)) {
        return null;
    }

    $secret = $_ENV['SESSION_SECRET'] ?? 'dt-apparels-secret-key-2026';
    $expectedSig = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSig, $sig)) {
        return null;
    }

    $data = json_decode($payload, true);
    return is_array($data) ? $data : null;
}

function clear_auth_cookie(): void
{
    setcookie(get_auth_cookie_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => isProduction(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function set_auth_session(array $user, string $accessToken, string $refreshToken): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'] ?? '';
    $_SESSION['user_email'] = $user['email'] ?? '';
    $_SESSION['user_name'] = $user['user_metadata']['full_name']
        ?? $user['user_metadata']['name']
        ?? ($user['email'] ?? '');
    $_SESSION['user_role'] = $user['user_metadata']['role'] ?? ($user['role'] ?? 'customer');
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['refresh_token'] = $refreshToken;

    save_auth_cookie($user, $accessToken, $refreshToken);
    regenerate_csrf_token();
}

function clear_auth_session(): void
{
    $_SESSION = [];
    clear_auth_cookie();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function supabase_http_request(string $method, string $url, array $headers = [], ?string $body = null): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'status' => $status,
            'body' => is_string($response) ? $response : '',
        ];
    }

    $headerLines = $headers;
    if ($body !== null) {
        $headerLines[] = 'Content-Length: ' . strlen($body);
    }

    $context = stream_context_create([
        'http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headerLines),
            'content' => $body ?? '',
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $status = (int) $m[0];
    }

    return [
        'status' => $status,
        'body' => is_string($response) ? $response : '',
    ];
}

function verify_supabase_token(string $accessToken): ?array
{
    if ($accessToken === '' || SUPABASE_URL === '') {
        return null;
    }

    $result = supabase_http_request('GET', SUPABASE_URL . '/auth/v1/user', [
        'Authorization: Bearer ' . $accessToken,
        'apikey: ' . SUPABASE_ANON_KEY,
    ]);

    if ($result['status'] !== 200 || $result['body'] === '') {
        return null;
    }

    $user = json_decode($result['body'], true);
    return is_array($user) ? $user : null;
}

function refresh_session_if_needed(): bool
{
    if (empty($_SESSION['access_token'])) {
        $cookieData = read_auth_cookie();
        if ($cookieData !== null && !empty($cookieData['access_token'])) {
            $_SESSION['user_id'] = $cookieData['id'] ?? '';
            $_SESSION['user_email'] = $cookieData['email'] ?? '';
            $_SESSION['user_name'] = $cookieData['name'] ?? '';
            $_SESSION['user_role'] = $cookieData['role'] ?? 'customer';
            $_SESSION['access_token'] = $cookieData['access_token'] ?? '';
            $_SESSION['refresh_token'] = $cookieData['refresh_token'] ?? '';
        }
    }

    if (empty($_SESSION['access_token'])) {
        return false;
    }

    $user = verify_supabase_token($_SESSION['access_token']);
    if ($user !== null) {
        $_SESSION['user_role'] = $user['user_metadata']['role'] ?? ($_SESSION['user_role'] ?? 'customer');
        if (!empty($user['user_metadata']['full_name'])) {
            $_SESSION['user_name'] = $user['user_metadata']['full_name'];
        }
        return true;
    }

    // Keep existing session if token verify fails transiently but session exists
    if (is_logged_in()) {
        return true;
    }

    if (empty($_SESSION['refresh_token'])) {
        return is_logged_in();
    }

    $result = supabase_http_request('POST', SUPABASE_URL . '/auth/v1/token?grant_type=refresh_token', [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_ANON_KEY,
    ], json_encode([
        'refresh_token' => $_SESSION['refresh_token'],
    ]));

    if ($result['status'] !== 200 || $result['body'] === '') {
        return is_logged_in();
    }

    $data = json_decode($result['body'], true);
    if (!is_array($data) || empty($data['access_token'])) {
        return is_logged_in();
    }

    $verifiedUser = verify_supabase_token($data['access_token']);
    if ($verifiedUser === null) {
        return is_logged_in();
    }

    set_auth_session(
        $verifiedUser,
        $data['access_token'],
        $data['refresh_token'] ?? $_SESSION['refresh_token']
    );

    return true;
}

function require_customer_auth(): void
{
    if (!refresh_session_if_needed()) {
        header('Location: ' . url('auth/login.php'));
        exit;
    }
}

function require_admin(): void
{
    if (!refresh_session_if_needed() || !is_admin()) {
        header('Location: ' . url(ADMIN_PATH . '/index.php'));
        exit;
    }
}

function redirect_if_admin_authenticated(): void
{
    if (refresh_session_if_needed() && is_admin()) {
        header('Location: ' . url(ADMIN_PATH . '/dashboard.php'));
        exit;
    }
}

function redirect_if_customer_authenticated(string $destination = 'index.php'): void
{
    if (refresh_session_if_needed() && is_logged_in()) {
        header('Location: ' . url($destination));
        exit;
    }
}

function supabase_public_config(): array
{
    return [
        'url' => SUPABASE_URL,
        'anonKey' => SUPABASE_ANON_KEY,
    ];
}

function supabase_service_request(string $method, string $endpoint, ?array $body = null, array $extraHeaders = []): ?array
{
    $key = !empty(SUPABASE_SERVICE_ROLE_KEY) ? SUPABASE_SERVICE_ROLE_KEY : SUPABASE_ANON_KEY;
    if ($key === '' || SUPABASE_URL === '') {
        return null;
    }

    $headers = array_merge([
        'Content-Type: application/json',
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Prefer: return=representation',
    ], $extraHeaders);

    $result = supabase_http_request(
        $method,
        SUPABASE_URL . $endpoint,
        $headers,
        $body !== null ? json_encode($body) : null
    );

    if ($result['status'] >= 400 || $result['body'] === '') {
        return null;
    }

    $decoded = json_decode($result['body'], true);
    return is_array($decoded) ? $decoded : ['raw' => $result['body']];
}
