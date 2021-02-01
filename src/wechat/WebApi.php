<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\wechat;

use Exception;

class WebApi extends BaseApi
{
    /**
     * 支付行为
     *
     * @param $params
     * @return mixed
     *
     * @throws Exception
     */
    public function pay($params)
    {
        return Helper::post($this->gatewayUrl, parent::pay($params));
    }

    /**
     * 获取支付平台支付方式
     * @return string
     */
    protected function getTradeType(): string
    {
        return 'NATIVE';
    }
}
