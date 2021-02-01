<?php
/**
 *
 * User: Ye
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\unionpay;

abstract class BaseApi
{
    /**
     * 银联支付网关地址
     * @var string
     */
    protected $gatewayUrl;

    /**
     * BaseApi constructor. 初始化银联支付 api 配置
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
        Helper::generateSign($params);

        return $params;
    }

    /**
     * 获取支付平台支付 api 名称
     *
     * @return string
     */
    abstract protected function getMethod(): string;

    /**
     * 获取支付平台销售产品码
     *
     * @return string
     */
    abstract protected function getProductCode(): string;

    /**
     * 组合请求的 HTML
     *
     * @param $reqUrl
     * @param $params
     * @return string
     */
    protected static function buildRequestForm($reqUrl, $params): string
    {
        // <body onload="javascript:document.pay_form.submit();">
        $encodeType = isset($params ['encoding']) ? $params ['encoding'] : 'UTF-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$reqUrl}" method="post">
	
eot;
        foreach ($params as $key => $value) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }
}
