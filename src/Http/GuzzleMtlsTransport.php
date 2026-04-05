<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle-backed {@see MtlsTransportInterface}. Built via {@see GuzzleClientFactory}.
 */
final class GuzzleMtlsTransport implements MtlsTransportInterface
{
    public function __construct(
        private readonly Client $client
    ) {
    }

    /**
     * @throws GuzzleException
     */
    public function sendGet(string $url, array $query, array $headers): ResponseInterface
    {
        return $this->client->get($url, [
            'query' => $query,
            'headers' => $headers,
        ]);
    }
}
