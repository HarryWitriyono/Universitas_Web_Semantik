<?php
// Token CSRF untuk form yang mengubah keadaan aplikasi.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate(?string $token): bool
{
    return isset($_SESSION['csrf_token']) &&
        is_string($token) &&
        hash_equals($_SESSION['csrf_token'], $token);
}
