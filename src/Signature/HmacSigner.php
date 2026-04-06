<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Signature;

use InvalidArgumentException;
use SensitiveParameter;

/**
 * HMAC over a canonical query string: sorted keys, RFC 3986 percent-encoding.
 */
final class HmacSigner implements SignerInterface
{
    /**
     * @throws InvalidArgumentException when a value is not scalar and not null
     */
    public function canonicalQueryString(array $parameters): string
    {
        $normalized = [];

        /** @var mixed $value */
        foreach ($parameters as $name => $value) {
            if ($value !== null && !is_scalar($value)) {
                throw new InvalidArgumentException(
                    sprintf('Query parameter "%s" must be scalar or null, %s given.', $name, get_debug_type($value))
                );
            }

            $normalized[$name] = (string) ($value ?? '');
        }

        ksort($normalized, SORT_STRING);

        return http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);
    }

    public function sign(string $payload, #[SensitiveParameter] string $secret, string $algorithm = 'sha256'): string
    {
        return hash_hmac($algorithm, $payload, $secret);
    }
}
