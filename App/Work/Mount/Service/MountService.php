<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-20 14:06
 */
namespace App\Work\Mount\Service;

use App\Work\Validator\MountValidator;
use App\Exceptions\CommonException;
use User\Service\CommonService;
use App\Work\Mount\Models\WowMountModel;
use App\Work\Mount\Models\WowMountLogModel;
use Common\Common;
use App\Utility\Database\Db;
use App\Work\Common\Lottery;
use App\Work\Config;
use App\Work\WxPay\Models\WowUserWalletModel;
use Common\CodeKey;
use App\Work\WxPay\Models\WowOrderLogModel;

class MountService
{
    protected $validator;
    //扣减的幸运币
    protected $reduceCoin = 0;

    public function __construct()
    {
        $this->validator = new MountValidator();
    }

    /**
     * @desc       获取坐骑列表
     * @author     文明<736038880@qq.com>
     * @date       2022-09-20 14:33
     * @param array $params
     *
     * @return array
     */
    public function getList(array $params)
    {
//        $this->validator->checkPage();
//        if (!$this->validator->validate($params)) {
//            CommonException::msgException($this->validator->getError()->__toString());
//        }
        $where = [
            'where' => [
                ['status', '=', 1]
            ]
        ];
        if (!empty($params['name'])) {
            $where['where'][] = ['name', 'like', "%{$params['name']}%"];
        }
        if (!empty($params['order']) && !empty($params['sort'])) {
            if($params['order'] !== 'rate'){
                CommonException::msgException('排序参数有误');
            }
            if(!in_array($params['sort'], ['desc','asc'])){
                CommonException::msgException('排序参数有误');
            }
            $where['order'] = [$params['order'] => $params['sort'], 'id' => 'desc'];
        } else {
            $where['order'] = ['rate' => 'asc', 'id' => 'desc'];
        }
        $fields = 'id,name,image_url,rate';
        $list = WowMountModel::baseQuery($where)->select(Db::raw($fields))->get()->toArray();

        return ['list' => $list];
    }

