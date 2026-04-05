<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Signature;

/**
 * Request signing: canonical query string + HMAC (or another MAC) over that string.
 */
interface SignerInterface
{
    /**
     * @param array<string, mixed> $parameters query parameters (string keys)
     *
     * @throws \InvalidArgumentException when parameters are invalid for signing
     */
    public function canonicalQueryString(array $parameters): string;

    /**
     * Produces a string suitable for the configured HTTP header (typically lowercase hex).
     */
    public function sign(string $payload, string $secret, string $algorithm = 'sha256'): string;
}
