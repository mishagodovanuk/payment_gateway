<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Config;

use Mihod\PaymentGateway\Exception\InvalidConfigurationException;

/**
 * HTTP client behavior (timeouts, http_errors).
 */
final readonly class HttpClientConfiguration
{
    public function __construct(
        public bool $httpErrors = true,
        public float $timeoutSeconds = 30.0,
        public float $connectTimeoutSeconds = 10.0,
    ) {
        self::isPositiveNumber($timeoutSeconds, 'HTTP timeout');
        self::isPositiveNumber($connectTimeoutSeconds, 'HTTP connect timeout');
    }

    /**
     * @param array{
     *   HTTP_ERRORS?: string,
     *   HTTP_TIMEOUT_SECONDS?: string,
     *   HTTP_CONNECT_TIMEOUT_SECONDS?: string
     * } $env
     */
    public static function fromArray(array $env): self
    {
        $httpErrorsRaw = trim($env['HTTP_ERRORS'] ?? '');
        $timeoutRaw = trim($env['HTTP_TIMEOUT_SECONDS'] ?? '');
        $connectTimeoutRaw = trim($env['HTTP_CONNECT_TIMEOUT_SECONDS'] ?? '');

        $httpErrors = $httpErrorsRaw !== ''
            ? filter_var($httpErrorsRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
            : null;

        $timeoutSeconds = $timeoutRaw !== '' ? (float) $timeoutRaw : 30.0;
        $connectTimeoutSeconds = $connectTimeoutRaw !== '' ? (float) $connectTimeoutRaw : 10.0;

        return new self(
            httpErrors: $httpErrors ?? true,
            timeoutSeconds: $timeoutSeconds,
            connectTimeoutSeconds: $connectTimeoutSeconds,
        );
    }

    private static function isPositiveNumber(float $value, string $label): void
    {
        if (!is_finite($value) || $value <= 0.0) {
            throw new InvalidConfigurationException("{$label} must be a positive number.");
        }
    }
}
