<?php
/*
 * Desc:
 * User: zhiqiang
 * Date: 2021-09-28 01:42
 */

namespace Qmister\Lock\Test;

use PHPUnit\Framework\TestCase;
use Qmister\Lock\Lock;
use Qmister\Lock\RedisLock;

class RedisLockTest extends TestCase
{
    public function testRedisLock()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $lock2 = new RedisLock('test', $redis);

        try {
            if (Lock::LOCK_RESULT_SUCCESS === $lock2->lock()) {
                $this->filePutContents('./redis_lock.log');
                $lock2->unlock();
            }
        } catch (\Exception $e) {
        }
        $this->assertEquals(true, is_file('./redis_lock.log'));
    }

    public function filePutContents($file)
    {
        file_put_contents($file, time().PHP_EOL, FILE_APPEND);
    }
}
