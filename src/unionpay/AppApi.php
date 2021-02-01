<?php
/**
 *银联app
 * User: 杨兰
 */

namespace Shibapipi\Pay\unionpay;

class AppApi extends BaseApi
{
    /**
     * 支付行为
     *
     * @param $params
     * @return mixed
     */
    public function pay($params)
    {
        $this->gatewayUrl = Helper::get('app_gateway_url');
        $result_arr = Helper::post(
            Helper::get('app_gateway_url'),
            parent::pay($params)
        );

        return Helper::dealCurlResult($result_arr, 'payment/payment.log');
    }

    /**
     * 获取支付平台支付 api 名称
     *
     * @return string
     */
    protected function getMethod(): string
    {
        return '';
    }

    /**
     * 获取支付平台销售产品码
     *
     * @return string
     */
    protected function getProductCode(): string
    {
        return '';
    }
}
