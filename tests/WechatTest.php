<?php
/**
 * 微信支付测试类
 * User: sun.yaopeng
 * Date: 2019/12/4
 */

namespace Shibapipi\Pay;

use Exception;
use Shibapipi\Pay\Interfaces\PayInterface;

class WechatTest implements PayInterface
{
    /**
     * 支付行为
     * @param $gateway
     * @param $params
     */
    public function pay($gateway, $params)
    {
        // PC
        echo json_encode(Pay::wechat()->web([
            'out_trade_no' => time(),
            'total_fee' => '1',
            'body' => 'test body - 测试',
        ]));

//        // M
//        echo json_encode(Pay::wechat()->wap([
//            'out_trade_no' => time(),
//            'total_fee' => '1',
//            'body' => 'test body - 测试',
//        ]));

//        // APP
//        echo json_encode(Pay::wechat()->app([
//            'out_trade_no' => time(),
//            'total_fee' => '1',
//            'body' => 'test body - 测试',
//        ]));
    }

    /**
     * 回调行为
     */
    public function notify()
    {
        echo json_encode(Pay::wechat()->notify());
    }

    /**
     * 查询行为
     * @param $order
     * @throws Exception
     */
    public function tradeQuery($order)
    {
        /**
         * 查询 app 支付，第一个参数请加上 'type' => 'app'
         */
        // 查询支付
        echo json_encode(Pay::wechat()->tradeQuery([
            'transaction_id' => '2019120422001452141000029531',
//            'type' => 'app'
        ]));

        // 查询退款，tradeQuery 第二个参数传 'refund'
        echo json_encode(Pay::wechat()->tradeQuery([
            'refund_id' => '2019120422001452141000029531',
//            'type' => 'app'
        ], 'refund'));
    }

    /**
     * 退款行为
     * @param $order
     * @throws Exception
     */
    public function refund($order)
    {
        /**
         * 进行 app 支付退款，第一个参数请加上 'type' => 'app'
         */
        echo json_encode(Pay::wechat()->refund([
            'transaction_id' => '1514192025',
            'out_refund_no' => time(),
            'total_fee' => '1',
            'refund_fee' => '1',
            'refund_desc' => '退款原因：doge测试退款',
//            'type' => 'app'
        ]));
    }
}
