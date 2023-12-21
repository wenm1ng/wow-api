<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-09-03 22:31
 */
namespace App\Work\WxPay\Service;

use Common\Common;
use App\Work\WxPay\Models\WowOrderModel;
use App\Work\Validator\OrderValidator;
use App\Exceptions\CommonException;
use Common\CodeKey;
use App\Utility\Database\Db;
use App\Work\WxPay\Models\WowOrderLogModel;
use User\Models\WowUserModelNew;
use App\Work\WxPay\Models\WowUserWalletModel;

class OrderService{
    protected $validator;
    protected $logName = 'wxPayCallback';

    public function __construct()
    {
        $this->validator = new OrderValidator();
    }

    /**
     * @desc        创建订单
     * @example
     * @param array $params
     *
     * @return mixed
     */
    public function addOrder(array $params){
        $this->validator->checkAddOrder();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        $userInfo = Common::getUserInfo();
        $userId = $userInfo['user_id'];
//        $params['money'] = 0.01;
        $money = $params['money'] * 100;
        $outTradeNo = date('YmdHis').getRandomStr(18);
        $result = (new WxPayService())->wxAddOrder($money, $userInfo['openId'], $outTradeNo);
        if($result['code'] !== 200 || empty($result['data']['prepay_id'])){
            CommonException::msgException($result['message'], CodeKey::WXPAY_ERROR);
        }

        $insertData = [
            'type' => 1,
            'order_status' => 1,
            'order_money' => $params['money'],
            'callback_json' => '',
            'wx_money' => $money,
            'order_id' => $outTradeNo,
            'user_id' => $userId,
            'prepay_id' => $result['data']['prepay_id']
        ];
        WowOrderModel::query()->insert($insertData);

        $prepayId = $result['data']['prepay_id'];
        $returnData = WxPayService::getSign($prepayId);

        return $returnData;
    }

    /**
     * @desc       获取订单列表
     * @author     文明<736038880@qq.com>
     * @date       2022-09-05 15:38
     * @param array $params
     *
     * @return array
     */
    public function getLogList(array $params){
        $this->validator->checkPage();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        $where = [
            'where' => [
                ['user_id', '=', Common::getUserId()]
            ],
            'order' => ['success_at' => 'desc', 'id' => 'desc']
        ];

        if(!empty($params['pay_type'])){
            $where['where'][] = ['pay_type', '=', $params['pay_type']];
        }

        if(!empty($params['order_type'])){
            $where['where'][] = ['order_type', '=', $params['order_type']];
        }

        if(!empty($params['month'])){
            $where['where'][] = ['date_month', '=', $params['month']];
        }
        $fields = 'id,order_id,order_type,pay_type,amount,help_id,date_month,success_at';
        $list = WowOrderLogModel::getPageOrderList($where, $params['page'], $fields, $params['pageSize']);
        $count = count($list);
        $list = Common::arrayGroup($list, 'date_month');

        return ['list' => empty($list) ? false : $list, 'count' => $count];
    }

    /**
     * @desc        微信支付回调
     * @example
     * @param array $params
     *
     * @return array
     */
    public function wxPayCallback(array $params){
        Common::log('wxPayCallback params:'. json_encode($params), $this->logName);
        $returnJson = (new WxPayService())->decryptToString($params['resource']['associated_data'], $params['resource']['nonce'], $params['resource']['ciphertext']);
        Common::log('wxPayCallback response:'.$returnJson, $this->logName);
        $return = json_decode($returnJson, true);
        if(!is_array($return) || empty($return['out_trade_no'])){
            CommonException::msgException('签名错误');
        }
        $this->callbackUpdateOrder($return['out_trade_no'], $return['transaction_id'], $returnJson, $return['payer']['openid'], $return['amount']['total'], $return['amount']['payer_total'], $return['success_time']);
        return [];
    }

    /**
     * @desc        支付回调修改订单信息
     * @example
     * @param string $tradeNo
     * @param string $transactionId
     * @param string $callbackJson
     */
    public function callbackUpdateOrder(string $tradeNo, string $transactionId, string $callbackJson, string $openId, float $money, float $payerMoney, string $successTime){
        try{
            Db::beginTransaction();
            //修改订单状态
            $updateData = [
                'wx_order_id' => $transactionId,
                'callback_json' => $callbackJson,
                'order_status' => 2 //2支付成功
            ];
            WowOrderModel::query()->where('order_id', $tradeNo)->update($updateData);

            $userId = WowUserModelNew::query()->where('openId', $openId)->value('user_id');
            $trueMoney = round($payerMoney / 100, 2);
            //记录订单日志
            $logData = [
                'order_type' => 1, //1帮币
                'order_id' => $tradeNo,
                'wx_order_id' => $transactionId,
                'date_month' => date('Y-m', strtotime($successTime)),
                'pay_type' => 1, //1充值
                'user_id' => !empty($userId) ? $userId : 0,
                'success_at' => date('Y-m-d H:i:s', strtotime($successTime)),
                'amount' => $trueMoney
            ];
            WowOrderLogModel::query()->insert($logData);
            //添加账户余额
            WowUserWalletModel::incrementMoney($trueMoney, $userId);
            Db::commit();
        }catch (\Exception $e){
            Db::rollBack();
            Common::log('orderUpdate fail:'.$e->getMessage(), $this->logName);
            CommonException::msgException('订单回写失败');
        }

    }
}