<?php

declare(strict_types=1);

namespace Nitro\Container;

use Exception;

/**
 * Thrown when the container cannot resolve a dependency.
 */
class BindingResolutionException extends Exception
{
}
