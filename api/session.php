<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

if (!verify_csrf($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token. Please refresh the page and try again.']);
    exit;
}

$accessToken = sanitize_input($input['access_token'] ?? '');
$refreshToken = sanitize_input($input['refresh_token'] ?? '');

if ($accessToken === '' || $refreshToken === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing tokens']);
    exit;
}

$user = verify_supabase_token($accessToken);
if ($user === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid access token']);
    exit;
}

$role = $user['user_metadata']['role'] ?? 'customer';

$profile = supabase_service_request('GET', '/rest/v1/profiles?id=eq.' . urlencode($user['id']) . '&select=role,full_name');
if (is_array($profile) && !empty($profile[0]['role'])) {
    $role = $profile[0]['role'];
    $user['user_metadata']['role'] = $role;
    if (!empty($profile[0]['full_name'])) {
        $user['user_metadata']['full_name'] = $profile[0]['full_name'];
    }
}

$user['role'] = $role;

$providers = array_column($user['identities'] ?? [], 'provider');
$isGoogleAuth = in_array('google', $providers, true)
    || (($user['app_metadata']['provider'] ?? '') === 'google');

$isAdminLogin = !empty($input['admin_login']);
$emailConfirmed = !empty($user['email_confirmed_at'])
    || !empty($user['confirmed_at'])
    || $isGoogleAuth;

if ($role !== 'admin' && !$emailConfirmed) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Please verify your email. Check your inbox and spam folder, then sign in again.',
    ]);
    exit;
}

if ($isAdminLogin && $role !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. Admin privileges required.',
    ]);
    exit;
}

set_auth_session($user, $accessToken, $refreshToken);

echo json_encode([
    'success' => true,
    'role' => $role,
    'email' => $user['email'] ?? '',
]);
