<?php

namespace Shibapipi\Pay\unionpay;

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
        self::$objects = config('unionpay');
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
     * 在线网关支付 固定使用01签名方式 勿改
     *
     * @param $params
     * @return string
     */
    public static function generateSign(&$params)
    {
//        if ($params['signMethod'] == '01') {
        return self::signByCertInfo($params, self::get('signCertPath'), self::get('signCertPwd'));
//        } else {
//            return self::signBySecureKey($params, self::get('secureKey'));
//        }
    }

    /**
     * 表示采用RSA签名
     *
     * @param $params
     * @param $cert_path
     * @param $cert_pwd
     * @return bool
     */
    private static function signByCertInfo(&$params, $cert_path, $cert_pwd)
    {
        if (isset($params['signature'])) {
            unset($params['signature']);
        }
        $result = false;
        if ($params['signMethod'] == '01') {
            //证书ID
            $params['certId'] = CertUtil::getSignCertIdFromPfx($cert_path, $cert_pwd);
            $private_key = CertUtil::getSignKeyFromPfx($cert_path, $cert_pwd);
            // 转换成key=val&串
            $params_str = self::createLinkString($params, true, false);
            if ($params['version'] == '5.0.0') {
                $params_sha1x16 = sha1($params_str, false);
                // 签名
                $result = openssl_sign($params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1);

                if ($result) {
                    $signature_base64 = base64_encode($signature);
                    $params['signature'] = $signature_base64;
                }
            } else {
                if ($params['version'] == '5.1.0') {
                    //sha256签名摘要
                    $params_sha256x16 = hash('sha256', $params_str);
                    // 签名
                    $result = openssl_sign($params_sha256x16, $signature, $private_key, 'sha256');
                    if ($result) {
                        $signature_base64 = base64_encode($signature);
                        $params['signature'] = $signature_base64;
                    } else {
                    }
                } else {
                    $result = false;
                }
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * 验签
     *
     * @param $params
     * @return
     */
    public static function validate($params)
    {
        $isSuccess = false;
        if ($params['signMethod'] == '01') {
            $signature_str = $params['signature'];
            unset ($params['signature']);
            $params_str = self::createLinkString($params, true, false);
            if ($params['version'] == '5.0.0') {
                // 公钥
                $public_key = CertUtil::getVerifyCertByCertId($params ['certId']);
                $signature = base64_decode($signature_str);
                $params_sha1x16 = sha1($params_str, false);
                $isSuccess = openssl_verify($params_sha1x16, $signature, $public_key, OPENSSL_ALGO_SHA1);

            } else {
                if ($params['version'] == '5.1.0') {
                    $strCert = $params['signPubKeyCert'];
                    $strCert = CertUtil::verifyAndGetVerifyCert($strCert);
                    if ($strCert == null) {
                        $isSuccess = false;
                    } else {
                        $params_sha256x16 = hash('sha256', $params_str);
                        $signature = base64_decode($signature_str);
                        $isSuccess = openssl_verify($params_sha256x16, $signature, $strCert, "sha256");
                    }
                } else {
                    $isSuccess = false;
                }
            }
        } else {
            $isSuccess = false;
        }

        return $isSuccess;
    }

    /**
     * 讲数组转换为string
     *
     * @param $para
     * @param $sort
     * @param $encode
     * @return string
     */
    public static function createLinkString($para, $sort, $encode)
    {
        if ($para == null || !is_array($para)) {
            return "";
        }

        $linkString = "";
        if ($sort) {
            $para = self::argSort($para);
        }
        foreach ($para as $key => $value) {
            if ($encode) {
                $value = urlencode($value);
            }
            $linkString .= $key."=".$value."&";
        }
        // 去掉最后一个&字符
        $linkString = substr($linkString, 0, -1);

        return $linkString;
    }

    private static function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 过滤退款行为参数
     *
     * @param  array  $params
     * @param  array  $order
     * @return mixed
     */
    public static function filterRefundParams($params, $order)
    {
        $newParams = array_merge($params, $order);
        $result = self::generateSign($newParams);

        return $newParams;
    }

    /**
     * 后台交易 HttpClient通信
     *
     * @param  $params
     * @param  $url
     * @return mixed
     */
    public static function post($url, $params)
    {
        $opts = self::createLinkString($params, false, true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不验证HOST
        curl_setopt($ch, CURLOPT_SSLVERSION,
            1); // http://php.net/manual/en/function.curl-setopt.php页面搜CURL_SSLVERSION_TLSv1
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type:application/x-www-form-urlencoded;charset=UTF-8'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($ch);
        if (curl_errno($ch)) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            return null;
        }
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != "200") {
            $errmsg = "http状态=".curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        $result_arr = self::convertStringToArray($html);
        return $result_arr;
    }

    /**
     * 处理curl返回结果
     *
     * @param  array  $result_arr  curl回结果
     * @param  string  $logChannel  curl回结果
     * @return void|array
     */
    public static function dealCurlResult($result_arr, $logChannel = '')
    {
        if (count($result_arr) <= 0) { //没收到200应答的情况
            return;
        }
        if (!self::validate($result_arr)) {
            //echo "应答报文验签失败<br>\n";
            return;
        }
        if ($result_arr["respCode"] == "00" || $result_arr["respCode"] == "45") {
            //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
            return $result_arr;
        } else {
            if ($result_arr["respCode"] == "03" || $result_arr["respCode"] == "04" || $result_arr["respCode"] == "05") {
                //后续需发起交易状态查询交易确定交易状态
                return;
            } else {
                //其他应答码做以失败处理
                return;
            }
        }
    }

    /**
     * 字符串转换为 数组
     *
     * @param  $str
     * @return
     */
    private static function convertStringToArray($str)
    {
        return self::parseQString($str);
    }

    /**
     * key1=value1&key2=value2转array
     * @param $str key1=value1&key2=value2的字符串
     * @param $$needUrlDecode 是否需要解url编码，默认不需要
     */
    private static function parseQString($str, $needUrlDecode = false)
    {
        $result = array();
        $len = strlen($str);
        $temp = "";
        $curChar = "";
        $key = "";
        $isKey = true;
        $isOpen = false;
        $openName = "\0";
        for ($i = 0; $i < $len; $i++) {
            $curChar = $str[$i];
            if ($isOpen) {
                if ($curChar == $openName) {
                    $isOpen = false;
                }
                $temp .= $curChar;
            } elseif ($curChar == "{") {
                $isOpen = true;
                $openName = "}";
                $temp .= $curChar;
            } elseif ($curChar == "[") {
                $isOpen = true;
                $openName = "]";
                $temp .= $curChar;
            } elseif ($isKey && $curChar == "=") {
                $key = $temp;
                $temp = "";
                $isKey = false;
            } elseif ($curChar == "&" && !$isOpen) {
                self::putKeyValueToDictionary($temp, $isKey, $key, $result, $needUrlDecode);
                $temp = "";
                $isKey = true;
            } else {
                $temp .= $curChar;
            }
        }
        self::putKeyValueToDictionary($temp, $isKey, $key, $result, $needUrlDecode);
        return $result;
    }

    /**
     * @param $temp
     * @param $isKey
     * @param $key
     * @param $result
     * @param $needUrlDecode
     * @return bool
     */
    private static function putKeyValueToDictionary($temp, $isKey, $key, &$result, $needUrlDecode)
    {
        if ($isKey) {
            $key = $temp;
            if (strlen($key) == 0) {
                return false;
            }
            $result [$key] = "";
        } else {
            if (strlen($key) == 0) {
                return false;
            }
            if ($needUrlDecode) {
                $result [$key] = urldecode($temp);
            } else {
                $result [$key] = $temp;
            }
        }
    }
}
