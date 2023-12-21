<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-15 14:05
 */
namespace App\Work\WxCallBack\Service;

use App\Exceptions\CommonException;
use Common\Common;

class WxCallBackService{

    /**
     * @desc       微信回调
     * @author     文明<736038880@qq.com>
     * @date       2022-09-15 16:51
     * @param array $params
     *
     * @return array
     */
    public function callBack(array $params){
//        include_once(EASYSWOOLE_ROOT . '/App/Work/Lib/WxCallBack/wxBizMsgCrypt.php');
//        if(!$this->checkParams($params)){
//            CommonException::msgException('参数有误');
//        }
        Common::log('wx_callback params:'.json_encode($params), 'wx_call_back');
//        return $params['echostr'];
        return [];
    }

    private function checkParams(array $params){
        $signature = $params["signature"];
        $timestamp = $params["timestamp"];
        $nonce = $params["nonce"];

        $token = config('app.CALLBACK_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if ($tmpStr == $signature ) {
            return true;
        } else {
            return false;
        }
    }
}