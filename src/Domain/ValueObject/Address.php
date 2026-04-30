<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class Address
{
    public readonly string $street;
    public readonly string $city;
    public readonly string $country;
    public readonly string $postalCode;

    public function __construct(string $street, string $city, string $country, string $postalCode)
    {
        foreach (['street' => $street, 'city' => $city, 'country' => $country, 'postalCode' => $postalCode] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidValueObjectException("Address {$field} cannot be empty");
            }
        }

        $this->street     = trim($street);
        $this->city       = trim($city);
        $this->country    = trim($country);
        $this->postalCode = trim($postalCode);
    }
}
