<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\DataProviders;

/**
 * Datasets for {@see \Mihod\PaymentGateway\Tests\Unit\Config\ClientConfigurationTest} (invalid fromArray).
 */
final class ClientConfigurationDataProvider
{
    private static function missingPath(): string
    {
        return '/no/such/file-' . bin2hex(random_bytes(8));
    }

    /**
     * @return iterable<string, array{0: non-empty-string, 1: array<string, mixed>}>
     */
    public static function invalidFromArrayCases(): iterable
    {
        yield 'empty hmac secret' => [
            'HMAC shared secret',
            ['HMAC_SECRET' => ''],
        ];

        yield 'empty signature hash algorithm' => [
            'SIGNATURE_HASH_ALGO',
            ['SIGNATURE_HASH_ALGO' => ''],
        ];

        yield 'non-scalar certificate entry' => [
            'client certificate',
            ['MTLS_CLIENT_CERT' => ['not-a-file']],
        ];

        yield 'unreadable certificate path' => [
            'client certificate',
            ['MTLS_CLIENT_CERT' => self::missingPath()],
        ];

        yield 'unreadable private key path' => [
            'client private key',
            ['MTLS_CLIENT_KEY' => self::missingPath()],
        ];

        yield 'unreadable ca bundle path' => [
            'CA bundle',
            [
                'MTLS_CA_BUNDLE' => self::missingPath(),
            ],
        ];
    }
}
