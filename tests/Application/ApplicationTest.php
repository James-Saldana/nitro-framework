<?php

declare(strict_types=1);

namespace Tests\Application;

use GuzzleHttp\Psr7\ServerRequest;
use Nitro\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application(dirname(__DIR__, 2));
    }

    public function testBootsAndResolvesCoreServices(): void
    {
        $this->app->boot();

        self::assertTrue($this->app->isBooted());
        self::assertTrue($this->app->container()->has('config'));
        self::assertTrue($this->app->container()->has('router'));
        self::assertTrue($this->app->container()->has('db'));
        self::assertTrue($this->app->container()->has('view'));
    }

    public function testHandlesRequestAndReturnsResponse(): void
    {
        $response = $this->app->handle(new ServerRequest('GET', '/api/ping'));

        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        self::assertTrue($payload['pong']);
    }

    public function testUnknownRouteYields404(): void
    {
        $response = $this->app->handle(new ServerRequest('GET', '/api/definitely-missing'));

        self::assertSame(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        self::assertSame(404, $payload['error']['status']);
    }

    public function testProtectedRouteRejectsGuests(): void
    {
        $response = $this->app->handle(new ServerRequest('GET', '/users'));

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
    }
}
