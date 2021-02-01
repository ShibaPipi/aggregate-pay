<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/11/29
 */

namespace Shibapipi\Pay;

use Exception;
use Shibapipi\Pay\Interfaces\PayInterface;
use Shibapipi\Pay\alipay\BaseApi;
use Shibapipi\Pay\alipay\Helper;

/**
 * Class Alipay
 * @package Shibapipi\Pay
 * @method web(array $params) PC 支付
 * @method wap(array $params) WAP 支付
 * @method app(array $params) APP 支付
 */
class Alipay implements PayInterface
{
    /**
     * 支付宝支付参数
     * @var array
     */
    protected $config;

    /**
     * 支付宝请求参数
     * @var array
     */
    protected $params;

    /**
     * Alipay constructor.
     */
    public function __construct()
    {
        $this->config = Helper::get();

        $this->params = [
            'app_id' => $this->config['app_id'],
            'method' => '',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'version' => '1.0',
//            'return_url' => $this->config['return_url'],
            'notify_url' => $this->config['notify_url'],
            'timestamp' => date('Y-m-d H:i:s'),
            'sign' => '',
            'biz_content' => '',
//            'app_auth_token' => $this->config['app_auth_token'] ?? '',
            'quit_url' => ''
        ];
    }

    /**
     * 魔术方法，调用支付 api
     * @param $method
     * @param $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        return $this->pay($method, ...$params);
    }

    /**
     * 支付行为
     * @param $gateway
     * @param $params
     * @return mixed
     */
    public function pay($gateway, $params)
    {
//        $this->params['return_url'] = $params['return_url'] ?? $this->params['return_url'];
        $this->params['notify_url'] = $params['notify_url'] ?? $this->params['notify_url'];

        unset($params['return_url'], $params['notify_url']);

        if (isset($_GET['order_id'])) {
            $params['quit_url'] = $this->params['quit_url'];
        }
        $this->params['biz_content'] = json_encode($params);

        $gateway = __NAMESPACE__.'\\alipay\\'.ucfirst($gateway).'Api';
        if (class_exists($gateway)) {
            $api = new $gateway();

            if ($api instanceof BaseApi) {
                return $api->pay($this->params);
            }
        }

        return false;
    }

    /**
     * 回调行为
     * @return mixed
     */
    public function notify()
    {
        $response = $GLOBALS['HTTP_RAW_POST_DATA'] ?? file_get_contents("php://input");

        parse_str($response, $data);

        if (isset($data['fund_bill_list'])) {
            $data['fund_bill_list'] = htmlspecialchars_decode($data['fund_bill_list']);
        }

        if (Helper::verifySign($data)) {
            return $data;
        }

        return false;
    }

    /**
     * 查询行为
     * @param  mixed  $order
     * @return array|bool|mixed|string
     * @throws Exception
     */
    public function tradeQuery($order)
    {
        return Helper::post(
            Helper::get('gateway_url'),
            Helper::filterTradeQueryParams($this->params, $order)
        );
    }

    /**
     * 退款行为
     * @param $order
     * @return array|bool|mixed|string
     */
    public function refund($order)
    {
        return Helper::post(
            Helper::get('gateway_url'),
            Helper::filterRefundParams($this->params, $order)
        );
    }
}
