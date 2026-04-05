<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway;

use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\Config\EnvironmentLoader;
use Mihod\PaymentGateway\Dto\SignedHttpResponse;
use Mihod\PaymentGateway\Exception\HttpResponseException;
use Mihod\PaymentGateway\Http\GuzzleClientFactory;
use Mihod\PaymentGateway\Http\MtlsTransportInterface;
use Mihod\PaymentGateway\Signature\HmacSigner;
use Mihod\PaymentGateway\Signature\SignerInterface;

/**
 * Sends HMAC-signed GET requests over mutual TLS. Depends on {@see SignerInterface} and
 * {@see MtlsTransportInterface}.
 */
final class SignedMtlsClient
{
    private readonly SignerInterface $signer;

    private readonly MtlsTransportInterface $transport;

    public function __construct(
        private readonly ClientConfiguration $configuration,
        ?SignerInterface $signer = null,
        ?MtlsTransportInterface $transport = null,
        ?GuzzleClientFactory $clientFactory = null
    ) {
        $this->signer = $signer ?? new HmacSigner();
        $factory = $clientFactory ?? new GuzzleClientFactory();
        $this->transport = $transport ?? $factory->createTransport($configuration);
    }

    /**
     * Factory: reads configuration from a .env file path parsed, not shell-exported.
     */
    public static function fromEnvFile(string $pathToDotenv, ?MtlsTransportInterface $transport = null): self
    {
        $loader = new EnvironmentLoader($pathToDotenv);

        return new self($loader->loadConfiguration(), null, $transport);
    }

    /**
     * @param array<string, scalar|null> $query One-dimensional associative array (e.g. transaction_id, amount)
     *
     * @throws HttpResponseException when status code is not 2xx
     */
    public function sendSignedGet(string $url, array $query): SignedHttpResponse
    {
        $canonical = $this->signer->canonicalQueryString($query);
        $signature = $this->signer->sign(
            $canonical,
            $this->configuration->hmacSharedSecret(),
            $this->configuration->signatureHashAlgorithm()
        );

        $headerName = $this->configuration->signatureHeaderName();

        $response = $this->transport->sendGet($url, $query, [
            $headerName => $signature,
        ]);

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status < 200 || $status > 299) {
            throw new HttpResponseException(
                sprintf('HTTP request failed with status %d.', $status),
                $status,
                $body
            );
        }

        return new SignedHttpResponse($status, $body, $this->normalizeHeaders($response->getHeaders()));
    }

    /**
     * @param array<string, array<int, string>> $headers
     *
     * @return array<string, array<int, string>|string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $out = [];

        foreach ($headers as $name => $values) {
            $out[strtolower($name)] = count($values) === 1 ? $values[0] : $values;
        }

        return $out;
    }
}
