<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-11-19 14:50
 */
namespace App\HttpController\Api\V1\Talent;

use App\HttpController\LoginController;
use App\HttpController\BaseController;
use Common\Common;
use Common\CodeKey;
use Talent\Service\TalentService;

class Comment extends LoginController
{
    /**
     * @desc       　进行评论、回复
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function createComment(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $talentService = new TalentService();
            $result = $talentService->createComment($params);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Throwable $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       　获取用户评论列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getTalentCommentList(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $talentService = new TalentService();
            $result = $talentService->getTalentCommentList($params);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Throwable $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       　删除评论
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function delComment(){
        $rs = CodeKey::result();
        try {
            $commentId = Common::getHttpParams($this->request(),'comment_id');
            $talentService = new TalentService();
            $result = $talentService->delComment($commentId);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Throwable $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }
}