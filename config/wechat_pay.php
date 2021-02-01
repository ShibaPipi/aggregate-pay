<?php
/**
 * 微信支付配置
 * User: sun.yaopeng
 * Date: 2019/11/29
 */

return [
    // 公众平台 应用号 appid
    'app_id' => 'wxd5852274f2898a64',
    // 开放平台 应用号 appid
    'appid' => 'wxd5852274f2898a64',
    // 商户平台 商户号 mchid
    'mch_id' => '1566875871',
    // 回调地址
    'base_gateway_url' => 'https://api.mch.weixin.qq.com/',
    'gateway_order' => 'pay/unifiedorder',
    'gateway_query' => 'pay/orderquery',
    'gateway_close' => 'pay/closeorder',
    'gateway_refund' => 'secapi/pay/refund',
    'notify_url' => '',
    'key' => 'VIJ85LLJ86en9gs8E0Yxco0bpE6NY82T',
    'cert_client' => '',
    'cert_key' => '',
];
