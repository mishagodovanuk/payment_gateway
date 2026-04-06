<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Http;

use GuzzleHttp\Client;
use Mihod\PaymentGateway\Http\GuzzleMtlsTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(GuzzleMtlsTransport::class)]
final class GuzzleMtlsTransportTest extends TestCase
{
    public function testSendGetDelegatesToGuzzleClient(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->with('https://example.test/', [
                'query' => ['a' => '1'],
                'headers' => ['X-Signature' => 'sig'],
            ])
            ->willReturn($response);

        $transport = new GuzzleMtlsTransport($client);
        self::assertSame($response, $transport->sendGet('https://example.test/', ['a' => '1'], ['X-Signature' => 'sig']));
    }
}

