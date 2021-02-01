<?php
/**
 * 支付退款行为服务层
 * User: sun.yaopeng
 * Date: 2019/12/12
 */

namespace Shibapipi\Pay\Services;

use Shibapipi\Pay\Pay;

class RefundService extends Service
{
    /**
     * 支付请求参数
     * @var array
     */
    protected $post = [];

    /**
     * 订单 id
     * @var string
     */
    protected $orderId = '';

    /**
     * 根据规则生成退款单号，调起退款
     * @var string
     */
    protected $refundId = '';

    /**
     * 支付机构返回的退款流水号，便于查询退款，微信返回 refund_id 字段，支付宝不返回
     * @var string
     */
    protected $refundNo = '';

    /**
     * 支付流水信息
     * @var mixed|null
     */
    protected $paymentInfo = null;

    /**
     * 支付方式：支付宝，微信
     * @var string
     */
    protected $payType = '';

    /**
     * 支付来源
     * @var string
     */
    protected $from = '';

    /**
     * 单次退款金额
     * @var string
     */
    protected $returnPrice = '';

    /**
     * 退款时间
     * @var string
     */
    protected $dateReturn = '';

    /**
     * 执行退款相关逻辑
     *
     * @return mixed
     */
    public function execute()
    {
        if (null !== $this->paymentInfo) {
            return Pay::{$this->payType}()->refund($this->buildRequestParams());
        }

        return false;
    }

    /**
     * 构造请求参数
     *
     * @return array
     */
    protected function buildRequestParams()
    {
        // TODO

        return [];
    }

    /**
     * @param  array|string  $refundInfo
     * @return bool
     */
    protected function result($refundInfo): bool
    {
        switch ($this->payType) {
            case 'alipay':
                $refundInfo = json_decode($refundInfo, true)['alipay_trade_refund_response'];
                if (10000 == $refundInfo['code'] && 'Success' == $refundInfo['msg']) {
                    return true;
                }
                break;
            case 'wechat':
                if ('SUCCESS' == $refundInfo['return_code'] && 'SUCCESS' == $refundInfo['result_code']) {
                    return true;
                }
                break;
            case "unionpay":
                if ($refundInfo['respCode'] == '00') {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * 生成退款单号
     *
     * @return string
     */
    private static function generateId(): string
    {
        return 'refund-'.date('YmsHis').str_pad(mt_rand(000, 999), 3, '0', STR_PAD_LEFT);
    }
}
