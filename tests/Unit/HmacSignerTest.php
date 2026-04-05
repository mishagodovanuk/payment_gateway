<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit;

use Mihod\PaymentGateway\Tests\DataProviders\HmacSignerDataProvider;
use Mihod\PaymentGateway\Signature\HmacSigner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

#[CoversClass(HmacSigner::class)]
final class HmacSignerTest extends TestCase
{
    #[DataProviderExternal(HmacSignerDataProvider::class, 'canonicalQueryStringCases')]
    public function testCanonicalQueryString(array $input, string $expected): void
    {
        $signer = new HmacSigner();
        self::assertSame($expected, $signer->canonicalQueryString($input));
    }

    #[DataProviderExternal(HmacSignerDataProvider::class, 'signProducesHexDigestCases')]
    public function testSignProducesDeterministicLowercaseHexDigest(
        string $payload,
        string $secret,
        string $algorithm,
        string $expected
    ): void {
        $signer = new HmacSigner();
        $signature = $signer->sign($payload, $secret, $algorithm);
        self::assertSame($expected, $signature);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    public function testNestedArrayInQueryThrows(): void
    {
        $signer = new HmacSigner();
        $this->expectException(\InvalidArgumentException::class);
        $signer->canonicalQueryString(['a' => ['nested']]);
    }

    public function testNonStringParameterNameThrows(): void
    {
        $signer = new HmacSigner();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be strings');
        $signer->canonicalQueryString([0 => 'value']);
    }
}
