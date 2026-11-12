<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\UserController;
use Nitro\Http\ResponseFactory;
use Nitro\Middleware\AuthMiddleware;
use Nitro\Middleware\GuestMiddleware;
use Nitro\Routing\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index'])->name('home');

    $router->group(['prefix' => '/auth', 'middleware' => GuestMiddleware::class], static function (Router $r): void {
        $r->get('/login', [AuthController::class, 'showLogin'])->name('login');
        $r->post('/login', [AuthController::class, 'login']);
        $r->get('/register', [AuthController::class, 'showRegister'])->name('register');
        $r->post('/register', [AuthController::class, 'register']);
    });

    $router->post('/auth/logout', [AuthController::class, 'logout'])->middleware(AuthMiddleware::class);

    $router->group(['prefix' => '/users', 'middleware' => AuthMiddleware::class], static function (Router $r): void {
        $r->get('', [UserController::class, 'index'])->name('users.index');
        $r->get('/{id:\d+}', [UserController::class, 'show'])->name('users.show');
    });

    $router->group(['prefix' => '/api'], static function (Router $r): void {
        $r->get('/ping', static fn (): \Psr\Http\Message\ResponseInterface =>
            (new ResponseFactory())->json(['pong' => true, 'time' => time()]));
    });
};
