<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Transport that performs GET over mutual TLS.
 */
interface MtlsTransportInterface
{
    /**
     * @param array<string, scalar|null> $query Query string parameters
     * @param array<string, string>      $headers Extra headers (e.g. signature)
     */
    public function sendGet(string $url, array $query, array $headers): ResponseInterface;
}
