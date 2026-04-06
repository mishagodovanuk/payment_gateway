<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Dto;

/**
 * Result DTO for a successful (2xx) signed GET response.
 */
final readonly class SignedHttpResponse
{
    /**
     * @param array<string, array<int, string>|string> $headers Lowercase header name => value(s)
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers,
    ) {
    }
}
