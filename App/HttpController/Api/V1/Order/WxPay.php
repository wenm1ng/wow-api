<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-09-04 21:14
 */

namespace App\HttpController\Api\V1\Order;

use App\Work\WxPay\Service\OrderService;
use App\HttpController\LoginController;

class WxPay extends LoginController
{
    public function wxPayCallback()
    {
        return $this->apiResponse(function () {
            $params = $this->getRequestJsonData();
            return (new OrderService())->wxPayCallback($params);
        });
    }
}