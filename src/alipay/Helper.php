<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\alipay;

class Helper
{
    const POST_CHARSET = 'UTF-8';

    /**
     * 注册树池子
     * @var null
     */
    protected static $objects = null;

    /**
     * 将子分类对象挂在树上
     */
    public static function set()
    {
        self::$objects = config('alipay');
    }

    /**
     * 从树上获取子分类对象，如果没有则注册
     *
     * @param $key
     * @return mixed
     */
    public static function get($key = null)
    {
        if (!isset(self::$objects)) {
            self::set();
        }

        return is_null($key) ? self::$objects : self::$objects[$key];
    }

    /**
     * 生成签名
     *
     * @param $params
     * @return string
     */
    public static function generateSign($params): string
    {
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n".
            wordwrap(self::get('private_key'), 64, "\n", true).
            "\n-----END RSA PRIVATE KEY-----";

        openssl_sign(self::getSignContent($params), $sign, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($sign);
    }

    /**
     * 验签
     *
     * @param  array  $data
     * @return bool
     */
    public static function verifySign(array $data): bool
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n"
            .wordwrap(self::get('ali_public_key'), 64, "\n", true)
            ."\n-----END PUBLIC KEY-----";

        return 1 === openssl_verify(
                self::getSignContent($data, true),
                base64_decode($data['sign']),
                $publicKey,
                OPENSSL_ALGO_SHA256
            );
    }

    /**
     * post 方式调起支付 api
     *
     * @param $gatewayUrl
     * @param $params
     * @return array|bool|mixed|string
     */
    public static function post($gatewayUrl, $params)
    {
        return self::postCurl($gatewayUrl, http_build_query($params));
    }

    /**
     * 过滤查询行为参数
     *
     * @param  array  $params
     * @param  mixed  $order
     * @return array
     */
    public static function filterTradeQueryParams(array $params, $order): array
    {
        unset($params['notify_url'], $params['return_url']);

        $params['method'] = 'alipay.trade.query';
        $params['biz_content'] = json_encode(is_array($order) ? $order : ['trade_no' => $order]);
        $params['sign'] = Helper::generateSign($params);

        return $params;
    }

    /**
     * 过滤退款行为参数
     *
     * @param  array  $params
     * @param  array  $order
     * @return array
     */
    public static function filterRefundParams(array $params, $order): array
    {
//        unset($params['notify_url'], $params['return_url']);
        unset($params['return_url']);
        $params['method'] = 'alipay.trade.refund';
        $params['biz_content'] = json_encode($order);
        $params['sign'] = Helper::generateSign($params);

        return $params;
    }

    /**
     * 以 post 方式提交 xml 到对应的 url
     *
     * @param $url
     * @param $params
     * @param  int  $second
     * @return bool|string
     */
    protected static function postCurl($url, $params, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //设置header
        $headers = ['content-type: application/x-www-form-urlencoded;charset='.self::POST_CHARSET];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    /**
     * 获取签名内容
     *
     * @param $data
     * @param  bool  $verify
     * @return string
     */
    private static function getSignContent($data, $verify = false)
    {
        ksort($data);

        $stringToBeSigned = '';
        foreach ($data as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k.'='.$v.'&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k.'='.$v.'&';
            }
        }

        return trim($stringToBeSigned, '&');
    }
}
