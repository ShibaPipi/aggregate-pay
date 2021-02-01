<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/11/29
 */

namespace Shibapipi\Pay;

use Exception;
use Shibapipi\Pay\Interfaces\PayInterface;
use Shibapipi\Pay\wechat\BaseApi;
use Shibapipi\Pay\wechat\Helper;

/**
 * Class Wechat
 * @package Shibapipi\Pay
 * @method web(array $params) PC 支付
 * @method wap(array $params) WAP 支付
 * @method app(array $params) APP 支付
 */
class Wechat implements PayInterface
{
    /**
     * 微信支付参数
     *
     * @var array
     */
    protected $config;

    /**
     * 微信支付请求参数
     *
     * @var array
     */
    protected $params;

    /**
     * Wechat constructor. 初始化微信支付配置，组合通用请求参数
     */
    public function __construct()
    {
        $this->config = Helper::get();

        $this->params = [
            'appid' => $this->config['app_id'],
            'mch_id' => $this->config['mch_id'],
            'nonce_str' => Helper::getNonceStr(),
            'notify_url' => $this->config['notify_url'],
            'sign' => '',
            'trade_type' => '',
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR']
        ];
    }

    /**
     * 调取不同的支付方式
     *
     * @param $method
     * @param $params
     * @return bool|mixed
     */
    public function __call($method, $params)
    {
        return $this->pay($method, ...$params);
    }

    /**
     * 实现调用
     *
     * @param $gateway
     * @param $params
     * @return bool|mixed
     */
    public function pay($gateway, $params)
    {
        $gateway = __NAMESPACE__.'\\wechat\\'.ucfirst($gateway).'Api';

        if (class_exists($gateway)) {
            $api = new $gateway;

            if ($api instanceof BaseApi) {
                return $api->pay(array_merge($this->params, $params));
            }
        }

        return false;
    }

    /**
     * 回调行为
     *
     * @param  bool  $refund
     * @return mixed
     */
    public function notify($refund = false)
    {
        $response = $GLOBALS['HTTP_RAW_POST_DATA'] ?? file_get_contents("php://input");

        $data = Helper::fromXml($response);

        if ($refund) {
            return array_merge(
                Helper::fromXml(Helper::decryptContents($data['req_info'])),
                $data
            );
        }

        if (Helper::generateSign($data) === $data['sign']) {
            return $data;
        }

        return false;
    }

    /**
     * 查询行为
     *
     * @param  mixed  $order
     * @param  string  $type
     * @return array|bool|mixed|string
     *
     * @throws Exception
     */
    public function tradeQuery($order, $type = '')
    {
        return Helper::post(
            Helper::get('base_gateway_url').Helper::get('gateway_query'),
            Helper::filterTradeQueryParams($this->params, $order, $type)
        );
    }

    /**
     * 退款行为
     *
     * @param $order
     * @return array|bool|mixed|string
     *
     * @throws Exception
     */
    public function refund($order)
    {
        return Helper::post(
            Helper::get('base_gateway_url').Helper::get('gateway_refund'),
            Helper::filterRefundParams($this->params, $order),
            true
        );
    }
}
