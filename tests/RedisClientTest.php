<?php

namespace Kuick\Tests\Redis;

use Kuick\Redis\RedisClient;
use PHPUnit\Framework\TestCase;
use RedisException;

/**
 * @covers Kuick\Redis\RedisClient
 */
class RedisClientTest extends TestCase
{
    public function testIfWrongRedisHostThrowsAnExceptionWithGivenDatabase(): void
    {
        $this->expectException(RedisException::class);
        new RedisClient('redis://some.inexistent.host:7000/1?persistent=false');
    }

    public function testIfAuthorizationTakesPlace(): void
    {
        $this->expectException(RedisException::class);
        new RedisClient('redis://127.0.0.1:6379?user=user&pass=pass');
    }

    public function testIfSetWorksCorrectly(): void
    {
        $this->expectException(RedisException::class);
        $redis = new RedisClient('redis://127.0.0.1');
        $redis->set('test', 'abc');
    }

    public function testIfStandardFlowWorksCorrectly(): void
    {
        $redis = new RedisClient('redis://test-redis:6379/1?persistent=false');
        // flush db to start from scratch
        $this->assertTrue($redis->flushDb());
        $this->assertFalse($redis->get('test'));
        // set test = abc with 10s ttl
        $this->assertTrue($redis->set('test', 'abc', 10));
        $this->assertEquals('abc', $redis->get('test'));
        // persist shoudn't fail as 10s ttl is set
        $this->assertTrue($redis->persist('test'));
        // ttl already removed, so persist should return false
        $this->assertFalse($redis->persist('test'));
        // value maintained
        $this->assertEquals('abc', $redis->get('test'));
        $this->assertTrue($redis->del('test'));
        $this->assertFalse($redis->get('test'));
        $this->assertTrue($redis->set('test', 'abc'));
        $this->assertTrue($redis->exists('test'));
        // flush all keys
        $this->assertTrue($redis->flushAll());
        $this->assertFalse($redis->exists('test'));
    }

    public function testIfKeyBrowsingWorksCorrectly(): void
    {
        $redis = new RedisClient('redis://test-redis:6379/1?persistent=false');
        // flush db to start from scratch
        $this->assertTrue($redis->flushDb());
        $this->assertEquals([], $redis->keys());
        $this->assertTrue($redis->set('test1', 'abc'));
        $this->assertTrue($redis->set('test2', 'abc'));
        $this->assertContains('test1', $redis->keys());
        $this->assertContains('test2', $redis->keys());
        $this->assertTrue($redis->flushAll());
        $this->assertEquals([], $redis->keys());
    }

    public function testIfScansWorkCorrectly(): void
    {
        $redis = new RedisClient('redis://test-redis:6379/1?persistent=false');
        // flush db to start from scratch
        $this->assertTrue($redis->flushDb());
        $this->assertEquals([], $redis->keys());
        $this->assertTrue($redis->set('test1', 'abc'));
        $this->assertTrue($redis->set('test2', 'abc'));
        $iterator = null;
        $scanned = $redis->scan($iterator, '*');
        $this->assertContains('test1', $scanned);
        $this->assertContains('test2', $scanned);
        // flush all keys
        $this->assertTrue($redis->flushAll());
        $iterator = null;
        $scanned = $redis->scan($iterator, '*');
        $this->assertEmpty($scanned);
    }

    public function testIfInfoWorksCorrectly(): void
    {
        $redis = new RedisClient('redis://test-redis:6379/1?persistent=false');
        $info = $redis->info();
        $this->assertIsArray($info);
        $this->assertArrayHasKey('redis_version', $info);
    }
}
