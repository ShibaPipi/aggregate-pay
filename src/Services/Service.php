<?php
/**
 *
 * Created By 皮神
 * Date: 2021/2/1
 */

namespace Shibapipi\Pay\Services;

class Service
{
    protected static $instance;

    /**
     * 防止类被外部实例化
     */
    private function __construct()
    {
    }

    /**
     * 防止类被外部克隆
     */
    private function __clone()
    {
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }
        static::$instance = new static;
        return static::$instance;
    }
}
