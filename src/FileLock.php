<?php


namespace Qmister\Lock;

use Exception;

class FileLock extends BaseLock
{
    use Singleton;
    /**
     * 锁文件.
     *
     * @var string
     */
    protected $fileLock;

    /**
     * File constructor.
     *
     * @param $name
     * @param null $filePath
     *
     * @throws Exception
     */
    public function __construct($name, $filePath = null)
    {
        $this->name = $name;
        $filePath = $filePath ?? sys_get_temp_dir().DIRECTORY_SEPARATOR.'Qmister-lock';
        $this->fileLock = $filePath.DIRECTORY_SEPARATOR.$this->name.'.lock';
        if (!is_file($this->fileLock)) {
            if (!is_dir($filePath)) {
                mkdir($filePath, 0755, true);
            }
            file_put_contents($this->fileLock, '');
        }
        if (is_null($this->handler)) {
            $this->handler = fopen($this->fileLock, 'w+');
            $this->isInHandler = true;
        }
        if (false === $this->handler) {
            throw new Exception("Open {$this->fileLock} failed", Lock::EXCEPTION_LOCKFILE_OPEN_FAIL);
        }
    }

    /**
     * 加锁
     *
     * @return bool
     */
    protected function __lock()
    {
        return flock($this->handler, LOCK_EX);
    }

    /**
     * 释放锁
     *
     * @return bool
     */
    protected function __unlock()
    {
        return flock($this->handler, LOCK_UN); // 解锁
    }

    /**
     * 不阻塞加锁
     *
     * @return bool
     */
    protected function __unblockLock()
    {
        return flock($this->handler, LOCK_EX | LOCK_NB);
    }

    /**
     * 关闭锁对象
     *
     * @return bool
     */
    protected function __close()
    {
        if (!is_null($this->handler)) {
            $result = fclose($this->handler);
            $this->handler = null;

            return $result;
        }
    }
}
