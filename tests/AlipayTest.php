<?php
/**
 * 支付测试类
 * User: sun.yaopeng
 * Date: 2019/12/4
 */

namespace Shibapipi\Pay\Tests;

use Exception;
use Shibapipi\Pay\Interfaces\PayInterface;

class AlipayTest implements PayInterface
{
    /**
     * 支付行为
     * @param $gateway
     * @param $params
     */
    public function pay($gateway, $params)
    {
        // PC
        echo Pay::alipay()->web([
            'out_trade_no' => time(),
            'total_amount' => '1',
            'subject' => 'test subject - 测试',
        ]);

//        // M
//        echo Pay::alipay()->wap([
//            'out_trade_no' => time(),
//            'total_amount' => '1',
//            'subject' => 'test subject - 测试',
//        ]);
//
//        // APP
//        echo Pay::alipay()->app([
//            'out_trade_no' => time(),
//            'total_amount' => '1',
//            'subject' => 'test subject - 测试',
//        ]);
    }

    /**
     * 回调行为
     */
    public function notify()
    {
        echo json_encode(Pay::alipay()->notify());
    }

    /**
     * 查询行为
     * @param $order
     * @throws Exception
     */
    public function tradeQuery($order)
    {
        echo Pay::alipay()->tradeQuery([
            'trade_no' => '2019120422001452141000029531'
        ]);
    }

    /**
     * 退款行为
     * @param $order
     */
    public function refund($order)
    {
        echo Pay::alipay()->refund([
            'trade_no' => '2019120422001452141000029531',
            'refund_amount' => '0.01'
        ]);
    }
}
