<?php
/**
 * 银联支付相关配置参数
 */
return [
    //sandbox 地址
    'appId' => 'up_fchy6l3nnrad_qi9p',
    'appSecret' => '9cf4981e334f1a1cab7042fb0aece12d',
    //报文版本号，固定5.1.0，请勿改动
    'version' => '5.1.0',
    //签名方式，证书方式固定01，请勿改动
    'signMethod' => '01',
    //测试商户号
    'merId' => '777290058178717',
    //pc wab交易请求地址
    'gateway_url' => "https://gateway.test.95516.com/gateway/api/frontTransReq.do",
    //app交易请求地址
    'app_gateway_url' => "https://gateway.test.95516.com/gateway/api/appTransReq.do",
    //退款请求地址
    'refund_url' => "https://gateway.test.95516.com/gateway/api/backTransReq.do",
    //保证证书文件有读取权限
    'signCertPath' => '',
    //'signCertPath' => '/srv/www/lsshop/upacp_demo_b2c/assets/测试环境证书/acp_test_sign.pfx',
    //签名证书密码，测试环境固定000000，生产环境请修改为从cfca下载的正式证书的密码，正式环境证书密码位数需小于等于6位，否则上传到商户服务网站会失败
    'signCertPwd' => '000000',
    //后台通知地址
    'backUrl' => '',
    //前台通知地址
    'frontUrl' => '',
    //加密证书配置
    // 敏感信息加密证书路径(商户号开通了商户对敏感信息加密的权限，需要对 卡号accNo，pin和phoneNo，cvn2，expired加密（如果这些上送的话），对敏感信息加密使用)
    'encryptCertPath' => '',
    //验签证书配置
    //验签中级证书
    'middleCertPath' => '',
    //验签根证书
    'rootCertPath' => '',
    //是否验证验签证书的CN，测试环境请设置false，生产环境请设置true。非false的值默认都当true处理。
    'ifValidateCNName' => false
];
