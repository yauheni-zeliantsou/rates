<?php

declare(strict_types=1);

namespace App\Auth\Interface;

interface UserRepositoryInterface
{
    public function verifyCredentials(string $email, string $password): ?int;
}
