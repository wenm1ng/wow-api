<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 10:51
 */
namespace Wa\Models;

use App\Common\EasyModel;

class WowWaTabModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_wa_tab';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    /**
     * @desc       　获取开启中的tab列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return array
     */
    public static function getEnableList(int $version){
        return self::query()->where('version', $version)->where('status', 1)->get(['version','type','type_name'])->toArray();
    }
}