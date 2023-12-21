<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-16 23:34
 */
namespace App\HttpController\Api\V1\User;

use Common\Common;
use Common\CodeKey;
use App\HttpController\LoginController;
use User\Service\UserService;
use User\Service\LeaderBoardService;
use User\Service\CommonService;

class Login extends LoginController
{

    /**
     * @desc       　用户手动拉取在线商品（异步）
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function saveUserInfo()
    {

        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $userService = new UserService();
            $result = $userService->saveUserInfo($params);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage().'_'.$e->getFile().'_'.$e->getCode();
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       获取用户收藏、评论数
     * @author     文明<736038880@qq.com>
     * @date       2022-07-13 9:48
     * @return bool
     */
    public function getNum(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new UserService())->getNum($params);
        });
    }

    /**
     * @desc       获取用户未读消息数量
     * @example
     * @return bool
     */
    public function getMessage(){
        return $this->apiResponse(function (){
            return (new UserService())->getMessage();
        });
    }

    /**
     * @desc       回去推送模板id
     * @author     文明<736038880@qq.com>
     * @date       2022-09-01 14:38
     * @return bool
     */
    public function getModelId(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new CommonService())->getModelId($params);
        });
    }

    /**
     * @desc       排行榜列表
     * @author     文明<736038880@qq.com>
     * @date       2022-09-12 10:44
     * @return bool
     */
    public function getLeaderBoardList(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new LeaderBoardService())->getList($params);
        });
    }
}