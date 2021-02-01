<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/3
 */

namespace Shibapipi\Pay\wechat;

use Exception;

class WapApi extends WebApi
{
    /**
     * 支付行为
     *
     * @param $params
     * @return string
     *
     * @throws Exception
     */
    public function pay($params): string
    {
        return parent::pay($params)['mweb_url'].'&redirect_url=';
    }

    /**
     * 获取支付平台支付 api 名称
     *
     * @return string
     */
    protected function getTradeType(): string
    {
        return 'MWEB';
    }
}
