<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-23 16:29
 */
namespace App\HttpController\Api\V1\Chat;

use App\HttpController\BaseController;
use App\Work\Chat\Service\ChatService;

class Chat extends BaseController
{
    /**
     * @desc        获取tab列表
     * @example
     * @return bool
     */
    public function getChatHistory()
    {
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new ChatService())->getChatHistory($params);
        });
    }

    /**
     * @desc       记录日志
     * @author     文明<736038880@qq.com>
     * @date       2022-07-25 11:31
     * @return bool
     */
    public function recordLog(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new ChatService())->recordLog($params);
        });
    }
}