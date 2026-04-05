<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Http;

use GuzzleHttp\Client;
use Mihod\PaymentGateway\Config\ClientConfiguration;

/**
 * Create a Guzzle client with mTLS options. No base URI — each request supplies a full URL.
 */
final class GuzzleClientFactory
{
    public function createTransport(ClientConfiguration $config): MtlsTransportInterface
    {
        return new GuzzleMtlsTransport($this->createClient($config));
    }

    public function createClient(ClientConfiguration $config): Client
    {
        $cert = $config->clientCertificatePath();
        $key = $config->clientPrivateKeyPath();
        $pass = $config->clientPrivateKeyPassphrase();

        $certOption = $pass !== '' ? [$cert, $pass] : $cert;
        $keyOption = $pass !== '' ? [$key, $pass] : $key;

        $verify = $config->verifyServerCertificate();
        $caBundle = $config->caBundlePath();

        $verifyOption = false;

        if ($verify) {
            $verifyOption = $caBundle !== null && $caBundle !== '' ? $caBundle : true;
        }

        return new Client([
            'cert' => $certOption,
            'ssl_key' => $keyOption,
            'verify' => $verifyOption,
            'http_errors' => false,
            'timeout' => 30.0,
        ]);
    }
}
