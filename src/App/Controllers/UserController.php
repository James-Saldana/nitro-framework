<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Nitro\Http\ResponseFactory;
use Nitro\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserController
{
    public function __construct(
        private readonly View $view,
        private readonly ResponseFactory $responses
    ) {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $users = User::query()->orderBy('id')->limit(50)->get();

        return $this->view->make('users/index', [
            'title' => 'Users',
            'users' => $users,
        ]);
    }

    public function show(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $user = User::query()->where('id', (int) $id)->first();

        if ($user === null) {
            return $this->responses->json(['message' => 'User not found.'], 404);
        }

        return $this->responses->json($user->toArray());
    }
}
