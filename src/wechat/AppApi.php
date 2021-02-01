<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/3
 */

namespace Shibapipi\Pay\wechat;

use Exception;

class AppApi extends WebApi
{
    /**
     * 支付行为
     *
     * @param $params
     * @return array
     *
     * @throws Exception
     */
    public function pay($params): array
    {
        $params['appid'] = Helper::get('appid');

        $request = [
            'appid' => $params['appid'],
            'partnerid' => $params['mch_id'],
            'prepayid' => parent::pay($params)['prepay_id'],
            'timestamp' => strval(time()),
            'noncestr' => Helper::getNonceStr(),
            'package' => 'Sign=WXPay',
        ];
        $request['sign'] = Helper::generateSign($request);

        return $request;
    }

    /**
     * 获取支付平台支付 api 名称
     *
     * @return string
     */
    protected function getTradeType(): string
    {
        return 'APP';
    }
}
