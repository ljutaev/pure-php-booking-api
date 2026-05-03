<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Command;

use App\Application\Command\RegisterUser\RegisterUserCommand;
use App\Application\Command\RegisterUser\RegisterUserHandler;
use App\Domain\Entity\User;
use App\Domain\Exception\DuplicateEmailException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class RegisterUserHandlerTest extends TestCase
{
    private function makeCommand(string $email = 'john@example.com'): RegisterUserCommand
    {
        return new RegisterUserCommand(
            email: $email,
            password: 'secret123',
            firstName: 'John',
            lastName: 'Doe',
        );
    }

    public function testHandleReturnsUserId(): void
    {
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);

        $result = (new RegisterUserHandler($users))->handle($this->makeCommand());

        self::assertInstanceOf(UserId::class, $result);
    }

    public function testHandleSavesUserWithHashedPassword(): void
    {
        $savedUser = null;
        $users     = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);
        $users->method('save')->willReturnCallback(function (User $u) use (&$savedUser): void {
            $savedUser = $u;
        });

        (new RegisterUserHandler($users))->handle($this->makeCommand());

        self::assertNotNull($savedUser);
        self::assertTrue(password_verify('secret123', $savedUser->getPasswordHash()));
    }

    public function testHandleSavesCorrectUserData(): void
    {
        $savedUser = null;
        $users     = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);
        $users->method('save')->willReturnCallback(function (User $u) use (&$savedUser): void {
            $savedUser = $u;
        });

        (new RegisterUserHandler($users))->handle($this->makeCommand());

        self::assertNotNull($savedUser);
        self::assertSame('john@example.com', $savedUser->getEmail()->value);
        self::assertSame('John', $savedUser->getFirstName());
        self::assertSame('Doe', $savedUser->getLastName());
    }

    public function testHandleThrowsWhenEmailAlreadyTaken(): void
    {
        $existing = new User(
            UserId::generate(),
            new \App\Domain\ValueObject\Email('john@example.com'),
            password_hash('oldpass', PASSWORD_BCRYPT),
            'John',
            'Doe',
        );

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($existing);

        $this->expectException(DuplicateEmailException::class);
        (new RegisterUserHandler($users))->handle($this->makeCommand());
    }
}
