<?php
/**
 * 支付服务层
 *
 * User: sun.yaopeng
 * Date: 2019/9/19
 */

namespace Shibapipi\Pay\Services;

use Bee\Logger;
use model\Payment as PaymentModel;
use model\WebsiteSwitch;
use stdClass;
use Shibapipi\Pay\Pay as DogePay;
use model\Order as OrderModel;
use model\BuyMemberOrder as BuyMemberOrderModel;
use Exception;
use Url;

class PayService extends Common
{
    /*
     * 二维码生成工具 url
     */
    const QR_CODE_URL = 'https://cli.im/api/qrcode/code?text=';

    protected $url;

    /**
     * 用户 id
     * @var int|string
     */
    protected $customerId;

    /**
     * 支付请求参数
     * @var array
     */
    protected $input;

    /**
     * 订单 id
     * @var string
     */
    protected $orderId;

    /**
     * 支付方式
     * @var string
     */
    protected $paymentType;

    /**
     * 支付请求单号，用于调起支付宝、微信支付等
     * @var string
     */
    protected $paymentId;

    /**
     * @var int 返回结果 code
     */
    protected $code = 0;

    /**
     * @var string 返回信息
     */
    protected $msg = '';

    /**
     * @var array 返回数据
     */
    protected $data = [];

    public function __construct($languageId, $customerId, $input)
    {
        parent::__construct($languageId);

        $this->url = new Url();
        $this->customerId = $customerId;
        $this->input = $input;
        $this->orderId = $input['order_id'];
        $this->paymentType = $input['payment_type'];
        $this->generatePaymentId();
    }

    /**
     * 支付行为
     * @param $apiName
     * @return $this
     */
    public function execute($apiName)
    {
        $this->getLanguageData('pay/pay');

        if ($this->orderId && $this->paymentType) {
            $orderInfo = self::getOrderInfo($this->customerId, $this->orderId);
            if (null === $orderInfo) {
                $this->msg = $this->languageData['lack_order'];
            } elseif ('1' != $orderInfo['order_status_id']) {
                $this->msg = $this->languageData['order_cannot_pay'];
                $this->code = 404;
            } elseif ($orderInfo['paid_price'] <= 0) {
                $this->msg = $this->languageData['order_without_pay'];
                $this->code = 404;
            } elseif (PaymentModel::where([
                    ['order_id', $this->orderId],
                    ['target_id', $this->customerId],
                    ['status', 'success']
                ])->count() > 0) {
                // 订单已经支付
                $this->msg = $this->languageData['order_has_paid'];
                $this->code = 404;
            } else {
                try {
                    $payment = PaymentModel::where([
                        ['payment_id', $this->paymentId],
                        ['order_id', $this->orderId],
                        ['target_id', $this->customerId],
                        ['pay_type', $this->paymentType]
                    ])->first();
                    if (null === $payment) {
                        $this->createPayment($orderInfo);
                    }
                    $this->data['order_id'] = $this->orderId;
                    $this->data['paid_price'] = $orderInfo['paid_price'];
                    $this->data['paymentInfo'] = DogePay::{$this->input['payment_type']}()->{$apiName}($this->buildRequestParams($orderInfo));
                    $this->code = 1;
                } catch (Exception $e) {
                    $this->msg = $e->getMessage();
                }
            }
        } else {
            $this->msg = $this->languageData['lack_order_id_or_payment_type'];
        }

        return $this;
    }

    /**
     * PC 支付
     *
     * @return mixed
     */
    public function responseForWeb()
    {
        if (1 == $this->code) {
            $data['paymentInfo'] = $this->data['paymentInfo'];

            if ('wechat' === $this->paymentType) {
                $view = 'account/wx_pay_order';
                $data['order_id'] = $this->orderId;
                $data['paid_price'] = $this->data['paid_price'];
                $data['paymentInfo'] = $this->data['paymentInfo']['code_url'];
                $data['logo'] = (new Login())->getLogo();
                $data['home'] = $this->url->newLink();
            } else {
                $view = 'account/ali_pay_order';
            }

            return compact('view', 'data');
        } elseif (105 == $this->code) {
            headerExit($this->url->newLink());
        } elseif (404 == $this->code) {
            headerExit($this->url->newLink('account/order'));
        } else {
            headerExit($this->url->newLink('error/not_found'));
        }

        return false;
    }

    /**
     * m 站支付
     *
     * @return mixed
     */
    public function responseForWap()
    {
        if (1 == $this->code) {
            if ('wechat' === $this->paymentType) {
                headerExit($this->data['paymentInfo']);
            } else {
                return $this->data;
            }
        } elseif (105 == $this->code) {
            headerExit($this->url->newLink('', 'wap'));
        } else {
            headerExit($this->url->newLink('error/not_found', 'wap'));
        }

        return false;
    }

