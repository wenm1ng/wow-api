<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-09-04 11:15
 */
namespace App\HttpController\Api\V1\Talent;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use Talent\Service\TalentService;

class Talent extends LoginController
{
    /**
     * @desc        获取用户信息
     * @example
     * @return bool
     */
    public function getTalentList(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $version = $params['version'] ?? 0;
            $oc = $params['oc'] ?? '';
            dump($params);
            $talentService = new TalentService();
            $result = $talentService->getTalentList($version, $oc);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        } catch(\Error $e){
            $rs[CodeKey::MSG] = handleErrorMsg($e);
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       　获取天赋技能树
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getTalentSkillTree(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $version = $params['version'] ?? 0;
            $talentId = $params['talent_id'] ?? 0;
            $oc = $params['oc'] ?? 0;
            $talentService = new TalentService();
            $result = $talentService->getTalentSkillTree($version, $talentId, $oc);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        } catch(\Error $e){
            $rs[CodeKey::MSG] = handleErrorMsg($e);
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       　添加用户天赋信息
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function addUserTalent(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $talentService = new TalentService();
            $result = $talentService->saveUserTalent($params);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        } catch(\Error $e){
            $rs[CodeKey::MSG] = handleErrorMsg($e);
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       　修改用户天赋信息
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function updateUserTalent(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $talentService = new TalentService();
            $result = $talentService->saveUserTalent($params);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        } catch(\Error $e){
            $rs[CodeKey::MSG] = handleErrorMsg($e);
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       　获取天赋大厅列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getTalentHallList(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $talentService = new TalentService();
            $result = $talentService->getTalentHallList($params);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Throwable $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }

    /**
     * @desc       　获取用户天赋列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getUserTalentList(){
        $rs = CodeKey::result();
        try {
            $params = Common::getHttpParams($this->request());
            $talentService = new TalentService();
            $result = $talentService->getUserTalentList($params);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Throwable $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }
}