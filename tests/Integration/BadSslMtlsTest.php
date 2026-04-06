<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Integration;

use GuzzleHttp\Exception\GuzzleException;
use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use Mihod\PaymentGateway\SignedMtlsClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

/**
 * Real network + mTLS against BadSSL. Requires valid paths in project .env.
 */
#[CoversClass(SignedMtlsClientFactory::class)]
#[Group('integration')]
#[RequiresPhpExtension('openssl')]
final class BadSslMtlsTest extends TestCase
{
    /**
     * @throws GuzzleException
     */
    public function testSignedGetAgainstClientBadSsl(): void
    {
        $envPath = dirname(__DIR__, 2) . '/.env';

        if (!is_readable($envPath)) {
            self::markTestSkipped('Create .env from .env.example with real certificate paths to run this test.');
        }

        try {
            $factory = new SignedMtlsClientFactory();
            $client = $factory->fromEnvFile($envPath);
        } catch (InvalidConfigurationException $e) {
            self::markTestSkipped('Invalid .env configuration: ' . $e->getMessage());
        }

        $response = $client->sendSignedGet('https://client.badssl.com/', [
            'transaction_id' => '12345',
            'amount' => '99.99',
            'currency' => 'USD',
        ]);

        self::assertGreaterThanOrEqual(200, $response->statusCode);
        self::assertLessThan(300, $response->statusCode);
        self::assertNotSame('', $response->body);
    }
}
