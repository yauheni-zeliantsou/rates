<?php

declare(strict_types=1);

namespace App\Support\Cache;

use Redis;

final readonly class RedisConnection
{
    private const string HOST = 'redis';
    private const int PORT = 6379;

    public function redis(): Redis
    {
        $redis = new Redis();
        $redis->connect(self::HOST, self::PORT);

        return $redis;
    }
}
