<?php
declare(strict_types=1);

/** Escape output HTML untuk mencegah HTML injection/XSS pada konteks teks/atribut. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
