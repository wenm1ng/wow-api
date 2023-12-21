<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-22 0:00
 */
namespace App\HttpController\Api\V1\Occupation;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use Occupation\Service\OccupationService;

class Occupation extends LoginController
{
    /**
     * @desc        获取用户信息
     * @example
     * @return bool
     */
    public function getOccupationList(){
        $rs = CodeKey::result();
        try {
            $version = Common::getHttpParams($this->request(), 'version');
            $occupationService = new OccupationService();
            $result = $occupationService->getOccupationList($version);
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }
}