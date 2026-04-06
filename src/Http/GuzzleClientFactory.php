<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Http;

use GuzzleHttp\Client;
use Mihod\PaymentGateway\Config\ClientConfiguration;

/**
 * Builds a Guzzle {@see Client} with client certificate (mTLS) and verify options.
 */
final readonly class GuzzleClientFactory
{
    public function createClient(ClientConfiguration $config): Client
    {
        $privateKeyPassphrase = $config->clientPrivateKeyPassphrase;
        $tlsCredential = static fn(string $path): array|string => $privateKeyPassphrase !== ''
            ? [$path, $privateKeyPassphrase]
            : $path;

        return new Client([
            'cert' => $tlsCredential($config->clientCertificatePath),
            'ssl_key' => $tlsCredential($config->clientPrivateKeyPath),
            'verify' => match (true) {
                !$config->verifyServerCertificate => false,
                $config->caBundlePath !== null => $config->caBundlePath,
                default => true,
            },
            'http_errors' => $config->http->httpErrors,
            'timeout' => $config->http->timeoutSeconds,
            'connect_timeout' => $config->http->connectTimeoutSeconds,
        ]);
    }
}
