<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\wechat;

abstract class BaseApi
{
    /**
     * 微信支付网关地址
     *
     * @var string
     */
    protected $gatewayUrl;

    /**
     * BaseApi constructor. 初始化微信支付 api 配置
     */
    public function __construct()
    {
        $this->gatewayUrl = Helper::get('base_gateway_url').Helper::get('gateway_order');
    }

    /**
     * 支付行为
     *
     * @param $params
     * @return mixed
     */
    public function pay($params)
    {
        $params['trade_type'] = $this->getTradeType();
        $params['out_trade_no'] = $params['trade_type'].$params['out_trade_no'];
        $params['sign'] = Helper::generateSign($params);

        return $params;
    }

    /**
     * 获取支付平台支付 api 名称
     *
     * @return string
     */
    abstract protected function getTradeType(): string;
}
