<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Config;

use Mihod\PaymentGateway\Exception\InvalidConfigurationException;

/**
 * Transport and signing settings. Construct via {@see self::fromArray()} or
 * {@see EnvironmentLoader::loadConfiguration()} so validation stays in one place.
 */
final class ClientConfiguration
{
    /**
     * @param non-empty-string $signatureHashAlgorithm Algorithm name for hash_hmac
     */
    public function __construct(
        private readonly string $clientCertificatePath,
        private readonly string $clientPrivateKeyPath,
        private readonly string $clientPrivateKeyPassphrase,
        private readonly string $hmacSharedSecret,
        private readonly bool $verifyServerCertificate = true,
        private readonly ?string $caBundlePath = null,
        private readonly string $signatureHeaderName = 'X-Signature',
        private readonly string $signatureHashAlgorithm = 'sha256',
    ) {
        $this->assertReadableFile($this->clientCertificatePath, 'client certificate');
        $this->assertReadableFile($this->clientPrivateKeyPath, 'client private key');

        if ($this->caBundlePath !== null && $this->caBundlePath !== '') {
            $this->assertReadableFile($this->caBundlePath, 'CA bundle');
        }

        if ($this->hmacSharedSecret === '') {
            throw new InvalidConfigurationException('HMAC shared secret must not be empty.');
        }
    }

    /**
     * @param array<mixed, mixed> $values Environment keys
     */
    public static function fromArray(array $values): self
    {
        $values = self::onlyStringKeys($values);
        $cert = self::string($values, 'MTLS_CLIENT_CERT');
        $key = self::string($values, 'MTLS_CLIENT_KEY');
        $pass = self::string($values, 'MTLS_CLIENT_KEY_PASSPHRASE', '');
        $secret = self::string($values, 'HMAC_SECRET');
        $verify = self::bool($values, 'MTLS_VERIFY_SSL', true);
        $caBundle = self::nullableString($values, 'MTLS_CA_BUNDLE');
        $header = self::string($values, 'SIGNATURE_HEADER_NAME', 'X-Signature');
        $algo = self::string($values, 'SIGNATURE_HASH_ALGO', 'sha256');

        if ($algo === '') {
            throw new InvalidConfigurationException('SIGNATURE_HASH_ALGO must not be empty.');
        }

        return new self($cert, $key, $pass, $secret, $verify, $caBundle === '' ? null : $caBundle, $header, $algo);
    }

    public function clientCertificatePath(): string
    {
        return $this->clientCertificatePath;
    }

    public function clientPrivateKeyPath(): string
    {
        return $this->clientPrivateKeyPath;
    }

    public function clientPrivateKeyPassphrase(): string
    {
        return $this->clientPrivateKeyPassphrase;
    }

    public function hmacSharedSecret(): string
    {
        return $this->hmacSharedSecret;
    }

    public function verifyServerCertificate(): bool
    {
        return $this->verifyServerCertificate;
    }

    public function caBundlePath(): ?string
    {
        return $this->caBundlePath;
    }

    public function signatureHeaderName(): string
    {
        return $this->signatureHeaderName;
    }

    public function signatureHashAlgorithm(): string
    {
        return $this->signatureHashAlgorithm;
    }

    /**
     * @param array<string, string|null> $values
     */
    private static function string(array $values, string $key, string $default = ''): string
    {
        if (! array_key_exists($key, $values)) {
            return $default;
        }

        $value = $values[$key];

        return $value ?? $default;
    }

    /**
     * @param array<string, string|null> $values
     */
    private static function nullableString(array $values, string $key): ?string
    {
        if (! array_key_exists($key, $values)) {
            return null;
        }

        $value = $values[$key];

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param array<string, string|null> $values
     */
    private static function bool(array $values, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
            return $default;
        }

        $filtered = filter_var($values[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $filtered ?? $default;
    }

    private function assertReadableFile(string $path, string $label): void
    {
        if ($path === '' || ! is_readable($path)) {
            throw new InvalidConfigurationException(sprintf('%s path is missing or not readable: %s', $label, $path));
        }
    }

    /**
     * @param array<mixed, mixed> $values
     *
     * @return array<string, string|null>
     */
    private static function onlyStringKeys(array $values): array
    {
        $out = [];

        foreach ($values as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if ($value === null) {
                $out[$key] = null;
            } elseif (is_scalar($value)) {
                $out[$key] = (string) $value;
            }
        }

        return $out;
    }
}
