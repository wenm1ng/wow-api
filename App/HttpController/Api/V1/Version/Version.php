<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-21 21:06
 */
namespace App\HttpController\Api\V1\Version;

use App\HttpController\LoginController;
use Common\Common;
use Common\CodeKey;
use Version\Service\VersionService;

class Version extends LoginController
{
    /**
     * @desc        获取用户信息
     * @example
     * @return bool
     */
    public function getVersionList(){
        $rs = CodeKey::result();
        try {
            $versionService = new VersionService();
            $result = $versionService->getVersionList();
            $rs[CodeKey::STATE] = CodeKey::SUCCESS;
            $rs[CodeKey::DATA] = $result;
        } catch (\Exception $e) {
            $rs[CodeKey::MSG] = $e->getMessage();
        }

        return $this->writeResultJson($rs);
    }
}