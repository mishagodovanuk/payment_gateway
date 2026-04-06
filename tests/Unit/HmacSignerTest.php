<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit;

use InvalidArgumentException;
use Mihod\PaymentGateway\Signature\HmacSigner;
use Mihod\PaymentGateway\Tests\DataProviders\HmacSignerDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

#[CoversClass(HmacSigner::class)]
final class HmacSignerTest extends TestCase
{
    #[DataProviderExternal(HmacSignerDataProvider::class, 'canonicalQueryStringCases')]
    public function testCanonicalQueryString(array $input, string $expected): void
    {
        self::assertSame($expected, (new HmacSigner())->canonicalQueryString($input));
    }

    #[DataProviderExternal(HmacSignerDataProvider::class, 'signProducesHexDigestCases')]
    public function testSignProducesHexDigest(
        string $payload,
        string $secret,
        string $algorithm,
        string $expected,
    ): void {
        $signature = (new HmacSigner())->sign($payload, $secret, $algorithm);
        self::assertSame($expected, $signature);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    public function testNestedArrayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new HmacSigner())->canonicalQueryString(['a' => ['nested']]);
    }
}
