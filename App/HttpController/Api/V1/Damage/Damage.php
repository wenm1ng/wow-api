<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-10-12 15:01
 */
namespace App\HttpController\Api\V1\Damage;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use Damage\Service\DamageService;

class Damage extends LoginController
{
    /**
     * @desc        获取用户信息
     * @example
     * @return bool
     */
    public function test(){
        $rs = CodeKey::result();
        try {
            $version = Common::getHttpParams($this->request(), 'version');
            $damageService = new DamageService();
            $result = $damageService->test();
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }
}