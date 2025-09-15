<?php

/**
 * Kuick Framework (https://github.com/milejko/kuick)
 *
 * @link       https://github.com/milejko/kuick
 * @copyright  Copyright (c) 2010-2024 Mariusz Miłejko (mariusz@milejko.pl)
 * @license    https://en.wikipedia.org/wiki/BSD_licenses New BSD License
 */

namespace Kuick\Redis;

interface RedisClientInterface
{
    public function set(string $key, mixed $value = null, ?int $ttl = null): bool;

    public function persist(string $key): bool;

    public function get(string $key): mixed;

    public function exists(string $key): bool;

    public function del(string $key): bool;

    public function flushDb(): bool;

    public function flushAll(): bool;

    public function keys(string $pattern = '*'): array;

    public function scan(?int &$iterator = null, string $pattern = '*', int $count = 1000, ?string $type = null): array|false;

    public function info(): array;
}
