<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\DataProviders;

/**
 * Datasets for {@see \Mihod\PaymentGateway\Tests\Unit\Http\GuzzleClientFactoryTest}.
 */
final class GuzzleClientFactoryDataProvider
{
    public const PLACEHOLDER_CA_FILE = '__CA_FILE__';

    /**
     * @return iterable<string, array{
     *     0: array<string, string>,
     *     1: array{verify: bool|string, certIsArray: bool, keyIsArray: bool}
     * }>
     */
    public static function createClientSslOptionsCases(): iterable
    {
        yield 'verify disabled' => [
            [
                'MTLS_VERIFY_SSL' => 'false',
            ],
            [
                'verify' => false,
                'certIsArray' => false,
                'keyIsArray' => false,
            ],
        ];

        yield 'custom ca bundle when verify enabled' => [
            [
                'MTLS_VERIFY_SSL' => 'true',
                'MTLS_CA_BUNDLE' => self::PLACEHOLDER_CA_FILE,
            ],
            [
                'verify' => self::PLACEHOLDER_CA_FILE,
                'certIsArray' => false,
                'keyIsArray' => false,
            ],
        ];

        yield 'passphrase wraps cert and key as tuples' => [
            [
                'MTLS_CLIENT_KEY_PASSPHRASE' => 'secret-pass',
            ],
            [
                'verify' => true,
                'certIsArray' => true,
                'keyIsArray' => true,
            ],
        ];
    }
}
