<?php

declare(strict_types=1);

class Session
{
    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }

        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $value;
    }

    // ── CSRF Protection ──────────────────────────────────────────

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        $stored = $_SESSION['_csrf_token'] ?? '';

        if ($stored === '' || $token === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public static function rotateCsrf(): void
    {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    // ── Old Input (namespaced per form) ──────────────────────────

    public static function setOld(array $input, string $form = '_default'): void
    {
        $_SESSION['_old'][$form] = $input;
    }

    public static function old(string $key, string $default = '', string $form = '_default'): string
    {
        return $_SESSION['_old'][$form][$key] ?? $default;
    }

    public static function clearOld(string $form = '_default'): void
    {
        if ($form === '_default') {
            unset($_SESSION['_old']);
        } else {
            unset($_SESSION['_old'][$form]);
        }
    }
}
