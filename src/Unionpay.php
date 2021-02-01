<?php

namespace Shibapipi\Pay;

use Shibapipi\Pay\Interfaces\PayInterface;
use Shibapipi\Pay\unionpay\BaseApi;
use Shibapipi\Pay\unionpay\Helper;

class Unionpay implements PayInterface
{
    protected $config;

    protected $params;

    protected $refundLogChannel = 'payment/refund.log';

    /**
     *    非对称签名：
     * 01（表示采用RSA签名） HASH表示散列算法
     * 11：支持散列方式验证SHA-256
     * 12：支持散列方式验证SM3
     **/

    public function __construct()
    {
        $this->config = Helper::get();
        $this->params = [
            'merId' => $this->config['merId'],//商户代码
            'bizType' => '000201',//产品类型
            'encoding' => 'utf-8', //编码方式
            'version' => $this->config['version'], //版本号
            'txnTime' => date('YmdHis'),//订单发送时间
            'currencyCode' => '156',//交易币种
            'txnType' => '01',//交易类型
            'txnSubType' => '01',//交易子类
            'accessType' => '0',//接入类型 0 直连商户
            'signMethod' => $this->config['signMethod'], //签名方法
            'backUrl' => $this->config['backUrl'],
            'frontUrl' => $this->config['frontUrl'],
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

    public function notify()
    {
        // TODO: Implement notify() method.
    }

    public function pay($gateway, $params)
    {
        $this->params['orderId'] = $params['orderId'];
        $this->params['txnAmt'] = $params['txnAmt'];

        $channelType = '08';//手机端 app端
        if ($gateway == 'web') {//pc端
            $channelType = '07';
        }

        $this->params['channelType'] = $channelType;//渠道类型 07：PC/平板 08：手机

        // 订单超时时间。
        // 超过此时间后，除网银交易外，其他交易银联系统会拒绝受理，提示超时。 跳转银行网银交易如果超时后交易成功，会自动退款，大约5个工作日金额返还到持卡人账户。
        // 此时间建议取支付时的北京时间加15分钟。
        // 超过超时时间调查询接口应答origRespCode不是A6或者00的就可以判断为失败。
        $this->params['payTimeout'] = date('YmdHis',
            strtotime(date('YmdHis', $params['createdAt']).'+30 minutes'));//下单时间后的半小时后超时

        $this->params['riskRateInfo'] = '{commodityName='.$params['body'].'}';//购买商品 || 购买会员

        $gateway = __NAMESPACE__.'\\unionpay\\'.ucfirst($gateway).'Api';
        if (class_exists($gateway)) {
            $api = new $gateway();
            if ($api instanceof BaseApi) {
                return $api->pay($this->params);
            }
        }

        return false;
    }

    /**
     * 退款接口
     *
     * @param $order
     * @return mixed
     */
    public function refund($order)
    {
        unset($this->params['txnTime']);
        unset($this->params['frontUrl']);
        unset($this->params['txnType']);
        unset($this->params['currencyCode']);
        $this->params['txnType'] = '04';
        $this->params['txnSubType'] = '00'; //交易子类
        $result_arr = Helper::post(
            Helper::get('refund_url'),
            Helper::filterRefundParams($this->params, $order)
        );

        return Helper::dealCurlResult($result_arr, $this->refundLogChannel);
    }

    public function tradeQuery($order)
    {
        // TODO: Implement tradeQuery() method.
    }
}
