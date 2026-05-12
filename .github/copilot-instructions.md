# Kuick Redis — Copilot Instructions

## Commands

```bash
# Fix code style
composer fix:phpcbf

# Static analysis (PHPStan level 5)
composer test:phpstan

# Code style check (PSR-12)
composer test:phpcs

# Mess detector
composer test:phpmd

# Run tests (requires Redis; see below)
composer test:phpunit

# Run all checks
composer test:all

# Run a single test
XDEBUG_MODE=coverage vendor/bin/phpunit --filter testMethodName

# Full CI run via Docker (builds image, runs Redis + tests, tears down)
make test
```

## Architecture

This is a minimal PHP 8.2+ library with three source files:

- **`RedisClientInterface`** — contract shared by both implementations (`get`, `set`, `del`, `exists`, `persist`, `keys`, `scan`, `flushDb`, `flushAll`, `info`)
- **`RedisClient`** — production implementation wrapping PHP's `Redis` extension; constructed from a DSN string (`redis://[user:pass@]host[:port][/db][?param=value]`)
- **`RedisClientMock`** — pure in-memory implementation of `RedisClientInterface` for use in tests; simulates TTL expiry using `createTimes` + `ttls` arrays without real timers

## Key Conventions

### DSN-based construction
`RedisClient` is always constructed with a DSN string parsed via `nyholm/dsn`. Optional parameters: `readTimeout`, `connectTimeout`, `retryInterval`, `persistent`. Database selection uses the DSN path (`/1` → `SELECT 1`). Auth uses `?user=...&pass=...`.

### Integration vs. unit tests
`RedisClientTest` connects to `test-redis:6379` (hostname only reachable inside Docker). Run `make test` for the full integration suite. `RedisClientMockTest` has no external dependencies and can run locally without Docker.

### Test class `#[CoversClass]` attribute
Every test class must declare `#[CoversClass(ClassName::class)]` (enforced via `requireCoverageMetadata` in `phpunit.xml`).

### Code style
PSR-12. Run `composer fix:phpcbf` before committing to auto-fix formatting. `@SuppressWarnings(PHPMD.StaticAccess)` is used where PHPMD rules would otherwise flag `DsnParser::parse()`.
