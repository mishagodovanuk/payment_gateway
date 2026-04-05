<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Config;

use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use Mihod\PaymentGateway\Tests\DataProviders\ClientConfigurationDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientConfiguration::class)]
final class ClientConfigurationTest extends TestCase
{
    private string $certFile;

    private string $keyFile;

    private string $caFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->certFile = tempnam(sys_get_temp_dir(), 'mtls') ?: self::fail('tempnam');
        $this->keyFile = tempnam(sys_get_temp_dir(), 'mtls') ?: self::fail('tempnam');
        $this->caFile = tempnam(sys_get_temp_dir(), 'ca') ?: self::fail('tempnam');
        file_put_contents($this->certFile, '-----BEGIN CERTIFICATE-----');
        file_put_contents($this->keyFile, '-----BEGIN PRIVATE KEY-----');
        file_put_contents($this->caFile, '-----BEGIN CERTIFICATE-----');
    }

    protected function tearDown(): void
    {
        @unlink($this->certFile);
        @unlink($this->keyFile);
        @unlink($this->caFile);

        parent::tearDown();
    }

    public function testFromArrayBuildsConfigurationAndGettersReturnValues(): void
    {
        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'MTLS_CLIENT_KEY_PASSPHRASE' => 'pk-pass',
            'HMAC_SECRET' => 'hmac',
            'MTLS_VERIFY_SSL' => 'false',
            'MTLS_CA_BUNDLE' => $this->caFile,
            'SIGNATURE_HEADER_NAME' => 'X-Custom',
            'SIGNATURE_HASH_ALGO' => 'sha384',
            99 => 'ignored-non-string-key-handled-in-onlyStringKeys',
            1 => 'ignored-int-key',
        ]);

        self::assertSame($this->certFile, $config->clientCertificatePath());
        self::assertSame($this->keyFile, $config->clientPrivateKeyPath());
        self::assertSame('pk-pass', $config->clientPrivateKeyPassphrase());
        self::assertSame('hmac', $config->hmacSharedSecret());
        self::assertFalse($config->verifyServerCertificate());
        self::assertSame($this->caFile, $config->caBundlePath());
        self::assertSame('X-Custom', $config->signatureHeaderName());
        self::assertSame('sha384', $config->signatureHashAlgorithm());
    }

    public function testFromArrayUsesDefaultsWhenKeysMissing(): void
    {
        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
        ]);

        self::assertTrue($config->verifyServerCertificate());
        self::assertNull($config->caBundlePath());
        self::assertSame('X-Signature', $config->signatureHeaderName());
        self::assertSame('sha256', $config->signatureHashAlgorithm());
        self::assertSame('', $config->clientPrivateKeyPassphrase());
    }

    public function testFromArrayBoolFallsBackToDefaultWhenValueIsNotABooleanString(): void
    {
        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
            'MTLS_VERIFY_SSL' => 'not-a-boolean',
        ]);

        self::assertTrue($config->verifyServerCertificate());
    }

    public function testFromArrayEmptyCaStringBecomesNull(): void
    {
        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
            'MTLS_CA_BUNDLE' => '',
        ]);

        self::assertNull($config->caBundlePath());
    }

    #[DataProviderExternal(ClientConfigurationDataProvider::class, 'invalidFromArrayCases')]
    public function testFromArrayThrowsInvalidConfiguration(string $messageSubstring, array $overrides): void
    {
        $base = [
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($messageSubstring);

        ClientConfiguration::fromArray(array_merge($base, $overrides));
    }

    public function testNullOptionalStringKeysUseDefaults(): void
    {
        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
            'MTLS_CLIENT_KEY_PASSPHRASE' => null,
            'SIGNATURE_HEADER_NAME' => null,
            'SIGNATURE_HASH_ALGO' => null,
            'MTLS_VERIFY_SSL' => null,
        ]);

        self::assertSame('', $config->clientPrivateKeyPassphrase());
        self::assertSame('X-Signature', $config->signatureHeaderName());
        self::assertSame('sha256', $config->signatureHashAlgorithm());
        self::assertTrue($config->verifyServerCertificate());
    }

    public function testEmptyStringVerifySslUsesDefault(): void
    {
        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
            'MTLS_VERIFY_SSL' => '',
        ]);

        self::assertTrue($config->verifyServerCertificate());
    }
}
