<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway;

use Mihod\PaymentGateway\Config\ClientConfiguration;
use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use Mihod\PaymentGateway\Http\GuzzleClientFactory;
use Mihod\PaymentGateway\Http\GuzzleMtlsTransport;
use Mihod\PaymentGateway\Http\MtlsTransportInterface;
use Mihod\PaymentGateway\Signature\HmacSigner;
use Mihod\PaymentGateway\Signature\SignerInterface;

/**
 * Factory for creating SignedMtlsClient with default implementations.
 */
final readonly class SignedMtlsClientFactory
{
    public function __construct(
        private GuzzleClientFactory $guzzleFactory = new GuzzleClientFactory(),
        private SignerInterface $signer = new HmacSigner(),
    ) {
    }

    public function create(ClientConfiguration $config, ?MtlsTransportInterface $transport = null): SignedMtlsClient
    {
        return new SignedMtlsClient(
            $config,
            $this->signer,
            $transport ?? new GuzzleMtlsTransport($this->guzzleFactory->createClient($config)),
        );
    }

    /**
     * @throws InvalidConfigurationException from {@see ClientConfiguration::fromEnvFile()}
     */
    public function fromEnvFile(string $path, ?MtlsTransportInterface $transport = null): SignedMtlsClient
    {
        return $this->create(ClientConfiguration::fromEnvFile($path), $transport);
    }
}
