<?php

namespace Kuick\Redis;

class RedisClientMock implements RedisClientInterface
{
    private array $storage = [];
    private array $createTimes = [];
    private array $ttls = [];

    public function get(string $key): mixed
    {
        return $this->exists($key) ? $this->storage[$key] : false;
    }

    public function set(string $key, mixed $value = null, ?int $ttl = null): bool
    {
        $this->storage[$key] = $value;
        $this->createTimes[$key] = time();
        $this->ttls[$key] = $ttl;
        return true;
    }

    public function persist(string $key): bool
    {
        if (!$this->exists($key)) {
            return false;
        }
        $this->ttls[$key] = null;
        return true;
    }

    public function del(string $key): bool
    {
        unset($this->createTimes[$key]);
        unset($this->storage[$key]);
        unset($this->ttls[$key]);
        return true;
    }

    public function flushDb(): bool
    {
        $this->createTimes = $this->storage = $this->ttls = [];
        return true;
    }

    public function flushAll(): bool
    {
        return $this->flushDb();
    }

    public function exists(string $key): bool
    {
        if (
            !array_key_exists($key, $this->ttls) ||
            !array_key_exists($key, $this->storage) ||
            !array_key_exists($key, $this->createTimes)
        ) {
            return false;
        }
        $ttl = $this->ttls[$key];
        if (null === $ttl) {
            return true;
        }
        //failed ttl
        if ((int) ($ttl + $this->createTimes[$key]) <= time()) {
            return false;
        }
        return true;
    }

    public function keys(string $pattern = '*'): array
    {
        $keys = [];
        foreach (array_keys($this->storage) as $key) {
            if (!$this->exists($key)) {
                continue;
            }
            $keys[] = $key;
        }
        return $keys;
    }

    public function scan(?int &$iterator = null, string $pattern = '*', int $count = 1000, ?string $type = null): array|false
    {
        $keys = $this->keys($pattern);
        $count = count($keys);
        $start = $iterator;
        $end = (int) $iterator + 10;
        if ($end > $count) {
            $end = $count;
        }
        $iterator = $end;
        return array_slice($keys, (int) $start, $end - (int) $start);
    }

    public function info(): array
    {
        return [
            'redis_version' => '6.0.9',
            'uptime_in_seconds' => 3600,
            'connected_clients' => 10,
            'used_memory' => 1048576,
            'total_commands_processed' => 5000,
            'total_connections_received' => 100,
        ];
    }
}
