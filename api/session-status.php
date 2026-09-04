<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

echo json_encode([
    'loggedIn' => is_logged_in(),
    'email' => $_SESSION['user_email'] ?? null,
]);
