<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-06-10 11:07
 */
namespace App\HttpController\Api\V1\Wa;

use App\HttpController\BaseController;
use Wa\Service\WaService;

class WaL extends BaseController
{
    /**
     * @desc        获取tab列表
     * @example
     * @return bool
     */
    public function toComment()
    {
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new WaService())->toComment($params);
        });
    }

    /**
     * @desc       　删除评论
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function delComment(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            $commentId = (int)($params['id'] ?? 0);
            return (new WaService())->delComment($commentId);
        });
    }

    /**
     * @desc       获取用户收藏wa列表
     * @author     文明<736038880@qq.com>
     * @date       2022-07-11 14:31
     * @return bool
     */
    public function getWaFavoritesList(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new WaService())->getWaFavoritesList($params);
        });
    }

    /**
     * @desc       获取用户所有wa评论
     * @author     文明<736038880@qq.com>
     * @date       2022-07-11 16:04
     * @return bool
     */
    public function getCommentAll(){
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new WaService())->getCommentAll($params);
        });
    }
}