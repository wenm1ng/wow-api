<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-22 0:02
 */
namespace Occupation\Service;

use Common\Common;
use Occupation\Models\WowOccupationModel;
use Occupation\Models\WowOccupationModelNew;
use Talent\Models\WowTalentModelNew;

class OccupationService
{

    protected $occupationModel;

    public function __construct($token = "")
    {
        $this->occupationModel = new WowOccupationModel();
    }

    public function getOccupationList($version){
        if(empty($version)){
            throw new \Exception('版本信息不能为空');
        }
        $occupationList = redis()->get('occupation_list:'.$version);
        if(!empty($occupationList)){
            $occupationList = json_decode($occupationList, true);
            return $occupationList;
        }
        $occupationList = $this->occupationModel->where(['version' => (int)$version])->order(['sort' => 'ASC'])->all()->toArray();
        if(!empty($occupationList)){
            redis()->set('occupation_list:'.$version, json_encode($occupationList), 3600);
        }
        return $occupationList;
    }

    /**
     * @desc       　根据版本号获取职业列表（包含天赋信息）
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param int $version
     *
     * @return array
     */
    public function getOcListByVersion(int $version){
        $where = [
            'where' => [
                ['version', '=', $version]
            ]
        ];
        $ocList = WowOccupationModelNew::getList($where, 'version,occupation,1 as type,name as title,image_url,0 as tt_id');
        $talentList = WowTalentModelNew::getList($where, 'occupation,talent_name as description');
        $ocList = mergeList('occupation', 'occupation', $ocList, $talentList, 'description', true);
        return $ocList;
    }
}