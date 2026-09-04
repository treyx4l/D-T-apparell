<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

clear_auth_session();
session_start();
regenerate_csrf_token();

header('Location: ' . url('index.php'));
exit;
