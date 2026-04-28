<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testCreateValidEmail(): void
    {
        $email = new Email('User@Example.COM');

        self::assertSame('user@example.com', $email->value);
    }

    public function testThrowsOnEmptyEmail(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Email('');
    }

    public function testThrowsOnInvalidFormat(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Email('not-an-email');
    }

    public function testThrowsOnMissingDomain(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Email('user@');
    }

    public function testEqualsIsCaseInsensitive(): void
    {
        $a = new Email('User@Example.com');
        $b = new Email('user@example.com');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentEmail(): void
    {
        $a = new Email('alice@example.com');
        $b = new Email('bob@example.com');

        self::assertFalse($a->equals($b));
    }
}
