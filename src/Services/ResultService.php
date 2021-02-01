<?php
/**
 * 支付结果查询服务层
 *
 * User: sun.yaopeng
 * Date: 2019/12/6
 */

namespace Shibapipi\Pay\Services;

class ResultService extends Service
{
    public static function check()
    {
        // TODO: 后期根据需要，考虑增加向支付机构调起查询的接口查询支付结果
        // Todo::是否已经出现了重复支付
        // TODO: 如果手动调起支付查询，并且支付成功，
        // TODO：需要判断订单标新在的状态是否为未支付，并更新订单表
        // TODO：（可能回调此时已经收到，并且已经修改完毕订单状态甚至已发货）
    }
}
