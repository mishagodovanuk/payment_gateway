<?php

declare(strict_types=1);

namespace Mihod\PaymentGateway\Tests\Unit\Exception;

use Mihod\PaymentGateway\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(InvalidConfigurationException::class)]
final class InvalidConfigurationExceptionTest extends TestCase
{
    public function testCanConstructWithMessageAndPrevious(): void
    {
        $previous = new RuntimeException('cause');
        $e = new InvalidConfigurationException('bad config', 42, $previous);

        self::assertSame('bad config', $e->getMessage());
        self::assertSame(42, $e->getCode());
        self::assertSame($previous, $e->getPrevious());
    }
}
