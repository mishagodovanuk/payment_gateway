<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Http;

use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\Http\GuzzleClientFactory;
use Mihod\PaymentGateway\Tests\DataProviders\GuzzleClientFactoryDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuzzleClientFactory::class)]
final class GuzzleClientFactoryTest extends TestCase
{
    private string $certFile;
    private string $keyFile;
    private string $caFile;

    protected function setUp(): void
    {
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
    }

    #[DataProviderExternal(GuzzleClientFactoryDataProvider::class, 'createClientSslOptionsCases')]
    public function testCreateClientAppliesSslOptions(array $overrides, array $expect): void
    {
        $defaults = [
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 's',
        ];

        $merged = array_merge($defaults, $overrides);

        if (($merged['MTLS_CA_BUNDLE'] ?? null) === GuzzleClientFactoryDataProvider::PLACEHOLDER_CA_FILE) {
            $merged['MTLS_CA_BUNDLE'] = $this->caFile;
        }

        $config = ClientConfiguration::fromArray($merged);
        $client = (new GuzzleClientFactory())->createClient($config);
        $cfg = $client->getConfig();

        $expectedVerify = $expect['verify'];
        if ($expectedVerify === GuzzleClientFactoryDataProvider::PLACEHOLDER_CA_FILE) {
            $expectedVerify = $this->caFile;
        }

        self::assertSame($expectedVerify, $cfg['verify']);

        $cert = $cfg['cert'];
        $key = $cfg['ssl_key'];

        if ($expect['certIsArray']) {
            self::assertIsArray($cert);
            self::assertIsArray($key);
            self::assertSame($this->certFile, $cert[0]);
            self::assertSame('secret-pass', $cert[1]);
            self::assertSame($this->keyFile, $key[0]);
            self::assertSame('secret-pass', $key[1]);
        } else {
            self::assertSame($this->certFile, $cert);
            self::assertSame($this->keyFile, $key);
        }
    }
}
