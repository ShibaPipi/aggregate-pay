<?php
/**
 *
 * User: sun.yaopeng
 * Date: 2019/12/2
 */

namespace Shibapipi\Pay\wechat;

use Exception;

class Helper
{
    /**
     * 注册树池子
     *
     * @var null
     */
    protected static $objects = null;

    /**
     * 将子分类对象挂在树上
     */
    public static function set()
    {
        self::$objects = config('wechat');
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
     *
     * @return string
     */
    public static function generateSign($params): string
    {
        return strtoupper(md5(self::getSignContent($params).'&key='.self::get('key')));
    }

    /**
     * 获取签名内容
     *
     * @param $data
     *
     * @return string
     */
    public static function getSignContent($data): string
    {
        ksort($data);

        $stringToBeSigned = '';

        foreach ($data as $k => $v) {
            $stringToBeSigned .= ($k != 'sign' && $v != '' && !is_array($v)) ? $k.'='.$v.'&' : '';
        }

        return trim($stringToBeSigned, '&');
    }

    /**
     * 生成随机字符串
     * @param  int  $length
     * @return string
     */
    public static function getNonceStr($length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $str = '';

        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    /**
     * post 方式调起支付 api
     *
     * @param $gatewayUrl
     * @param $params
     * @param  bool  $useCert
     * @return array|bool|mixed|string
     *
     * @throws Exception
     */
    public static function post($gatewayUrl, $params, $useCert = false)
    {
        $result = self::postCurl($gatewayUrl, self::toXml($params), $useCert);
        $result = is_array($result) ? $result : self::fromXml($result);

        if (!isset($result['return_code']) || $result['return_code'] != 'SUCCESS') {
            throw new Exception(
                json_encode(compact('gatewayUrl', 'params', 'result'))
            );
        }

        if (isset($result['result_code']) && $result['result_code'] != 'SUCCESS') {
            // TODO:支付异常邮件通知
        }

        return $result;
    }

    /**
     * 将xml转为array
     *
     * @param $xml
     * @return mixed
     */
    public static function fromXml($xml)
    {
        libxml_disable_entity_loader(true);

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 解密内容
     *
     * @param $contents
     * @return string
     */
    public static function decryptContents($contents)
    {
        return openssl_decrypt(
            base64_decode($contents),
            'AES-256-ECB',
            md5(self::get('key')),
            OPENSSL_RAW_DATA
        );
    }

    /**
     * 过滤查询行为参数
     *
     * @param  array  $params
     * @param  mixed  $order
     * @param  string  $type
     * @return array
     */
    public static function filterTradeQueryParams(array $params, $order, string $type): array
    {
        if ('refund' == $type) {
            $params['refund_id'] = is_array($order) ? $order['refund_id'] : $order;
        } else {
            $params['transaction_id'] = is_array($order) ? $order['transaction_id'] : $order;
        }

        if ('app' == $order['type']) {
            $params['app_id'] = self::get('appid');
        }
        unset($params['notify_url'], $params['trade_type'], $params['spbill_create_ip']);

        $params['sign'] = Helper::generateSign($params);

        return $params;
    }

    /**
     * 过滤退款行为参数
     *
     * @param  array  $params
     * @param  $order
     * @return array
     */
    public static function filterRefundParams(array $params, $order): array
    {
        if (isset($order['type']) && 'app' == $order['type']) {
            $params['app_id'] = self::get('appid');
            unset($order['type']);
        }

        unset($params['trade_type'], $params['spbill_create_ip']);

        $params = array_merge($params, $order);
        $params['sign'] = Helper::generateSign($params);

        return $params;
    }

    /**
     * 以 post 方式提交 xml 到对应的 url
     *
     * @param $url
     * @param $params
     * @param  bool  $useCert
     * @param  int  $second
     * @return bool|string
     */
    protected static function postCurl($url, $params, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //使用证书：cert 与 key 分别属于两个.pem文件
        if (true === $useCert) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, self::get('cert_client'));
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, self::get('cert_key'));
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        //运行curl
        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    /**
     * 组合参数，生成 xml
     *
     * @param $data
     * @return string
     */
    protected static function toXml($data): string
    {
        $xml = '<xml>';

        foreach ($data as $key => $val) {
            $xml .= is_numeric($val)
                ? '<'.$key.'>'.$val.'</'.$key.'>'
                : '<'.$key.'><![CDATA['.$val.']]></'.$key.'>';
        }

        $xml .= '</xml>';

        return $xml;
    }
}
