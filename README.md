# Kuick Redis

[![Latest Version](https://img.shields.io/github/release/milejko/kuick-redis.svg?cacheSeconds=3600)](https://github.com/milejko/kuick-redis/releases)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4%20|%208.5-blue?logo=php&cacheSeconds=3600)](https://www.php.net)
[![Total Downloads](https://img.shields.io/packagist/dt/kuick/redis.svg?cacheSeconds=3600)](https://packagist.org/packages/kuick/redis)
[![GitHub Actions CI](https://github.com/milejko/kuick-redis/actions/workflows/ci.yml/badge.svg)](https://github.com/milejko/kuick-redis/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/milejko/kuick-redis/graph/badge.svg?token=80QEBDHGPH)](https://codecov.io/gh/milejko/kuick-redis)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?cacheSeconds=14400)](LICENSE)

A minimal, DSN-configured Redis client for PHP 8.2+ with a built-in in-memory mock — no extra dependencies beyond the `php-redis` extension and `nyholm/dsn`.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [DSN Format](#dsn-format)
- [API Reference](#api-reference)
- [Testing Without Redis — RedisClientMock](#testing-without-redis--redisclientmock)
- [Architecture](#architecture)
- [Development](#development)
  - [Running the Full CI Suite (Docker)](#running-the-full-ci-suite-docker)
  - [Running Tests Locally](#running-tests-locally)
  - [Code Quality](#code-quality)
- [Contributing](#contributing)

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | ≥ 8.2 |
| PHP extension | `php-redis` |
| Composer dependency | `nyholm/dsn` ^2.0 |

---

## Installation

```bash
composer require kuick/redis
```

---

## Quick Start

```php
use Kuick\Redis\RedisClient;

// Connect to a local Redis instance
$redis = new RedisClient('redis://localhost');

// Store a value with a 60-second TTL
$redis->set('greeting', 'hello world', 60);

// Retrieve it
echo $redis->get('greeting'); // hello world

// Remove the TTL — key persists indefinitely
$redis->persist('greeting');

// Delete the key
$redis->del('greeting');
```

---

## DSN Format

```
redis://[user:pass@]host[:port][/db][?param=value&...]
```

| Component | Description | Default |
|---|---|---|
| `host` | Redis server hostname or IP | _(required)_ |
| `port` | Redis server port | `6379` |
| `/db` | Database index (e.g. `/1` → `SELECT 1`) | _(none)_ |
| `?user=` | ACL username for authentication | _(none)_ |
| `?pass=` | Password for authentication | _(none)_ |
| `?readTimeout=` | Read timeout in seconds (float) | `2.5` |
| `?connectTimeout=` | Connection timeout in seconds (float) | `2.5` |
| `?retryInterval=` | Retry interval in milliseconds | `100` |
| `?persistent=` | Use a persistent connection (`1`/`0`) | `1` |

**Examples:**

```
redis://localhost
redis://localhost:6380/2
redis://localhost?pass=secret&readTimeout=5
redis://localhost?user=myuser&pass=mypass
redis://redis.example.com:6379/0?persistent=0&connectTimeout=1
```

---

## API Reference

All three classes share the same `RedisClientInterface` contract:

```php
interface RedisClientInterface
{
    // Store a value. $ttl = null means no expiry.
    public function set(string $key, mixed $value = null, ?int $ttl = null): bool;

    // Remove TTL from a key so it persists indefinitely.
    public function persist(string $key): bool;

    // Retrieve a value; returns false if the key does not exist or has expired.
    public function get(string $key): mixed;

    // Check whether a key exists (and has not expired).
    public function exists(string $key): bool;

    // Delete a single key.
    public function del(string $key): bool;

    // Delete all keys in the current database.
    public function flushDb(): bool;

    // Delete all keys in all databases.
    public function flushAll(): bool;

    // Return keys matching a glob pattern (default: all keys).
    public function keys(string $pattern = '*'): array;

    // Incrementally iterate keys (cursor-based). $iterator is updated each call;
    // returns false when iteration is complete.
    public function scan(?int &$iterator = null, string $pattern = '*', int $count = 1000, ?string $type = null): array|false;

    // Return server info as an associative array.
    public function info(): array;
}
```

### `scan` usage example

```php
$iterator = null;
do {
    $keys = $redis->scan($iterator, 'session:*', 100);
    foreach ($keys ?: [] as $key) {
        // process $key
    }
} while ($iterator !== 0 && $iterator !== null);
```

---

## Testing Without Redis — RedisClientMock

`RedisClientMock` is a pure PHP, in-memory implementation of `RedisClientInterface`. It simulates TTL expiry using wall-clock comparisons — no timers, no Redis server required.

Use it anywhere you'd use the real client:

```php
use Kuick\Redis\RedisClientMock;

$redis = new RedisClientMock();
$redis->set('token', 'abc123', 10); // expires in 10 seconds
var_dump($redis->exists('token')); // bool(true)

$redis->persist('token');          // remove expiry
$redis->del('token');
```

Type-hint against `RedisClientInterface` in your own services so you can swap implementations freely:

```php
use Kuick\Redis\RedisClientInterface;

class SessionStore
{
    public function __construct(private RedisClientInterface $redis) {}

    public function save(string $id, array $data, int $ttl = 3600): void
    {
        $this->redis->set($id, serialize($data), $ttl);
    }
}

// Production
$store = new SessionStore(new RedisClient('redis://localhost'));

// Tests — no Redis needed
$store = new SessionStore(new RedisClientMock());
```

---

## Architecture

```
src/
├── RedisClientInterface.php   # Shared contract (get, set, del, exists, persist,
│                              #   keys, scan, flushDb, flushAll, info)
├── RedisClient.php            # Production: wraps php-redis, DSN-configured
└── RedisClientMock.php        # Test double: pure in-memory, simulates TTL

tests/
├── RedisClientTest.php        # Integration tests (requires test-redis:6379)
└── RedisClientMockTest.php    # Unit tests (no external deps)
```

`RedisClient` delegates directly to the native `Redis` extension object. The DSN is parsed via `nyholm/dsn` at construction time — options like `readTimeout`, `connectTimeout`, `retryInterval`, and `persistent` are passed through the `Redis` constructor options array (PHP `redis` >= 6.0 style).

---

## Development

### Running the Full CI Suite (Docker)

The `Makefile` target builds a PHP image, starts a Redis 8 sidecar, runs all checks inside the container, then tears everything down:

```bash
make test
```

This is the canonical way to reproduce the CI environment locally. PHP version can be overridden:

```bash
PHP_VERSION=8.3 make test
```

### Running Tests Locally

Integration tests (`RedisClientTest`) require a reachable Redis at `test-redis:6379` (add an `/etc/hosts` alias or use Docker). Mock tests have no external dependencies:

```bash
# Unit tests only (no Redis needed)
XDEBUG_MODE=coverage vendor/bin/phpunit --filter RedisClientMockTest

# Single test method
XDEBUG_MODE=coverage vendor/bin/phpunit --filter testMethodName

# All tests (Redis required at test-redis:6379)
composer test:phpunit
```

### Code Quality

| Command | Tool | Description |
|---|---|---|
| `composer fix:phpcbf` | PHP_CodeSniffer | Auto-fix PSR-12 style issues |
| `composer test:phpcs` | PHP_CodeSniffer | Check PSR-12 compliance |
| `composer test:phpstan` | PHPStan (level 5) | Static analysis |
| `composer test:phpmd` | PHPMD | Mess detection |
| `composer test:phpunit` | PHPUnit | Tests with coverage |
| `composer test:all` | — | Run all of the above |

Run `composer fix:phpcbf` before committing — the CI pipeline enforces PSR-12.

> **Note:** Every test class must declare `#[CoversClass(ClassName::class)]` — coverage metadata is required by `phpunit.xml`.

---

## Contributing

1. Fork the repository and create a feature branch.
2. Run `composer fix:phpcbf` to normalise formatting.
3. Add or update tests; ensure `composer test:all` passes.
4. Open a pull request — CI will run automatically.

---

MIT © [Mariusz Miłejko](https://github.com/milejko)
