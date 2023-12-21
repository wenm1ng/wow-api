<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-20 23:51
 */
namespace App\HttpController;

use App\Utility\Code;
use EasySwoole\Http\AbstractInterface\Controller;
use Common\CodeKey;
use Common\Common;
use User\Service\LoginService;
use App\Exceptions\CommonException;

class BaseController extends CommonController
{

    protected function onRequest(?string $action): ?bool
    {
        return $this->commonRequest($action);
    }
}