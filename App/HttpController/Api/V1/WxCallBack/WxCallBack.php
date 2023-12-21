<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-15 13:58
 */
namespace App\HttpController\Api\V1\WxCallBack;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use App\Work\WxCallBack\Service\WxCallBackService;

class WxCallBack extends LoginController
{
    public function callBack(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new WxCallBackService())->callBack($params);
        });
//        try{
//            $params = $this->getRequestJsonData();
//            $return = (new WxCallBackService())->callBack($params);
//        }catch (\Exception $e){
//
//        }
//        return $this->response()->write($return);
    }
}