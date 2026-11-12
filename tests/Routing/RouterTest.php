<?php

declare(strict_types=1);

namespace Tests\Routing;

use GuzzleHttp\Psr7\ServerRequest;
use Nitro\Container\Container;
use Nitro\Exceptions\MethodNotAllowedException;
use Nitro\Exceptions\NotFoundException;
use Nitro\Http\ResponseFactory;
use Nitro\Routing\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RouterTest extends TestCase
{
    private Container $container;

    private Router $router;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    public function testMatchesExactRoute(): void
    {
        $this->router->get('/ping', static fn (): ResponseInterface =>
            (new ResponseFactory())->text('pong'));

        $match = $this->router->match(new ServerRequest('GET', '/ping'));

        self::assertSame('/ping', $match['route']->pattern());
        self::assertSame([], $match['parameters']);
    }

    public function testExtractsParametersWithConstraints(): void
    {
        $this->router->get('/users/{id:\d+}', static fn (): ResponseInterface =>
            (new ResponseFactory())->text('ok'));

        $match = $this->router->match(new ServerRequest('GET', '/users/42'));

        self::assertSame(['id' => '42'], $match['parameters']);
    }

    public function testConstraintRejectsNonMatchingSegments(): void
    {
        $this->router->get('/users/{id:\d+}', static fn (): ResponseInterface =>
            (new ResponseFactory())->text('ok'));

        $this->expectException(NotFoundException::class);

        $this->router->match(new ServerRequest('GET', '/users/not-a-number'));
    }

    public function testThrowsMethodNotAllowed(): void
    {
        $this->router->get('/only-get', static fn (): ResponseInterface =>
            (new ResponseFactory())->text('ok'));

        $this->expectException(MethodNotAllowedException::class);

        $this->router->match(new ServerRequest('POST', '/only-get'));
    }

    public function testGroupPrefixAndMiddleware(): void
    {
        $this->router->group(['prefix' => '/admin', 'middleware' => ['FakeMiddleware']], static function (Router $r): void {
            $r->get('/stats', static fn (): ResponseInterface =>
                (new ResponseFactory())->text('ok'));
        });

        $match = $this->router->match(new ServerRequest('GET', '/admin/stats'));

        self::assertSame(['FakeMiddleware'], $match['route']->getMiddleware());
    }

    public function testNamedRouteUrlGeneration(): void
    {
        $this->router->get('/users/{id}', static fn (): ResponseInterface =>
            (new ResponseFactory())->text('ok'))->name('users.show');

        self::assertSame('/users/7', $this->router->url('users.show', ['id' => 7]));
    }

    public function testResourceRegistersRestfulRoutes(): void
    {
        $this->router->resource('/posts', 'App\Controllers\PostController');

        $get = $this->router->match(new ServerRequest('GET', '/posts/3'));
        $put = $this->router->match(new ServerRequest('PUT', '/posts/3'));

        self::assertSame(['id' => '3'], $get['parameters']);
        self::assertSame(['id' => '3'], $put['parameters']);
    }
}
