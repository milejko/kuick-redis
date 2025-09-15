<?php

namespace Kuick\Tests\Redis;

use PHPUnit\Framework\TestCase;
use Kuick\Redis\RedisClientMock;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

/**
 * @covers Kuick\Redis\RedisClientMock
 */
class RedisClientMockTest extends TestCase
{
    public function testIfStandardFlowWorksCorrectly(): void
    {
        $redis = new RedisClientMock();
        assertFalse($redis->exists('inexisten'));

        assertTrue($redis->set('test', 'abc'));
        assertTrue($redis->exists('test'));
        assertEquals('abc', $redis->get('test'));

        assertTrue($redis->del('test'));
        assertFalse($redis->exists('test'));
        assertFalse($redis->get('test'));

        assertTrue($redis->set('test1', 'abc'));
        assertTrue($redis->set('test2', 'abc'));
        assertEquals(['test1', 'test2'], $redis->keys());
        assertTrue($redis->flushDb());
        assertEquals([], $redis->keys());

        assertTrue($redis->set('test1', 'abc'));
        assertTrue($redis->set('test2', 'abc'));
        assertEquals(['test1', 'test2'], $redis->keys());
        assertTrue($redis->flushAll());
        assertEquals([], $redis->keys());

        $iterator = null;
        assertEquals($redis->scan($iterator, '*'), $redis->keys('*'));
    }

    public function testIfCacheExpires(): void
    {
        $redis = new RedisClientMock();
        assertTrue($redis->set('test1', 'abc', 1));
        assertTrue($redis->set('test2', 'abc', 1));
        assertEquals(['test1', 'test2'], $redis->keys());
        sleep(1);//wait till expired
        assertFalse($redis->get('test2'));
        assertEquals([], $redis->keys());
    }

    public function testPersistence(): void
    {
        $redis = new RedisClientMock();
        assertTrue($redis->set('test', 'abc', 10));
        assertEquals(['test'], $redis->keys());
        //wait till expired
        assertFalse($redis->persist('inexistent'));
        assertTrue($redis->persist('test'));
        assertEquals('abc', $redis->get('test'));
    }

    public function testInfo(): void
    {
        $redis = new RedisClientMock();
        $info = $redis->info();
        assertTrue(is_array($info));
        assertTrue(isset($info['redis_version']));
    }
}
