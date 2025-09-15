<?php

namespace Kuick\Redis;

use Nyholm\Dsn\DsnParser;
use Redis;

class RedisClient implements RedisClientInterface
{
    private const DEFAULT_PORT = 6379;
    private const READ_TIMEOUT = 2.5;
    private const CONNECT_TIMEOUT = 2.5;
    private const RETRY_INTERVAL = 100;
    private const PERSISTENT = true;

    private Redis $wrappedClient;

    /**
     * @param string $dsnString
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(string $dsnString)
    {
        $dsn = DsnParser::parse($dsnString);
        //calculate options
        $options = [
            'host'           => $dsn->getHost(),
            'port'           => $dsn->getPort() ?? self::DEFAULT_PORT,
            'readTimeout'    => $dsn->getParameter('readTimeout', self::READ_TIMEOUT),
            'connectTimeout' => $dsn->getParameter('connectTimeout', self::CONNECT_TIMEOUT),
            'retryInterval'  => $dsn->getParameter('retryInterval', self::RETRY_INTERVAL),
            'persistent'     => $dsn->getParameter('persistent', self::PERSISTENT),
        ];
        $this->wrappedClient = new Redis($options);
        //optional database selection (not empty path)
        if ($dsn->getPath()) {
            $this->wrappedClient->select((int) trim($dsn->getPath(), '/'));
        }
        //optional authentication
        if ($dsn->getParameter('user') || $dsn->getParameter('pass')) {
            $this->wrappedClient->auth(['user' => $dsn->getParameter('user', null), 'pass' => $dsn->getParameter('pass', null)]);
        }
    }

    public function get(string $key): mixed
    {
        return $this->wrappedClient->get($key);
    }

    public function set(string $key, mixed $value = null, ?int $ttl = null): bool
    {
        return $this->wrappedClient->set($key, $value, $ttl ? $ttl : null);
    }

    public function persist(string $key): bool
    {
        return (bool)$this->wrappedClient->persist($key);
    }

    public function del(string $key): bool
    {
        return (bool)$this->wrappedClient->del($key);
    }

    public function flushDb(): bool
    {
        return (bool)$this->wrappedClient->flushDB();
    }

    public function flushAll(): bool
    {
        return (bool)$this->wrappedClient->flushAll();
    }

    public function exists(string $key): bool
    {
        return (bool)$this->wrappedClient->exists($key);
    }

    public function keys(string $pattern = '*'): array
    {
        return $this->wrappedClient->keys($pattern);
    }

    public function scan(?int &$iterator = null, string $pattern = '*', int $count = 1000, ?string $type = null): array|false
    {
        return $this->wrappedClient->scan($iterator, $pattern, $count, $type);
    }

    public function info(): array
    {
        return $this->wrappedClient->info();
    }
}
