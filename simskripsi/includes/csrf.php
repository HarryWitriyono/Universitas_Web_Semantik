<?php
declare(strict_types=1);

/**
 * Token CSRF untuk formulir POST.
 * Sertakan setelah includes/session.php.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['_csrf_token'] ?? '';
    $stored = $_SESSION['_csrf_token'] ?? '';

    if (
        !is_string($sent) ||
        !is_string($stored) ||
        $sent === '' ||
        $stored === '' ||
        !hash_equals($stored, $sent)
    ) {
        http_response_code(403);
        exit('Permintaan ditolak: token CSRF tidak valid. Silakan muat ulang halaman dan coba kembali.');
    }
}

/**
 * Panggil pada handler POST sebelum memproses perubahan data.
 */
function csrf_verify_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        csrf_verify();
    }
}
