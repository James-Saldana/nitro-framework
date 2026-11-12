<?php

declare(strict_types=1);

namespace App\Controllers;

use Nitro\Http\Request;
use Nitro\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HomeController
{
    public function __construct(private readonly View $view)
    {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $name = Request::string($request, 'name', 'world');

        return $this->view->make('home', [
            'title' => 'Nitro Framework',
            'name' => $name,
        ]);
    }
}
