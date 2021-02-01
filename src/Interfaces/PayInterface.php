<?php
/**
 * 支付通用接口
 * User: sun.yaopeng
 * Date: 2019/11/29
 */

namespace Shibapipi\Pay\Interfaces;

interface PayInterface
{
    /**
     * 支付行为
     * @param $gateway
     * @param $params
     * @return mixed
     */
    public function pay($gateway, $params);

    /**
     * 回调行为
     * @return mixed
     */
    public function notify();

    /**
     * 查询行为
     * @param $order
     * @return mixed
     */
    public function tradeQuery($order);

    /**
     * 退款行为
     * @param $order
     * @return mixed
     */
    public function refund($order);
}
