<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Exception;

use InvalidArgumentException;
use Throwable;

final class InvalidConfigurationException extends InvalidArgumentException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
