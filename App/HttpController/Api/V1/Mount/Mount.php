<?php
/*
 * @desc       坐骑相关抽奖
 * @author     文明<736038880@qq.com>
 * @date       2022-09-20 14:01
 */
namespace App\HttpController\Api\V1\Mount;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use App\Work\Mount\Service\MountService;

class Mount extends LoginController
{
    /**
     * @desc       坐骑列表
     * @author     文明<736038880@qq.com>
     * @date       2022-09-20 16:23
     * @return bool
     */
    public function getList(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new MountService())->getList($params);
        });
    }
}