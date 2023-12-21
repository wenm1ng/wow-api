<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-09 10:26
 */
namespace Talent\Models;

use App\Common\EasyModel;
use App\Utility\Database\Db;

class WowTalentModelNew extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_talent';

    protected $primaryKey = 'wt_id';

    protected $keyType = 'int';

    /**
     * @desc       　获取天赋列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param int    $version 版本
     * @param string $oc 职业
     *
     * @return array
     */
    public static function getTalentByVersionOc(int $version, string $oc){
        return self::query()->where('version', $version)->where('occupation', $oc)->pluck('talent_name')->toArray();
    }
}