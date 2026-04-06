<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Exception;

use InvalidArgumentException;

/**
 * Configuration or environment values failed validation (paths, secrets, .env).
 */
final class InvalidConfigurationException extends InvalidArgumentException
{
}
