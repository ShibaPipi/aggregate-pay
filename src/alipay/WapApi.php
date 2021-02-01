<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\alipay;

class WapApi extends WebApi
{
    /**
     * 获取支付平台支付 api 名称
     * @return string
     */
    protected function getMethod(): string
    {
        return 'alipay.trade.wap.pay';
    }

    /**
     * 获取支付平台销售产品码
     * @return string
     */
    protected function getProductCode(): string
    {
        return 'QUICK_WAP_WAY';
    }
}
