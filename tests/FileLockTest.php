<?php
/*
 * Desc:
 * User: zhiqiang
 * Date: 2021-09-27 23:58
 */

namespace Qmister\Lock\Test;

use PHPUnit\Framework\TestCase;
use Qmister\Lock\FileLock;
use Qmister\Lock\Lock;

class FileLockTest extends TestCase
{
    public function testFileLock1()
    {
        $lock = new FileLock('test');

        try {
            $result = $lock->lock(
                function () {
                    // 加锁后处理的任务
                    $this->filePutContents('./file_lock1.log');
                }
            );
        } catch (\Exception $e) {
        }
        switch ($result) {
            case Lock::LOCK_RESULT_CONCURRENT_COMPLETE:
                // 其它请求已处理
                break;
            case Lock::LOCK_RESULT_CONCURRENT_UNTREATED:
                // 在当前请求处理
                break;
            case Lock::LOCK_RESULT_FAIL:
                echo '获取锁失败', PHP_EOL;
                break;
        }
        $this->assertEquals(true, is_file('./file_lock1.log'));
        $this->assertEquals(Lock::LOCK_RESULT_SUCCESS, $result);
    }

    public function testFileLock2()
    {
        $lock2 = new FileLock('test2');

        try {
            if (Lock::LOCK_RESULT_SUCCESS === $lock2->lock()) {
                $this->filePutContents('./file_lock2.log');
                $lock2->unlock();
            }
        } catch (\Exception $e) {
        }
        $this->assertEquals(true, is_file('./file_lock2.log'));
    }

    public function filePutContents($file)
    {
        file_put_contents($file, time().PHP_EOL, FILE_APPEND);
    }
}
