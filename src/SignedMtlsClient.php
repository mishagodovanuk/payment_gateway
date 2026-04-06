<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway;

use GuzzleHttp\Exception\GuzzleException;
use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\Dto\SignedHttpResponse;
use Mihod\PaymentGateway\Http\MtlsTransportInterface;
use Mihod\PaymentGateway\Signature\SignerInterface;

/**
 * Sends HMAC-signed GET requests over mutual TLS.
 */
final readonly class SignedMtlsClient
{
    public function __construct(
        private ClientConfiguration $config,
        private SignerInterface $signer,
        private MtlsTransportInterface $transport,
    ) {
    }

    /**
     * @param array<string, scalar|null> $query Flat query parameters for the canonical string and request.
     *
     * @throws GuzzleException when using Guzzle-backed transport and the HTTP layer fails
     */
    public function sendSignedGet(string $url, array $query): SignedHttpResponse
    {
        $signature = $this->signer->sign(
            $this->signer->canonicalQueryString($query),
            $this->config->hmacSharedSecret,
            $this->config->signatureHashAlgorithm,
        );

        $response = $this->transport->sendGet($url, $query, [
            $this->config->signatureHeaderName => $signature,
        ]);

        return new SignedHttpResponse(
            $response->getStatusCode(),
            (string) $response->getBody(),
            array_map(
                static fn(array $values): array|string => count($values) === 1 ? $values[0] : $values,
                array_change_key_case($response->getHeaders()),
            ),
        );
    }
}
