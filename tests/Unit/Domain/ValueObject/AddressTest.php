<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\Address;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testCreateValidAddress(): void
    {
        $address = new Address('Main St 1', 'Kyiv', 'UA', '01001');

        self::assertSame('Main St 1', $address->street);
        self::assertSame('Kyiv', $address->city);
        self::assertSame('UA', $address->country);
        self::assertSame('01001', $address->postalCode);
    }

    public function testThrowsOnEmptyStreet(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Address('', 'Kyiv', 'UA', '01001');
    }

    public function testThrowsOnEmptyCity(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Address('Main St 1', '', 'UA', '01001');
    }

    public function testThrowsOnEmptyCountry(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Address('Main St 1', 'Kyiv', '', '01001');
    }

    public function testThrowsOnEmptyPostalCode(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Address('Main St 1', 'Kyiv', 'UA', '');
    }
}
