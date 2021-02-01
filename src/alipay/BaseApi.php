<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\alipay;

abstract class BaseApi
{
    /**
     * 支付宝网关地址
     *
     * @var string
     */
    protected $gatewayUrl;

    /**
     * BaseApi constructor. 初始化支付宝支付 api 配置
     */
    public function __construct()
    {
        $this->gatewayUrl = Helper::get('gateway_url');
    }

    /**
     * 支付行为
     *
     * @param $params
     * @return mixed
     */
    public function pay($params)
    {
        $params['method'] = $this->getMethod();
        switch ($params['method']) {
            case 'alipay.trade.wap.pay':
                // TODO： return_url
                $params['return_url'] = '';
                break;
            default:
                break;
        }
        $params['biz_content'] = json_encode(array_merge(
            json_decode($params['biz_content'], true),
            ['product_code' => $this->getProductCode()]
        ));
        $params['sign'] = Helper::generateSign($params);

        return $params;
    }

    /**
     * 获取支付平台支付 api 名称
     *
     * @return string
     */
    abstract protected function getMethod();

    /**
     * 获取支付平台销售产品码
     *
     * @return string
     */
    abstract protected function getProductCode();

    /**
     * 组合支付请求 HTML
     *
     * @param $gatewayUrl
     * @param $params
     * @return string
     */
    protected function buildRequestForm($gatewayUrl, $params): string
    {
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$gatewayUrl."' method='POST'>";
        foreach ($params as $key => $val) {
            $val = str_replace("'", '&apos;', $val);
            $sHtml .= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }
}
