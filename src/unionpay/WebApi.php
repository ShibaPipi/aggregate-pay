<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/11/29
 */

namespace Shibapipi\Pay\unionpay;

class WebApi extends BaseApi
{
    /**
     * 支付行为
     * @param $params
     * @return mixed
     */
    public function pay($params)
    {
        return $this->buildRequestForm($this->gatewayUrl, parent::pay($params));
    }

    /**
     * 获取支付平台支付 api 名称
     * @return string
     */
    protected function getMethod()
    {
        //return 'alipay.trade.page.pay';
    }

    /**
     * 获取支付平台销售产品码
     * @return string
     */
    protected function getProductCode()
    {
        //return 'FAST_INSTANT_TRADE_PAY';
    }
}
