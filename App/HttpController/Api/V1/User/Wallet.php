<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-05 11:53
 */
namespace App\HttpController\Api\V1\User;

use App\HttpController\BaseController;
use User\Service\WalletService;

class Wallet extends BaseController
{
    /**
     * @desc       　获取用户余额
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return bool
     */
    public function getMoney()
    {
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new WalletService())->getMoney($params);
        });
    }
}