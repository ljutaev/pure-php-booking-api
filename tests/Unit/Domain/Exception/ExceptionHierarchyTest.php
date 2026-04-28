<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Exception;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\InvalidValueObjectException;
use PHPUnit\Framework\TestCase;

class ExceptionHierarchyTest extends TestCase
{
    public function testInvalidValueObjectExceptionExtendsDomainException(): void
    {
        $e = new InvalidValueObjectException('bad value');

        self::assertInstanceOf(DomainException::class, $e);
        self::assertSame('bad value', $e->getMessage());
    }

    public function testBusinessRuleViolationExceptionExtendsDomainException(): void
    {
        $e = new BusinessRuleViolationException('rule violated');

        self::assertInstanceOf(DomainException::class, $e);
    }

    public function testEntityNotFoundExceptionExtendsDomainException(): void
    {
        $e = new EntityNotFoundException('not found');

        self::assertInstanceOf(DomainException::class, $e);
    }
}
