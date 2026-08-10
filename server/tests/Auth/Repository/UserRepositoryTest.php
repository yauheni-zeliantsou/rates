<?php

declare(strict_types=1);

namespace Tests\Auth\Repository;

use App\Auth\UserRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    public function testVerifyCredentialsReturnsUserIdWhenPasswordMatches(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('execute')->with(['user@example.com']);
        $statement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => 42, 'password_hash' => password_hash('secret123', PASSWORD_BCRYPT)]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT id, password_hash FROM users WHERE email = ?')
            ->willReturn($statement);

        $repository = new UserRepository($pdo);

        $this->assertSame(42, $repository->verifyCredentials('user@example.com', 'secret123'));
    }

    public function testVerifyCredentialsReturnsNullWhenPasswordDoesNotMatch(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())
            ->method('fetch')
            ->willReturn(['id' => 42, 'password_hash' => password_hash('secret123', PASSWORD_BCRYPT)]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($statement);

        $repository = new UserRepository($pdo);

        $this->assertNull($repository->verifyCredentials('user@example.com', 'wrong-password'));
    }

    public function testVerifyCredentialsReturnsNullWhenUserDoesNotExist(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($statement);

        $repository = new UserRepository($pdo);

        $this->assertNull($repository->verifyCredentials('unknown@example.com', 'secret123'));
    }
}
