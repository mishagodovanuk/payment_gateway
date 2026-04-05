<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Signature;

use InvalidArgumentException;

/**
 * HMAC over a stable canonical representation of query parameters: sorted keys, RFC 3986 encoding.
 */
final class HmacSigner implements SignerInterface
{
    /**
     * @param array<string, mixed> $parameters key/value pairs (string keys)
     *
     * @throws InvalidArgumentException for nested arrays
     */
    public function canonicalQueryString(array $parameters): string
    {
        $flat = [];

        foreach ($parameters as $name => $value) {
            // @phpstan-ignore-next-line function.impossibleType — keys may be non-string at runtime (PHP arrays)
            if (! is_string($name)) {
                throw new InvalidArgumentException('Query parameter names must be strings.');
            }

            if (is_array($value)) {
                throw new InvalidArgumentException(
                    'Query parameters must be scalar; nested arrays are not supported.'
                );
            }

            $flat[$name] = $value === null ? '' : (string) $value;
        }

        ksort($flat, SORT_STRING);

        return http_build_query($flat, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Returns lowercase hexadecimal digest (used for X-Signature headers).
     */
    public function sign(string $payload, string $secret, string $algorithm = 'sha256'): string
    {
        return hash_hmac($algorithm, $payload, $secret, false);
    }
}
