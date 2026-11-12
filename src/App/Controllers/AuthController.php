<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Nitro\Auth\AuthManager;
use Nitro\Http\Request;
use Nitro\Http\ResponseFactory;
use Nitro\Support\PasswordHasher;
use Nitro\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly ResponseFactory $responses,
        private readonly View $view
    ) {
    }

    public function showLogin(ServerRequestInterface $request): ResponseInterface
    {
        return $this->view->make('auth/login', ['title' => 'Sign in']);
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $credentials = [
            'email' => Request::string($request, 'email'),
            'password' => Request::string($request, 'password'),
        ];

        if (! $this->auth->attempt($credentials)) {
            return $this->view->make('auth/login', [
                'title' => 'Sign in',
                'error' => 'Invalid credentials.',
            ], 401);
        }

        return $this->responses->redirect('/users');
    }

    public function showRegister(ServerRequestInterface $request): ResponseInterface
    {
        return $this->view->make('auth/register', ['title' => 'Create account']);
    }

    public function register(ServerRequestInterface $request): ResponseInterface
    {
        $email = Request::string($request, 'email');
        $password = Request::string($request, 'password');

        if ($email === '' || strlen($password) < 8) {
            return $this->view->make('auth/register', [
                'title' => 'Create account',
                'error' => 'A valid email and a password of at least 8 characters are required.',
            ], 422);
        }

        User::query()->insert([
            'email' => $email,
            'password' => PasswordHasher::hash($password),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->auth->attempt(['email' => $email, 'password' => $password]);

        return $this->responses->redirect('/users');
    }

    public function logout(ServerRequestInterface $request): ResponseInterface
    {
        $this->auth->logout();

        return $this->responses->redirect('/');
    }
}