    /**
     * @desc       进行坐骑抽奖
     * @author     文明<736038880@qq.com>
     * @date       2022-09-20 16:51
     * @param array $params
     *
     * @return array
     */
    public function doLottery(array $params){
        $this->validator->checkLottery($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
//        dump($params);
        //检查幸运币数量
        $userId = Common::getUserId();
//        $luckyCoin = WowUserWalletModel::query()->where('user_id', $userId)->value('lucky_coin');
//        $this->reduceCoin = $params['type'] == 1 ? 1 : 9;
//        if($luckyCoin < $this->reduceCoin){
//            CommonException::msgException('幸运币不足', CodeKey::COIN_NOT_ENOUGH);
//        }

        $where = [
            'where' => [
                ['status', '=', 1]
            ]
        ];
        if(empty($params['is_all'])){
            $where['whereIn'][] = ['id', $params['id']];
            $list = redis()->hMGet(Config::REDIS_KEY_MOUNT_LIST, $params['id']);
        }else{
            $list = redis()->hGetAll(Config::REDIS_KEY_MOUNT_LIST);
        }

        if(empty($list)){
            $fields = 'id,name,description,image_url,rate';
            $list = WowMountModel::baseQuery($where)->select(DB::raw($fields))->get()->toArray();
            $jsonList = [];
            foreach ($list as $val) {
                $jsonList[$val['id']] = json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            redis()->hMSet(Config::REDIS_KEY_MOUNT_LIST, $jsonList);
        }else{
            foreach ($list as $key => $val) {
                if(empty($val)){
                    unset($list[$key]);
                    continue;
                }
                $list[$key] = json_decode($list[$key], true);
            }
            $list = array_values($list);
        }

        $count = count($list);
        $return = [];
        if($params['type'] == 1){
            //单抽
            $randNum = mt_rand(1, $count);
            $return[] = Lottery::doDraw($list[$randNum-1]['name'], $list[$randNum-1]['rate'], $list[$randNum-1]['image_url'], $list[$randNum-1]['id']);
        }else{
            //9连抽
            for ($i = 0; $i < 9; $i++) {
                $randNum = mt_rand(1, $count);
                $return[] = Lottery::doDraw($list[$randNum-1]['name'], $list[$randNum-1]['rate'], $list[$randNum-1]['image_url'], $list[$randNum-1]['id']);
            }
        }
        //测试代码
//        foreach ($return as $key => &$val) {
//            if(in_array($key, [0,1,3])){
//                $val['is_bingo'] = 1;
//                $val['image_url'] = 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg';
//                $val['name'] = '奥利瑟拉佐尔的烈焰之爪';
//                continue;
//            }
//            $val['is_bingo'] = 0;
//        }
//        $return = [
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 1,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 1,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 0,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 0,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 0,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 0,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 0,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 0,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//            [
//                'id' => 604,
//                'name' => 'test',
//                'image_url' => 'https://mingtongct.com/images/mount/argusfelstalkermountred.jpg',
//                'is_bingo' => 0,
//                'is_open' => 0,
//                'title' => '点击开刷',
//                'is_show_image' => 0
//            ],
//        ];
        $this->addLotteryLog($return);
        $return = ['list' => $return, 'lucky_coin' => $params['type'] == 1 ? 1 : 9];
        return $return;
    }

    /**
     * @desc       记录抽奖日志
     * @author     文明<736038880@qq.com>
     * @date       2022-09-21 10:51
     * @param array $params
     *
     * @return bool
     */
    private function addLotteryLog(array $params){
        $mountIds = array_filter(array_column($params, 'id'));
        if(empty($mountIds)){
            return false;
        }

        try{
            DB::beginTransaction();
            $userId = Common::getUserId();
            $list = WowMountLogModel::query()->whereIn('mount_id', $mountIds)->where('user_id', $userId)->select(DB::raw('id,mount_id,times,suc_times_record,suc_times'))->get()->toArray();
            $list = array_column($list, null, 'mount_id');
            $insertData = [];
            foreach ($params as $val) {
                $list[$val['id']]['times'] = (!empty($list[$val['id']]['times']) ? $list[$val['id']]['times'] : 0) + 1;
                $list[$val['id']]['suc_times'] = (!empty($list[$val['id']]['suc_times']) ? $list[$val['id']]['suc_times'] : 0) + ($val['is_bingo'] ? 1 : 0);
                if(!isset($list[$val['id']]['suc_times_record'])){
                    //新增
                    $insertData[$val['id']] = [
                        'user_id' => $userId,
                        'mount_id' => $val['id'],
                        'times' => 1,
                        'suc_times' => $val['is_bingo'] ? 1 : 0,
                        'suc_times_record' => $val['is_bingo'] ? ',1' : '',
                    ];
                    $list[$val['id']]['suc_times_record'] = $insertData[$val['id']]['suc_times_record'];
                    continue;
                }
                //此次抽奖出现多次 DB没有记录的坐骑
                if(isset($insertData[$val['id']])){
                    $insertData[$val['id']]['times'] = $list[$val['id']]['times'];
                    $insertData[$val['id']]['suc_times'] = $list[$val['id']]['suc_times'];
                    if($val['is_bingo']){
                        $insertData[$val['id']]['suc_times_record'] .= ','. $list[$val['id']]['times'];
                    }
                    continue;
                }
                //编辑
                $list[$val['id']]['suc_times_record'] .= ','.$list[$val['id']]['times'];
                $updateData = [
                    'times' => DB::raw('times + 1'),
                ];
                if($val['is_bingo']){
                    $updateData['suc_times_record'] = DB::raw('concat(suc_times_record, ",", '.$list[$val['id']]['times'].')');
                    $updateData['suc_times'] = DB::raw('suc_times + 1');
                }
                WowMountLogModel::query()->where('id', $list[$val['id']]['id'])->update($updateData);
            }
            if(!empty($insertData)){
                $insertData = array_values($insertData);
                WowMountLogModel::query()->insert($insertData);
            }
//            //扣减幸运币
//            WowUserWalletModel::incrementLuckyCoin(-$this->reduceCoin, $userId);
//            //添加幸运币扣减日志
//            $logData = [
//                'order_type' => 2, //2幸运币
//                'order_id' => date('YmdHis').getRandomStr(18),
//                'wx_order_id' => '',
//                'date_month' => date('Y-m'),
//                'pay_type' => 5, //5幸运币扣减
//                'user_id' => !empty($userId) ? $userId : 0,
//                'success_at' => date('Y-m-d H:i:s'),
//                'amount' => $this->reduceCoin
//            ];
//            WowOrderLogModel::query()->insert($logData);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Common::log($e->getMessage().'_'.$e->getFile().'_'.$e->getLine(), 'sqlTransaction');
            CommonException::msgException('系统错误');
        }
        return true;
    }

    /**
     * @desc       获取坐骑抽奖记录列表
     * @author     文明<736038880@qq.com>
     * @date       2022-09-21 16:06
     * @param array $params
     *
     * @return array
     */
    public function getLotteryLogList(array $params){
        $this->validator->checkPage();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        $userId = Common::getUserId();
        $where = [
            'where' => [
                ['user_id', '=', $userId]
            ]
        ];

        if(!empty($params['name'])){
            $where['where'][] = ['m.name', 'like', "%{$params['name']}%"];
        }
        if (!empty($params['order']) && !empty($params['sort'])) {
            if(!in_array($params['order'], ['times','suc_times','rate'])){
                CommonException::msgException('排序参数有误');
            }
            if(!in_array($params['sort'], ['desc','asc'])){
                CommonException::msgException('排序参数有误');
            }
            $where['order'] = [$params['order'] => $params['sort'], 'l.id' => 'desc'];

            if($params['order'] === 'suc_times'){
                $where['order'] = [$params['order'] => $params['sort'], 'times' => 'desc', 'l.id' => 'desc'];
            }
        } else {
            $where['order'] = ['times' => 'desc','suc_times' => 'desc', 'l.id' => 'desc'];
        }
        $fields = 'l.id,l.mount_id,l.times,l.suc_times_record,l.suc_times,m.image_url,m.name';
        $list = WowMountLogModel::baseQuery($where)->from('wow_mount_log as l')
            ->leftJoin('wow_mount as m', 'l.mount_id', 'm.id')
            ->select(Db::raw($fields))
            ->limit($params['pageSize'])->offset($params['pageSize'] * ($params['page'] - 1))
            ->get()->toArray();

        return ['list' => $list, 'page' => $params['page']];
    }
}