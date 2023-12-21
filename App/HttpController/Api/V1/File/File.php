<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-19 10:36
 */
namespace App\HttpController\Api\V1\File;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use App\Work\Common\File as FileService;

class File extends LoginController
{
    public function uploadImageToBlog(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            $url = $params['url'] ?? '';
            return (new FileService())->uploadImageToBlog($url);
        });
    }

    public function uploadImage(){
        return $this->apiResponse(function (){
            $params = $this->getRequestJsonData();
            return (new FileService())->uploadImage($params);
        });
    }
}