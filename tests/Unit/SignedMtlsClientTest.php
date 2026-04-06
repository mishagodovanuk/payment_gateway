<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\Http\MtlsTransportInterface;
use Mihod\PaymentGateway\Signature\HmacSigner;
use Mihod\PaymentGateway\SignedMtlsClient;
use Mihod\PaymentGateway\SignedMtlsClientFactory;
use Mihod\PaymentGateway\Tests\DataProviders\SignedMtlsClientDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignedMtlsClient::class)]
#[CoversClass(SignedMtlsClientFactory::class)]
final class SignedMtlsClientTest extends TestCase
{
    private string $certFile;
    private string $keyFile;

    protected function setUp(): void
    {
        $this->certFile = tempnam(sys_get_temp_dir(), 'mtls') ?: self::fail('tempnam');
        $this->keyFile = tempnam(sys_get_temp_dir(), 'mtls') ?: self::fail('tempnam');
        file_put_contents($this->certFile, '-----BEGIN CERTIFICATE-----');
        file_put_contents($this->keyFile, '-----BEGIN PRIVATE KEY-----');
    }

    protected function tearDown(): void
    {
        @unlink($this->certFile);
        @unlink($this->keyFile);
    }

    #[DataProviderExternal(SignedMtlsClientDataProvider::class, 'signedGetSignatureCases')]
    public function testSendSignedGetAddsSignatureHeader(string $algorithm, array $query, string $secret): void
    {
        $expectedSig = hash_hmac($algorithm, 'amount=99.99&currency=USD&transaction_id=12345', $secret);

        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->expects($this->once())
            ->method('sendGet')
            ->with(
                'https://example.test/api/check',
                $query,
                ['X-Signature' => $expectedSig],
            )
            ->willReturn(new Response(200, ['Content-Type' => 'text/plain'], 'ok'));

        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => $secret,
        ]);

        $client = new SignedMtlsClient($config, new HmacSigner(), $transport);
        $response = $client->sendSignedGet('https://example.test/api/check', $query);

        self::assertSame(200, $response->statusCode);
        self::assertSame('ok', $response->body);
    }

    #[DataProviderExternal(SignedMtlsClientDataProvider::class, 'headerNormalizationCases')]
    public function testNormalizesMultiValueHeaders(array $rawHeaders, array $expectedHeaders): void
    {
        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->method('sendGet')->willReturn(new Response(200, $rawHeaders, '{}'));

        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
        ]);

        $client = new SignedMtlsClient($config, new HmacSigner(), $transport);
        $response = $client->sendSignedGet('https://example.test/', []);

        self::assertSame($expectedHeaders, $response->headers);
    }

    public function testFactoryFromEnvFile(): void
    {
        $envPath = tempnam(sys_get_temp_dir(), 'pgwenv') ?: self::fail('tempnam');
        file_put_contents($envPath, implode("\n", [
            "MTLS_CLIENT_CERT={$this->certFile}",
            "MTLS_CLIENT_KEY={$this->keyFile}",
            "HMAC_SECRET=from-env",
        ]));

        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->expects($this->once())->method('sendGet')->willReturn(new Response(200, [], 'done'));

        $factory = new SignedMtlsClientFactory();
        $client = $factory->fromEnvFile($envPath, $transport);
        $response = $client->sendSignedGet('https://example.test/', []);

        self::assertSame('done', $response->body);

        @unlink($envPath);
    }

    public function testTransportExceptionPropagates(): void
    {
        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->method('sendGet')->willThrowException(
            new RequestException('Server error', new Request('GET', '/'), new Response(500))
        );

        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
        ]);

        $client = new SignedMtlsClient($config, new HmacSigner(), $transport);

        $this->expectException(RequestException::class);
        $client->sendSignedGet('https://example.test/', []);
    }
}
