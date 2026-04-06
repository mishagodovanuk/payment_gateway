<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Config;

use Mihod\PaymentGateway\Config\HttpClientConfiguration;
use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpClientConfiguration::class)]
final class HttpClientConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $cfg = new HttpClientConfiguration();

        self::assertTrue($cfg->httpErrors);
        self::assertSame(30.0, $cfg->timeoutSeconds);
        self::assertSame(10.0, $cfg->connectTimeoutSeconds);
    }

    public function testFromArrayParsesValues(): void
    {
        $cfg = HttpClientConfiguration::fromArray([
            'HTTP_ERRORS' => 'false',
            'HTTP_TIMEOUT_SECONDS' => '12.5',
            'HTTP_CONNECT_TIMEOUT_SECONDS' => '3',
        ]);

        self::assertFalse($cfg->httpErrors);
        self::assertSame(12.5, $cfg->timeoutSeconds);
        self::assertSame(3.0, $cfg->connectTimeoutSeconds);
    }

    public function testFromArrayUsesDefaultsOnEmptyStrings(): void
    {
        $cfg = HttpClientConfiguration::fromArray([
            'HTTP_ERRORS' => '',
            'HTTP_TIMEOUT_SECONDS' => '',
            'HTTP_CONNECT_TIMEOUT_SECONDS' => '',
        ]);

        self::assertTrue($cfg->httpErrors);
        self::assertSame(30.0, $cfg->timeoutSeconds);
        self::assertSame(10.0, $cfg->connectTimeoutSeconds);
    }

    public function testConstructorRejectsNonPositiveTimeout(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('HTTP timeout must be a positive number');

        new HttpClientConfiguration(timeoutSeconds: 0.0);
    }
}

