<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Dto;

/**
 * Result DTO for a successful (2xx) signed GET response.
 */
final class SignedHttpResponse
{
    /**
     * @param array<string, array<int, string>|string> $headers Lowercase header name => value(s)
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $body,
        private readonly array $headers
    ) {
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
