<?php

namespace App\Utility;

use CloudKit\Login\Models\SysUserChildModel;
use CloudKit\Package\Models\PackageUserRelationModel;
use EasySwoole\EasySwoole\Config;
use Swoole\Coroutine;
use Common\Common;
use EasySwoole\Spl\SplContextArray;

/**
 * @desc
 * @author   Huangbin <huangbin2018@qq.com>
 * @date     2020/3/25 15:30
 * @package  App\Utility
 */
class Company
{
//    /**
//     * @desc 获取公司代码
//     * @return mixed
//     */
//    public static function getCompanyCode()
//    {
//        // $cid = Coroutine::getCid();
//        global $_COMPANY_INFO;
//        // echo var_export($_COMPANY_INFO['code'], true) . date('Y-m-d H:i:s') . PHP_EOL;
//        return $_COMPANY_INFO['code'];
//    }
//
//    /**
//     * @desc 设置公司代码
//     * @param string $companyCode
//     * @return mixed
//     */
//    public static function setCompanyCode($companyCode)
//    {
//        global $_COMPANY_INFO;
//        if(!$_COMPANY_INFO instanceof SplContextArray){
//            $_COMPANY_INFO = new SplContextArray();
//        }
//        $_COMPANY_INFO['code'] = $companyCode;
//    }

    /**
     * @desc 是否是saas环境
     * @return bool
     */
    public static function isSaaS()
    {
        global $_COMPANY_INFO;
        return $_COMPANY_INFO['is_saas'] ? true : false;
    }

    /**
     * @desc 设置saas环境
     * @param bool $isSass
     */
    public static function setSaaS($isSass = false)
    {
        global $_COMPANY_INFO;
        if(!$_COMPANY_INFO instanceof SplContextArray){
            $_COMPANY_INFO = new SplContextArray();
        }
        $_COMPANY_INFO['is_saas'] = $isSass;
    }

    /**
     * @desc 获取公司代理ID
     * @return mixed
     */
    public static function getAgentId()
    {
        // $cid = Coroutine::getCid();
        global $_COMPANY_INFO;
        // echo var_export($_COMPANY_INFO['code'], true) . date('Y-m-d H:i:s') . PHP_EOL;
        return $_COMPANY_INFO['code'];
    }

    /**
     * @desc 设置公司代理ID
     * @param string $companyCode
     * @return mixed
     */
    public static function setAgentId($userId)
    {
        global $_COMPANY_INFO;
        if(!$_COMPANY_INFO instanceof SplContextArray){
            $_COMPANY_INFO = new SplContextArray();
        }
        $agentId = Common::getAgentId();
        var_dump($agentId);
        if($agentId) {
            $_COMPANY_INFO['code'] = $agentId;
        } else {
            $redis = redis();
            var_dump($redis);
            $agentIdkey = 'listing_user_agentid_'.$userId;
            $agentId = $redis->get($agentIdkey);
            if(empty($agentId)) {
                $userPackRelation = new PackageUserRelationModel();
                $userChild = new SysUserChildModel();
                $childDetail = $userChild->getOneByWhere(['UserId' => $userId]);
                if($childDetail) {
                    $userId = $childDetail['ParentUserId'];
                }
                $userPackInfo = $userPackRelation->getOneByWhere(['user_id' => $userId], ['agent_id']);
                if ($userPackInfo) {
                    $agentId = $userPackInfo['agent_id'];
                }
                $redis->set($agentIdkey,$userPackInfo['agent_id'],3600);
            }
            $_COMPANY_INFO['code'] = $agentId;
        }
    }
}
