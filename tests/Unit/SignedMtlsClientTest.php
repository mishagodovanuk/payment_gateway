<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\Exception\HttpResponseException;
use Mihod\PaymentGateway\Http\MtlsTransportInterface;
use Mihod\PaymentGateway\SignedMtlsClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignedMtlsClient::class)]
final class SignedMtlsClientTest extends TestCase
{
    private string $certFile;

    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->certFile = tempnam(sys_get_temp_dir(), 'mtls') ?: self::fail('tempnam');
        $this->keyFile = tempnam(sys_get_temp_dir(), 'mtls') ?: self::fail('tempnam');
        file_put_contents($this->certFile, '-----BEGIN CERTIFICATE-----');
        file_put_contents($this->keyFile, '-----BEGIN PRIVATE KEY-----');
    }

    protected function tearDown(): void
    {
        @unlink($this->certFile);
        @unlink($this->keyFile);

        parent::tearDown();
    }

    public function testSendSignedGetAddsSignatureHeaderAndAccepts2xx(): void
    {
        $expectedSig = hash_hmac(
            'sha256',
            'amount=99.99&currency=USD&transaction_id=12345',
            'secret',
            false
        );

        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->expects($this->once())
            ->method('sendGet')
            ->with(
                'https://example.test/api/check',
                [
                    'transaction_id' => '12345',
                    'amount' => '99.99',
                    'currency' => 'USD',
                ],
                ['X-Signature' => $expectedSig]
            )
            ->willReturn(new Response(200, ['Content-Type' => 'text/plain'], 'ok'));

        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'MTLS_CLIENT_KEY_PASSPHRASE' => '',
            'HMAC_SECRET' => 'secret',
            'MTLS_VERIFY_SSL' => 'true',
            'SIGNATURE_HEADER_NAME' => 'X-Signature',
        ]);

        $gateway = new SignedMtlsClient($config, transport: $transport);
        $response = $gateway->sendSignedGet('https://example.test/api/check', [
            'transaction_id' => '12345',
            'amount' => '99.99',
            'currency' => 'USD',
        ]);

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $response->body());
    }

    public function testSendSignedGetNormalizesMultiValueHeadersToArray(): void
    {
        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->method('sendGet')->willReturn(new Response(200, [
            'Content-Type' => ['application/json'],
            'Set-Cookie' => ['a=1', 'b=2'],
        ], '{}'));

        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
        ]);

        $gateway = new SignedMtlsClient($config, transport: $transport);
        $response = $gateway->sendSignedGet('https://example.test/', []);

        self::assertSame(['content-type' => 'application/json', 'set-cookie' => ['a=1', 'b=2']], $response->headers());
    }

    public function testFromEnvFileBuildsClientFromDotenvFile(): void
    {
        $envPath = tempnam(sys_get_temp_dir(), 'pgwenv') ?: self::fail('tempnam');
        $content = "MTLS_CLIENT_CERT={$this->certFile}\n";
        $content .= "MTLS_CLIENT_KEY={$this->keyFile}\n";
        $content .= "HMAC_SECRET=from-env\n";

        file_put_contents($envPath, $content);

        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->expects($this->once())->method('sendGet')->willReturn(new Response(200, [], 'done'));

        $client = SignedMtlsClient::fromEnvFile($envPath, $transport);
        $response = $client->sendSignedGet('https://example.test/', []);

        self::assertSame('done', $response->body());

        @unlink($envPath);
    }

    public function testNon2xxResponseThrows(): void
    {
        $transport = $this->createMock(MtlsTransportInterface::class);
        $transport->method('sendGet')->willReturn(new Response(500, [], 'error'));

        $config = ClientConfiguration::fromArray([
            'MTLS_CLIENT_CERT' => $this->certFile,
            'MTLS_CLIENT_KEY' => $this->keyFile,
            'HMAC_SECRET' => 'secret',
        ]);

        $gateway = new SignedMtlsClient($config, transport: $transport);

        $this->expectException(HttpResponseException::class);
        $gateway->sendSignedGet('https://example.test/', []);
    }
}
