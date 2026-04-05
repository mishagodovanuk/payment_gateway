<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\DataProviders;

/**
 * Shared datasets for {@see \Mihod\PaymentGateway\Tests\Unit\HmacSignerTest}
 * via {@see \PHPUnit\Framework\Attributes\DataProviderExternal}.
 */
final class HmacSignerDataProvider
{
    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function canonicalQueryStringCases(): iterable
    {
        yield 'sorted keys and RFC 3986 encoding' => [
            [
                'currency' => 'USD',
                'amount' => '99.99',
                'transaction_id' => '12345',
            ],
            'amount=99.99&currency=USD&transaction_id=12345',
        ];

        yield 'empty query' => [
            [],
            '',
        ];

        yield 'single parameter' => [
            ['x' => 'y'],
            'x=y',
        ];
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string, 3: non-empty-string}>
     */
    public static function signProducesHexDigestCases(): iterable
    {
        $payload = 'amount=99.99&currency=USD&transaction_id=12345';
        $secret = 'test-secret';

        yield 'sha256 matches hash_hmac' => [
            $payload,
            $secret,
            'sha256',
            hash_hmac('sha256', $payload, $secret, false),
        ];
    }
}
