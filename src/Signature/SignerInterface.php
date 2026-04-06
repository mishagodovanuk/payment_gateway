<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Signature;

use InvalidArgumentException;
use SensitiveParameter;

/**
 * Canonical query string + MAC (e.g. HMAC) for request signing.
 */
interface SignerInterface
{
    /**
     * @param array<string, scalar|null> $parameters Flat query parameters (string keys).
     *
     * @throws InvalidArgumentException when a value is not scalar and not null
     */
    public function canonicalQueryString(array $parameters): string;

    /** Algorithm name for {@see hash_hmac()} (e.g. sha256). */
    public function sign(string $payload, #[SensitiveParameter] string $secret, string $algorithm = 'sha256'): string;
}
