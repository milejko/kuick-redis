<?php

namespace Kuick\Tests\Redis;

use PHPUnit\Framework\TestCase;
use Kuick\Redis\RedisMock;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

/**
 * @covers Kuick\Redis\RedisMock
 */
class RedisMockTest extends TestCase
{
    public function testIfStandardFlowWorksCorrectly(): void
    {
        $redis = new RedisMock();
        assertFalse($redis->exists('inexisten'));

        assertTrue($redis->set('test', 'abc'));
        assertTrue($redis->exists('test'));
        assertEquals('abc', $redis->get('test'));

        assertTrue($redis->del('test'));
        assertFalse($redis->exists('test'));
        assertEquals(null, $redis->get('test'));

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
    }
}