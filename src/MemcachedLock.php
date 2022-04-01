<?php

namespace Qmister\Lock;

class MemcachedLock extends BaseLock
{
    use Singleton;
    /**
     * 等待锁超时时间，单位：毫秒，0为不限制.
     *
     * @var int
     */
    public $waitTimeout;

    /**
     * 获得锁每次尝试间隔，单位：毫秒.
     *
     * @var int
     */
    public $waitSleepTime;

    /**
     * 锁超时时间，单位：秒.
     *
     * @var int
     */
    public $lockExpire;

    public $guid;

    public $lockValue;
    /**
     * @var \Memcached
     */
    protected $handler;

    /**
     * 构造方法.
     *
     * @param string     $name          锁名称
     * @param \Memcached $handler
     * @param int        $waitTimeout   获得锁等待超时时间，单位：毫秒，0为不限制
     * @param int        $waitSleepTime 获得锁每次尝试间隔，单位：毫秒
     * @param int        $lockExpire    锁超时时间，单位：秒
     */
    public function __construct($name, \Memcached $handler, $waitTimeout = 0, $waitSleepTime = 1, $lockExpire = 3)
    {
        $this->name = $name;
        $this->handler = $handler;
        $this->waitTimeout = $waitTimeout;
        $this->waitSleepTime = $waitSleepTime;
        $this->lockExpire = $lockExpire;
        $this->guid = uniqid('', true);
    }

    /**
     * 加锁
     *
     * @return bool
     */
    protected function __lock()
    {
        $time = microtime(true);
        $sleepTime = $this->waitSleepTime * 1000;
        $waitTimeout = $this->waitTimeout / 1000;
        while (true) {
            $value = $this->handler->get($this->name);
            $this->lockValue = [
                'expire' => time() + $this->lockExpire,
                'guid'   => $this->guid,
            ];
            if (false === $value) {
                // 无值
                $result = $this->handler->add($this->name, $this->lockValue, $this->lockExpire);
                if ($result) {
                    return true;
                }
            } else {
                // 有值
                if ($value['expire'] < time()) {
                    $result = $this->handler->add($this->name, $this->lockValue, $this->lockExpire);
                    if ($result) {
                        return true;
                    }
                }
            }
            if (0 === $this->waitTimeout || microtime(true) - $time < $waitTimeout) {
                usleep($sleepTime);
            } else {
                break;
            }
        }

        return false;
    }

    /**
     * 释放锁
     *
     * @return bool
     */
    protected function __unlock()
    {
        if ((isset($this->lockValue['expire']) && $this->lockValue['expire'] > time())) {
            return $this->handler->delete($this->name) > 0;
        } else {
            return true;
        }
    }

    /**
     * 不阻塞加锁
     *
     * @return bool
     */
    protected function __unblockLock()
    {
        $value = $this->handler->get($this->name);
        $this->lockValue = [
            'expire' => time() + $this->lockExpire,
            'guid'   => $this->guid,
        ];
        if (false === $value) {
            // 无值
            $result = $this->handler->add($this->name, $this->lockValue, $this->lockExpire);
            if (!$result) {
                return false;
            }
        } else {
            // 有值
            if ($value < time()) {
                $result = $this->handler->add($this->name, $this->lockValue, $this->lockExpire);
                if (!$result) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 关闭锁对象
     *
     * @return bool
     */
    protected function __close()
    {
        if (!is_null($this->handler)) {
            $result = $this->handler->close();
            $this->handler = null;

            return $result;
        }
    }
}
