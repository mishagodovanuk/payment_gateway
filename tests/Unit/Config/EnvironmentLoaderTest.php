<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Config;

use Mihod\PaymentGateway\Config\EnvironmentLoader;
use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvironmentLoader::class)]
final class EnvironmentLoaderTest extends TestCase
{
    public function testParseFileThrowsWhenPathIsNotARegularFile(): void
    {
        $loader = new EnvironmentLoader(sys_get_temp_dir());

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must be a regular file');

        $loader->parseFile();
    }

    public function testParseFileThrowsWhenEnvFileIsNotReadable(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env') ?: self::fail('tempnam');
        chmod($path, 0000);
        $loader = new EnvironmentLoader($path);

        try {
            $this->expectException(InvalidConfigurationException::class);
            $loader->parseFile();
        } finally {
            chmod($path, 0600);
            unlink($path);
        }
    }

    public function testLoadIntoEnvironmentThrowsWhenFileNotReadable(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env') ?: self::fail('tempnam');
        chmod($path, 0000);
        $loader = new EnvironmentLoader($path);

        try {
            $this->expectException(InvalidConfigurationException::class);
            $loader->loadIntoEnvironment();
        } finally {
            chmod($path, 0600);
            unlink($path);
        }
    }

    public function testLoadIntoEnvironmentThrowsWhenPathIsNotARegularFile(): void
    {
        $loader = new EnvironmentLoader(sys_get_temp_dir());

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must be a regular file');

        $loader->loadIntoEnvironment();
    }

    public function testLoadIntoEnvironmentInvokesDotenvSafeLoad(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'env') ?: self::fail('tempnam');
        $unique = 'PGW_ENV_TEST_' . bin2hex(random_bytes(6));
        file_put_contents($path, "{$unique}=loaded-value\n");

        $loader = new EnvironmentLoader($path);
        $loader->loadIntoEnvironment();

        self::assertSame('loaded-value', $_ENV[$unique] ?? null);

        unset($_ENV[$unique]);
        @unlink($path);
    }
}
