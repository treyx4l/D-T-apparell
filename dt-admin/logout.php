<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

clear_auth_session();
session_start();
regenerate_csrf_token();

header('Location: ' . url(ADMIN_PATH . '/index.php'));
exit;
