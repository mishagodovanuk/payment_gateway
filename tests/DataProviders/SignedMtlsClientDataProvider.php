<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\DataProviders;

/**
 * Shared datasets for {@see \Mihod\PaymentGateway\Tests\Unit\SignedMtlsClientTest}.
 */
final class SignedMtlsClientDataProvider
{
    /**
     * @return iterable<string, array{
     *     0: string,
     *     1: array<string, scalar|null>,
     *     2: string
     * }>
     */
    public static function signedGetSignatureCases(): iterable
    {
        yield 'canonical payload sha256 signature' => [
            'sha256',
            [
                'transaction_id' => '12345',
                'amount' => '99.99',
                'currency' => 'USD',
            ],
            'secret',
        ];
    }

    /**
     * @return iterable<string, array{
     *     0: array<string, array<int, string>>,
     *     1: array<string, array<int, string>|string>
     * }>
     */
    public static function headerNormalizationCases(): iterable
    {
        yield 'single header becomes string; multi stays list' => [
            [
                'Content-Type' => ['application/json'],
                'Set-Cookie' => ['a=1', 'b=2'],
            ],
            [
                'content-type' => 'application/json',
                'set-cookie' => ['a=1', 'b=2'],
            ],
        ];
    }
}

