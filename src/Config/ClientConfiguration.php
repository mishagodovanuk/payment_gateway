<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Config;

use Dotenv\Dotenv;
use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use SensitiveParameter;

/**
 * mTLS transport and HMAC signing configuration.
 */
final readonly class ClientConfiguration
{
    public function __construct(
        public string $clientCertificatePath,
        public string $clientPrivateKeyPath,
        public HttpClientConfiguration $http,
        #[SensitiveParameter]
        public string $clientPrivateKeyPassphrase = '',
        #[SensitiveParameter]
        public string $hmacSharedSecret = '',
        public bool $verifyServerCertificate = true,
        public ?string $caBundlePath = null,
        public string $signatureHeaderName = 'X-Signature',
        public string $signatureHashAlgorithm = 'sha256',
    ) {
        self::isReadable($clientCertificatePath, 'Client certificate');
        self::isReadable($clientPrivateKeyPath, 'Client private key');
        self::isReadable($caBundlePath, 'CA bundle');
        self::isNotEmpty($hmacSharedSecret, 'HMAC shared secret');
    }

    /**
     * Loads {@see $_ENV} from the given file (mutable Dotenv), then builds configuration.
     *
     * @throws InvalidConfigurationException when the file or required values are invalid
     */
    public static function fromEnvFile(string $path): self
    {
        self::isReadable($path, 'Environment file');

        Dotenv::createMutable(dirname($path), basename($path))->load();

        return self::fromArray($_ENV);
    }

    /**
     * @param array{
     *   MTLS_CLIENT_CERT?: string,
     *   MTLS_CLIENT_KEY?: string,
     *   MTLS_CLIENT_KEY_PASSPHRASE?: string,
     *   HMAC_SECRET?: string,
     *   MTLS_VERIFY_SSL?: string,
     *   MTLS_CA_BUNDLE?: string,
     *   SIGNATURE_HEADER_NAME?: string,
     *   SIGNATURE_HASH_ALGO?: string,
     *   HTTP_ERRORS?: string,
     *   HTTP_TIMEOUT_SECONDS?: string,
     *   HTTP_CONNECT_TIMEOUT_SECONDS?: string
     * } $env Typically {@see $_ENV} or Dotenv output (string values only).
     *
     * @throws InvalidConfigurationException when paths are unreadable or HMAC secret is empty
     */
    public static function fromArray(array $env): self
    {
        $caBundlePath = $env['MTLS_CA_BUNDLE'] ?? '';
        $mtlsVerifySsl = $env['MTLS_VERIFY_SSL'] ?? '';
        $httpConfig = HttpClientConfiguration::fromArray($env);

        return new self(
            clientCertificatePath: $env['MTLS_CLIENT_CERT'] ?? '',
            clientPrivateKeyPath: $env['MTLS_CLIENT_KEY'] ?? '',
            http: $httpConfig,
            clientPrivateKeyPassphrase: $env['MTLS_CLIENT_KEY_PASSPHRASE'] ?? '',
            hmacSharedSecret: $env['HMAC_SECRET'] ?? '',
            verifyServerCertificate: $mtlsVerifySsl !== ''
                ? filter_var($mtlsVerifySsl, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true
                : true,
            caBundlePath: $caBundlePath !== '' ? $caBundlePath : null,
            signatureHeaderName: $env['SIGNATURE_HEADER_NAME'] ?? 'X-Signature',
            signatureHashAlgorithm: $env['SIGNATURE_HASH_ALGO'] ?? 'sha256',
        );
    }

    private static function isReadable(?string $path, string $label): void
    {
        if ($path !== null && !is_readable($path)) {
            throw new InvalidConfigurationException("{$label} is not readable: {$path}");
        }
    }

    private static function isNotEmpty(string $value, string $label): void
    {
        if ($value === '') {
            throw new InvalidConfigurationException("{$label} must not be empty.");
        }
    }
}
