<?php

declare(strict_types=1);

namespace Nitro\Auth;

use Nitro\Events\Dispatcher;
use Nitro\Events\UserLoggedIn;
use Nitro\Events\UserLoggedOut;

/**
 * Session-based authentication guard coordinating the user provider.
 */
final class AuthManager
{
    private const SESSION_KEY = 'auth.user_id';

    private ?Authenticatable $user = null;

    public function __construct(
        private readonly Session $session,
        private readonly UserProvider $provider,
        private readonly Dispatcher $events
    ) {
        $this->session->start();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->session->get(self::SESSION_KEY);

        if ($id === null) {
            return null;
        }

        return $this->user = $this->provider->retrieveById($id);
    }

    public function id(): string|int|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * Attempt to authenticate with the given credentials.
     *
     * @param array{email?: string, username?: string, password: string} $credentials
     */
    public function attempt(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null || ! $this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function login(Authenticatable $user): void
    {
        $this->session->regenerate();
        $this->session->put(self::SESSION_KEY, $user->getAuthIdentifier());
        $this->user = $user;

        $this->events->dispatch(new UserLoggedIn($user->getAuthIdentifier()));
    }

    public function loginUsingId(string|int $id): bool
    {
        $user = $this->provider->retrieveById($id);

        if ($user === null) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function logout(): void
    {
        $id = $this->id();

        $this->session->invalidate();
        $this->session->remove(self::SESSION_KEY);
        $this->user = null;

        if ($id !== null) {
            $this->events->dispatch(new UserLoggedOut($id));
        }
    }
}
