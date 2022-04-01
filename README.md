## 锁实例化

~~~
//文件锁
$lock = new \Qmister\Lock\FileLock('test');
$lock =  \Qmister\Lock\FileLock::getInstance('test');

//redis锁
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$lock =new \Qmister\Lock\RedisLock('test', $redis);
$lock = \Qmister\Lock\RedisLock::getInstance('test', $redis);

//memcache锁
$memcache = new \Memcache();
$memcache->connect('127.0.0.1', 11211, 120);
$lock =new \Qmister\Lock\MemcacheLock('test', $memcache);
$lock = \Qmister\Lock\MemcacheLock::getInstance('test', $memcache);

//memcached锁
$memcached = new \Memcached();
$memcached->addServer('127.0.0.1', 11211);
$memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
$lock = new \Qmister\Lock\MemcachedLock('test', $memcached);
$lock = \Qmister\Lock\MemcachedLock::getInstance('test', $memcached);
~~~

## 锁基本使用

~~~

if (\Qmister\Lock\Lock::LOCK_RESULT_SUCCESS === $lock->lock()) {
   // 加锁后处理的任务
   $lock->unlock();
}
~~~

## 锁高级使用

~~~
$result = $lock->lock(
    function(){
        // 加锁后处理的任务
    },
    function(){
        // 判断是否其它并发已经处理过任务
    }
);
switch($result)
{
    case \Qmister\Lock\Lock::LOCK_RESULT_CONCURRENT_COMPLETE:
        // 其它请求已处理
        break;
    case \Qmister\Lock\Lock::LOCK_RESULT_CONCURRENT_UNTREATED:
        // 在当前请求处理
        break;
    case \Qmister\Lock\Lock::LOCK_RESULT_FAIL:
        echo '获取锁失败', PHP_EOL;
        break;
}
~~~

