<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 11:56
 */
namespace App\HttpController\Api\V1\Wa;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use Wa\Service\WaService;

class Wa extends LoginController
{
    /**
     * @desc        获取tab列表
     * @example
     * @return bool
     */
    public function getTabList(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new WaService())->getTabList($params);
        });
    }

    /**
     * @desc       　获取wa列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getWaList(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new WaService())->getWaList($params);
        });
    }

    /**
     * @desc       　获取wa详情
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getWaInfo(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            $id = (int)($params['id'] ?? 0);
            return (new WaService())->getWaInfo($id);
        });
    }

    public function getLabels(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new WaService())->getLabels($params);
        });
    }

    /**
     * @desc       　获取wa评论列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getWaComment(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new WaService())->getWaComment($params);
        });
    }

    public function saveFiddlerData(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            //wa详情内容
            $params = json_decode($params['data'], true);
            if(!isset($params['response_data']['data']['last_version'])){
                return null;
            }
            return (new WaService())->saveFiddlerWaData($params);
        });
    }

    /**
     * @desc   采集数据转移
     * @return bool
     */
    public function savePythonWa(){
        return $this->apiResponse(function (){
            return WaService::savePythonWa();
        });
    }
}