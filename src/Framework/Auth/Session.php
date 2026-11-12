<?php

declare(strict_types=1);

namespace Nitro\Auth;

/**
 * Session manager abstracting PHP's native session handling with
 * strict-mode cookies, regeneration and flash messages.
 */
final class Session
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;

            return;
        }

        if (headers_sent()) {
            throw new \RuntimeException('Cannot start the session after headers were sent.');
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (($_SERVER['HTTPS'] ?? '') !== ''),
        ]);

        ini_set('session.use_strict_mode', '1');

        session_start();
        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started || session_status() === PHP_SESSION_ACTIVE;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertStarted();

        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->assertStarted();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->assertStarted();

        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->assertStarted();
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->put('_flash.' . $key, $value);
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->get('_flash.' . $key, $default);
        $this->remove('_flash.' . $key);

        return $value;
    }

    public function regenerate(): void
    {
        $this->assertStarted();
        session_regenerate_id(true);
    }

    public function invalidate(): void
    {
        $this->assertStarted();
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        if (! $this->isStarted()) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies') !== false) {
            setcookie(session_name(), '', time() - 42000, '/');
        }

        session_destroy();
        $this->started = false;
    }

    private function assertStarted(): void
    {
        if (! $this->isStarted()) {
            throw new \RuntimeException('The session has not been started; call start() first.');
        }
    }
}