    /**
     * App 支付
     *
     * @return array
     */
    public function responseForApp()
    {
        $data = [];

        if (1 == $this->code) {
            $data['alipayPaymentInfo'] = $data['unionpayPaymentInfo'] = '';
            $data['wechatPaymentInfo'] = new stdClass();
            $data['unionpayPaymentInfo'] = new stdClass();
            switch ($this->paymentType) {
                case 'wechat':
                    $data['wechatPaymentInfo'] = $this->data['paymentInfo'];
                    break;
                case 'alipay':
                    $data['alipayPaymentInfo'] = $this->data['paymentInfo'];
                    break;
                case 'unionpay':
                    $data['unionpayPaymentInfo'] = $this->data['paymentInfo'];
                    break;
            }
        }
        $code = (int)!$this->code;
        $msg = $this->msg;

        return compact('code', 'msg', 'data');
    }

    /**
     * 返回订单类型
     *
     * @param $orderId
     * @return string 商品购买，会员充值，...
     */
    public static function getOrderType($orderId)
    {
        if ('hy' === substr($orderId, 0, 2)) {
            $orderType = 'customer';
        } else {
            $orderType = 'order';
        }

        return $orderType;
    }


    /**
     * 返回订单信息
     * @param $customerId
     * @param $orderId
     * @return array
     */
    public static function getOrderInfo($customerId, $orderId)
    {
        $where = [
            ['order_id', $orderId],
            ['customer_id', $customerId]
        ];

        switch (self::getOrderType($orderId)) {
            case 'order':
                $orderInfo = OrderModel::where($where)->first(['order_id', 'order_status_id', 'paid_price', 'date_added']);
                break;
            case 'customer':
                $orderInfo = BuyMemberOrderModel::where($where)->first(['order_id', 'order_status_id', 'total', 'created_at']);
                break;
            default:
                $orderInfo = null;
        }
        if (null !== $orderInfo) {
            $orderInfo = $orderInfo->toArray();
            $orderInfo['paid_price'] = $orderInfo['total'] ?? $orderInfo['paid_price'];
            $orderInfo['paid_price'] = dealPrice($orderInfo['paid_price']);
        }

        return $orderInfo;
    }

    /**
     * 新增一条支付记录
     * @param $orderInfo
     * @return bool
     */
    protected function createPayment($orderInfo)
    {
        $payment = new PaymentModel();

        $payment->payment_id = $this->paymentId;
        $payment->order_id = $this->orderId;
        $payment->target_type = 1; // 固定设置为 1
        $payment->target_id = $this->customerId;
        $payment->trade_type = 1;
        $payment->pay_amount = $orderInfo['paid_price'];
        $payment->currency_type = 'CNY';
        $payment->exchange_rate = '0.0000';
        $payment->pay_type = $this->paymentType;
        $payment->pay_from = self::getPayFrom();
        $payment->status = 'ready';

        return $payment->save();
    }

    /**
     * 构造请求参数
     * @param $orderInfo
     * @return array
     */
    protected function buildRequestParams($orderInfo)
    {
        switch ($this->paymentType) {
            case 'alipay':
                $request['out_trade_no'] = $this->paymentId;
                $request['total_amount'] = $orderInfo['paid_price'];
                $request['subject'] = self::getOrderType($this->orderId) === 'customer' ? '购买会员' : '订单支付';
                break;
            case 'wechat':
                $request['out_trade_no'] = $this->paymentId;
                $request['total_fee'] = $orderInfo['paid_price'] * 100;
                $request['body'] = self::getOrderType($this->orderId) === 'customer' ? '购买会员' : '订单支付';
                break;
            case 'unionpay':
                $request['orderId'] = $this->paymentId;
                $request['txnAmt'] = $orderInfo['paid_price'] * 100;
                $request['body'] = self::getOrderType($this->orderId) === 'customer' ? '购买会员' : '订单支付';
                $request['createdAt'] = $orderInfo['created_at'];
                break;
        }

        return $request;
    }

    /**
     * 生成支付请求号
     */
    private function generatePaymentId()
    {
        $this->paymentId = $this->paymentType . $this->orderId;
    }

    private static function getPayFrom()
    {
        switch (getAppName()) {
            case 'catalog':
                $payFrom = 'pc';
                break;
            case 'api':
                $payFrom = 'app';
                break;
            case 'wap_app':
                $payFrom = 'wap';
                break;
            default:
                $payFrom = '';
                break;
        }

        return $payFrom;
    }

    /**
     * 选择支付方式，获取待支付订单信息
     *
     * @param $post
     * @return array
     */
    public static function selectType($customerId, $orderId)
    {
        $code = 1;
        $data = self::getOrderInfo($customerId, $orderId);
        $route = '';

        if (null === $data) {
            $data = [];
            $code = 0;
        } else {
            $paymentInfo = (new WebsiteSwitch)->getWebsiteSwitch([
                'unionPayPayment',
                'alipay',
                'weChatPayment'
            ]);

            $data['paymentInfo'] = array_combine(
                array_column($paymentInfo, 'code'),
                array_column($paymentInfo, 'status')
            );
        }

        return compact('code', 'data', 'route');
    }
}
