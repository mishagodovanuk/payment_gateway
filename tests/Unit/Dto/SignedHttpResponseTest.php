<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Dto;

use Mihod\PaymentGateway\Dto\SignedHttpResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignedHttpResponse::class)]
final class SignedHttpResponseTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $headers = [
            'content-type' => 'text/plain',
            'set-cookie' => ['a=1', 'b=2'],
        ];

        $r = new SignedHttpResponse(201, 'payload', $headers);

        self::assertSame(201, $r->statusCode);
        self::assertSame('payload', $r->body);
        self::assertSame($headers, $r->headers);
    }
}
