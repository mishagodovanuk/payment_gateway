<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Config;

use Dotenv\Dotenv;
use Mihod\PaymentGateway\Exception\InvalidConfigurationException;

/**
 * Reads .env files without mutating superglobals (via {@see Dotenv::parse()}), then merges with
 * {@see $_ENV} so deployment environment variables override file contents.
 */
final class EnvironmentLoader
{
    public function __construct(
        private readonly string $envFilePath
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function parseFile(): array
    {
        if (! is_readable($this->envFilePath)) {
            throw new InvalidConfigurationException(sprintf('.env file not readable: %s', $this->envFilePath));
        }

        if (! is_file($this->envFilePath)) {
            throw new InvalidConfigurationException(sprintf('.env must be a regular file: %s', $this->envFilePath));
        }

        $content = file_get_contents($this->envFilePath);
        // @codeCoverageIgnoreStart
        if ($content === false) {
            throw new InvalidConfigurationException(sprintf('Could not read .env file: %s', $this->envFilePath));
        }
        // @codeCoverageIgnoreEnd

        return Dotenv::parse($content);
    }

    public function loadConfiguration(): ClientConfiguration
    {
        $parsed = $this->parseFile();
        $merged = array_merge($parsed, $_ENV);

        return ClientConfiguration::fromArray($merged);
    }

    /**
     * Loads variables into the process environment (for legacy apps that expect getenv() side effects).
     */
    public function loadIntoEnvironment(): void
    {
        if (! is_readable($this->envFilePath)) {
            throw new InvalidConfigurationException(sprintf('.env file not readable: %s', $this->envFilePath));
        }

        if (! is_file($this->envFilePath)) {
            throw new InvalidConfigurationException(sprintf('.env must be a regular file: %s', $this->envFilePath));
        }

        $directory = dirname($this->envFilePath);
        $file = basename($this->envFilePath);
        Dotenv::createImmutable($directory, $file)->safeLoad();
    }
}
