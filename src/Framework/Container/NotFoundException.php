<?php

declare(strict_types=1);

namespace Nitro\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * PSR-11 not-found exception.
 */
class NotFoundException extends BindingResolutionException implements NotFoundExceptionInterface
{
}
