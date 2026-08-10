<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Interface\UserRepositoryInterface;
use PDO;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function verifyCredentials(string $email, string $password): ?int
    {
        $statement = $this->pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $statement->execute([$email]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false || !password_verify($password, $row['password_hash'])) {
            return null;
        }

        return (int) $row['id'];
    }
}
