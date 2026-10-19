<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_role(string ...$allowedRoles): void
{
    require_login();

    $role = (string)($_SESSION['role'] ?? '');
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        exit('403 Forbidden: Anda tidak memiliki hak akses ke halaman ini.');
    }
}
