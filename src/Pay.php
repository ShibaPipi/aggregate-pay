<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/11/29
 */

namespace Shibapipi\Pay;

use Shibapipi\Pay\Interfaces\PayInterface;

/**
 * Class Pay
 * @package Shibapipi\Pay
 * @method static Alipay alipay
 * @method static Wechat wechat
 */
class Pay
{
    public static function __callStatic($method, $params)
    {
        $platform = __NAMESPACE__.'\\'.ucfirst($method);

        if (class_exists($platform)) {
            $app = new $platform();

            if ($app instanceof PayInterface) {
                return $app;
            }
        }

        return false;
    }
}
