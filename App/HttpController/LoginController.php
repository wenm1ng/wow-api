<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-20 23:51
 */
namespace App\HttpController;
use EasySwoole\Http\AbstractInterface\Controller;
use Common\CodeKey;
use User\Service\LoginService;
use Common\Common;
use App\Exceptions\CommonException;

Class LoginController extends CommonController {

    protected function onRequest(?string $action): ?bool
    {
        return $this->commonRequest($action, 0);
    }
}