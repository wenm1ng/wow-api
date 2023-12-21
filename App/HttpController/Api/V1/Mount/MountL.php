<?php
/*
 * @desc       坐骑相关抽奖
 * @author     文明<736038880@qq.com>
 * @date       2022-09-20 14:01
 */
namespace App\HttpController\Api\V1\Mount;

use App\HttpController\BaseController;
use Common\Common;
use Common\CodeKey;
use App\Work\Mount\Service\MountService;

class MountL extends BaseController
{
    /**
     * @desc       进行抽奖
     * @author     文明<736038880@qq.com>
     * @date       2022-09-20 16:51
     * @return bool
     */
    public function doLottery(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new MountService())->doLottery($params);
        });
    }

    /**
     * @desc       获取抽奖记录日志列表
     * @author     文明<736038880@qq.com>
     * @date       2022-09-21 16:09
     * @return bool
     */
    public function getLotteryLogList(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new MountService())->getLotteryLogList($params);
        });
    }
}