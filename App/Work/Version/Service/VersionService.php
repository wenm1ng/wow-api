<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-21 21:09
 */
namespace Version\Service;

use Common\Common;
use Version\Models\WowVersionModelNew;

class VersionService
{

    protected $versionModel;

    public function getVersionList(){
//        $versionList = redis()->get('version_list');
//        if(!empty($versionList)){
//            $versionList = json_decode($versionList, true);
//            return $versionList;
//        }
//        $versionList = WowVersionModelNew::query()->get()->toArray();
//        redis()->set('version_list', json_encode($versionList), 3600);
        $versionList = [
            ['wv_id' => 4, 'version' => 4, 'name' => 'WLK'],
            ['wv_id' => 1, 'version' => 1, 'name' => '正式服'],
            ['wv_id' => 2, 'version' => 3, 'name' => 'TBC'],
            ['wv_id' => 3, 'version' => 2, 'name' => '经典旧世'],
        ];
        return $versionList;
    }
}