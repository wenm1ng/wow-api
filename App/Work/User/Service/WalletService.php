<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-05 11:54
 */
namespace User\Service;

/**
 * UserService不要去掉会报错
 */

use Common\Common;
use User\Validator\UserValidate;
use App\Work\WxPay\Models\WowUserWalletModel;
use App\Work\WxPay\Models\WowOrderLogModel;
use App\Exceptions\CommonException;
use App\Work\Config;
use App\Utility\Database\Db;

class WalletService
{
    protected $validator;
    public function __construct($token = "")
    {
        $this->validator = new UserValidate();
    }

    /**
     * @desc       获取用户余额
     * @author     文明<736038880@qq.com>
     * @date       2022-09-05 13:12
     * @param array $params
     *
     * @return array
     */
    public function getMoney(array $params){
        $this->validator->checkGetMoney();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        $money = WowUserWalletModel::getMoney($params['type'], Common::getUserId());
        return ['money' => $money];
    }

    /**
     * @desc       获取幸运币
     * @author     文明<736038880@qq.com>
     * @date       2022-09-28 18:10
     * @return array
     */
    public function getLuckyCoin(){
        $luckyCoin = WowUserWalletModel::query()->where('user_id', Common::getUserId())->value('lucky_coin');
        return ['lucky_coin' => $luckyCoin];
    }

    /**
     * @desc       获取所有币值
     * @author     文明<736038880@qq.com>
     * @date       2022-09-29 10:33
     * @return array|int[]
     */
    public function getCoin(){
        $info = WowUserWalletModel::query()->where('user_id', Common::getUserId())->first(['lucky_coin','money']);
        $return = ['lucky_coin' => 0, 'money' => 0];
        if(empty($info)){
            return $return;
        }
        $info = $info->toArray();
        return ['lucky_coin' => $info['lucky_coin'], 'money' => $info['money'], 'rate' => 100];
    }

    /**
     * @desc       金额操作及记录相关日志
     * @author     文明<736038880@qq.com>
     * @date       2022-09-08 16:16
     * @param float $money
     * @param int   $userId
     * @param int   $type
     */
    public function operateMoney(float $money, int $userId, int $type, $helpId = 0){
        WowUserWalletModel::incrementMoney($money, $userId);
        //记录订单日志
        $logData = [
            'order_type' => 1, //1帮币
            'order_id' => date('YmdHis').getRandomStr(18),
            'wx_order_id' => '',
            'date_month' => date('Y-m'),
            'pay_type' => $type, //2发布求助
            'user_id' => $userId,
            'success_at' => date('Y-m-d H:i:s'),
            'amount' => $money,
            'help_id' => $helpId
        ];
        WowOrderLogModel::query()->insert($logData);
    }

    /**
     * @desc       转换币种
     * @author     文明<736038880@qq.com>
     * @date       2022-09-22 13:12
     * @param $params
     *
     * @return array
     */
    public function transformMoney($params){
//        int $originType, int $transformType, int $transformMoney
        $this->validator->checkTransformMoney();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        $userId = Common::getUserId();
        //替换类型字段映射
        $transformLink = [
            1 => 'money',
            2 => 'lucky_coin'
        ];
        $nowMoney = WowUserWalletModel::query()->where('user_id', $userId)->value($transformLink[$params['origin_type']]);
        $nowMoney = $nowMoney * Config::MONEY_CHANGE_LUCKY_RATE;
        if($nowMoney < $params['transform_money']){
            CommonException::msgException('帮币余额不足');
        }
        try{
            DB::beginTransaction();
            //替换类型倍数映射
            $resultMoney = $params['transform_money'] / Config::MONEY_CHANGE_LUCKY_RATE;
            $updateData = [
                $transformLink[$params['origin_type']] => Db::raw("`{$transformLink[$params['origin_type']]}` - {$resultMoney}"),
                $transformLink[$params['transform_type']] => Db::raw("`{$transformLink[$params['transform_type']]}` + ".$params['transform_money']),
            ];
            WowUserWalletModel::query()->where('user_id', $userId)->update($updateData);
            //扣减帮币
            WowOrderLogModel::addSimpleOrderLog($params['origin_type'], 7, $userId, $resultMoney);
            //添加幸运币
            WowOrderLogModel::addSimpleOrderLog($params['transform_type'], 6, $userId, $params['transform_money']);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Common::log($e->getMessage().'_'.$e->getFile().'_'.$e->getLine(), 'sqlTransaction');
            CommonException::msgException('系统错误');
        }
        return [];
    }
}