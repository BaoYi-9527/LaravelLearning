<?php

namespace App\Services;

/**
 * 单例
 */
class Singleton
{
    private static $instances = [];

    # 单例不允许通过构建函数或者克隆实现
    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    /**
     * Notes: 单例不允许反序列化
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton...');
    }

    public static function getInstance()
    {
        $subClass = static::class; # 后期静态绑定
        if (!isset(self::$instances[$subClass])) {
            # 此处使用 static 关键字而不是确切的类名
            # 是因为我们使用的是当前运行环境中的类，而不是基类，这样当继承自基类的子类被调用时就可以保证单例的实现
            self::$instances[$subClass] = new static();
        }
        return self::$instances[$subClass];
    }

}
