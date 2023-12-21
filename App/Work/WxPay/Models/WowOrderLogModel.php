<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-05 10:20
 */
namespace App\Work\WxPay\Models;

use App\Common\EasyModel;

class WowOrderLogModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_order_log';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    /**
     * @desc       简单创建订单和订单日志入库
     * @author     文明<736038880@qq.com>
     * @date       2022-09-22 13:09
     * @param int   $orderType
     * @param int   $payType
     * @param int   $userId
     * @param float $money
     */
    public static function addSimpleOrderLog(int $orderType, int $payType, int $userId, float $money){
        $orderId = date('YmdHis').getRandomStr(18);
        $insertData = [
            'type' => $orderType,
            'order_status' => 2,
            'order_money' => $money,
            'callback_json' => '',
            'wx_money' => 0,
            'order_id' => $orderId,
            'user_id' => $userId
        ];
        WowOrderModel::query()->insert($insertData);
        //记录订单日志
        $logData = [
            'order_type' => $orderType, //1帮币
            'order_id' => $orderId,
            'wx_order_id' => '',
            'date_month' => date('Y-m'),
            'pay_type' => $payType, //2发布求助
            'user_id' => $userId,
            'success_at' => date('Y-m-d H:i:s'),
            'amount' => $money
        ];
        self::query()->insert($logData);
    }
}