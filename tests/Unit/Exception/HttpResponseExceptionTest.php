<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Exception;

use Mihod\PaymentGateway\Exception\HttpResponseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(HttpResponseException::class)]
final class HttpResponseExceptionTest extends TestCase
{
    public function testStatusCodeAndResponseBodyAccessors(): void
    {
        $previous = new RuntimeException('upstream');
        $e = new HttpResponseException('bad gateway', 502, 'upstream body', 0, $previous);

        self::assertSame('bad gateway', $e->getMessage());
        self::assertSame(502, $e->statusCode());
        self::assertSame('upstream body', $e->responseBody());
        self::assertSame($previous, $e->getPrevious());
    }
}
