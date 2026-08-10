<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\Repository\TokenRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class TokenRepositoryTest extends TestCase
{
    public function testSaveInsertsTokenRow(): void
    {
        $expiresAt = new DateTimeImmutable('2026-08-07 12:00:00');

        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())
            ->method('execute')
            ->with(['hash', 42, 'web-frontend', '2026-08-07 12:00:00']);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO oauth_tokens (token_hash, user_id, client_id, expires_at) VALUES (?, ?, ?, ?)')
            ->willReturn($statement);

        $repository = new TokenRepository($pdo);
        $repository->save('hash', 42, 'web-frontend', $expiresAt);
    }

    public function testFindActiveUserIdByTokenHashReturnsUserIdWhenFound(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('execute')->with(['hash']);
        $statement->expects($this->once())->method('fetchColumn')->willReturn('42');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($statement);

        $repository = new TokenRepository($pdo);

        $this->assertSame(42, $repository->findActiveUserIdByTokenHash('hash'));
    }

    public function testFindActiveUserIdByTokenHashReturnsNullWhenNotFound(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchColumn')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($statement);

        $repository = new TokenRepository($pdo);

        $this->assertNull($repository->findActiveUserIdByTokenHash('hash'));
    }
}
