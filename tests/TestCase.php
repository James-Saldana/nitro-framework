<?php

declare(strict_types=1);

namespace Tests;

use Nitro\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        Container::setInstance($this->container);
    }
}
