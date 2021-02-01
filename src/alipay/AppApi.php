<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\alipay;

class AppApi extends BaseApi
{
    /**
     * 支付行为
     *
     * @param $params
     * @return array|string
     */
    public function pay($params)
    {
        return http_build_query(parent::pay($params));
    }

    /**
     * 获取支付平台支付 api 名称
     *
     * @return string
     */
    protected function getMethod(): string
    {
        return 'alipay.trade.app.pay';
    }

    /**
     * 获取支付平台销售产品码
     *
     * @return string
     */
    protected function getProductCode(): string
    {
        return 'QUICK_MSECURITY_PAY';
    }
}
