<?php


namespace Qmister\Lock;

class RedisLock extends BaseLock
{
    use Singleton;
    /**
     * 等待锁超时时间，单位：毫秒，0为不限制.
     *
     * @var int
     */
    protected $waitTimeout;

    /**
     * 获得锁每次尝试间隔，单位：毫秒.
     *
     * @var int
     */
    protected $waitSleepTime;

    /**
     * 锁超时时间，单位：秒.
     *
     * @var int
     */
    protected $lockExpire;
    /**
     * @var string
     */
    protected $guid;

    /**
     * @var array
     */
    protected $lockValue;
    /**
     * @var \Redis
     */
    protected $handler;

    /**
     * 构造方法.
     *
     * @param string $name          锁名称
     * @param \Redis $handler
     * @param int    $waitTimeout   获得锁等待超时时间，单位：毫秒，0为不限制
     * @param int    $waitSleepTime 获得锁每次尝试间隔，单位：毫秒
     * @param int    $lockExpire    锁超时时间，单位：秒
     */
    public function __construct($name, \Redis $handler, $waitTimeout = 0, $waitSleepTime = 1, $lockExpire = 3)
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
            $value = json_decode($this->handler->get($this->name), true);
            $this->lockValue = [
                'expire' => time() + $this->lockExpire,
                'guid'   => $this->guid,
            ];
            if (null === $value) {
                // 无值
                $result = $this->handler->setnx($this->name, json_encode($this->lockValue));
                if ($result) {
                    $this->handler->expire($this->name, $this->lockExpire);

                    return true;
                }
            } else {
                // 有值
                if ($value['expire'] < time()) {
                    $result = json_decode($this->handler->getSet($this->name, json_encode($this->lockValue)), true);
                    if ($result === $value) {
                        $this->handler->expire($this->name, $this->lockExpire);

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
            return $this->handler->del($this->name) > 0;
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
        $value = json_decode($this->handler->get($this->name), true);
        $this->lockValue = [
            'expire' => time() + $this->lockExpire,
            'guid'   => $this->guid,
        ];
        if (null === $value) {
            // 无值
            $result = $this->handler->setnx($this->name, json_encode($this->lockValue));
            if (!$result) {
                return false;
            }
        } else {
            // 有值
            if ($value < time()) {
                $result = json_decode($this->handler->getSet($this->name, json_encode($this->lockValue)), true);
                if ($result !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 关闭锁对象
     *
     * @return void
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
