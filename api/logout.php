<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input) && verify_csrf($input['csrf_token'] ?? '')) {
        clear_auth_session();
        session_start();
        regenerate_csrf_token();
    }
    echo json_encode(['success' => true]);
    exit;
}

clear_auth_session();
session_start();
regenerate_csrf_token();

header('Location: ' . url('index.php'));
exit;
