<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-08-02 16:22
 */
namespace App\HttpController\Api\V1\HelpCenter;

use App\HttpController\BaseController;
use Common\Common;
use Common\CodeKey;
use App\Work\HelpCenter\Service\HelpCenterService;

class HelpCenterL extends BaseController
{
    /**
     * @desc       发布求助
     * @author     文明<736038880@qq.com>
     * @date       2022-08-02 16:23
     * @return bool
     */
    public function addHelp(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->addHelp($params,  $this->request());
        });
    }

    /**
     * @desc       采纳答案
     * @author     文明<736038880@qq.com>
     * @date       2022-08-06 16:24
     * @return bool
     */
    public function adoptAnswer(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->adoptAnswer($params);
        });
    }

    /**
     * @desc       提交回答
     * @author     文明<736038880@qq.com>
     * @date       2022-08-06 16:29
     * @return bool
     */
    public function setAnswerStatus(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->setAnswerStatus($params);
        });
    }

    /**
     * @desc       修改求助回答
     * @author     文明<736038880@qq.com>
     * @date       2022-08-06 16:31
     * @return bool
     */
    public function updateAnswer(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->updateAnswer($params);
        });
    }

    /**
     * @desc       添加求助回答
     * @author     文明<736038880@qq.com>
     * @date       2022-08-06 16:32
     * @return bool
     */
    public function addAnswer(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->addAnswer($params, $this->request());
        });
    }

    /**
     * @desc       删除求助
     * @author     文明<736038880@qq.com>
     * @date       2022-08-06 16:33
     * @return bool
     */
    public function deleteHelp(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->deleteHelp($params);
        });
    }

    /**
     * @desc    删除回答
     * @example
     * @return bool
     */
    public function delAnswer(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->delAnswer($params);
        });
    }

    /**
     * @desc       获取用户自己的回答列表
     * @author     文明<736038880@qq.com>
     * @date       2022-08-31 15:02
     * @return bool
     */
    public function getUserAnswerList(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->getAnswerList($params, 1);
        });
    }

    /**
     * @desc       获取用户自己的帮助列表
     * @author     文明<736038880@qq.com>
     * @date       2022-08-31 15:02
     * @return bool
     */
    public function getUserHelpList(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new HelpCenterService())->getHelpList($params, 1);
        });
    }
}