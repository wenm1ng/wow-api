<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 11:15
 */
namespace Version\Models;

use App\Common\EasyModel;

class WowVersionModelNew extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_version';

    protected $primaryKey = 'wv_id';

    protected $keyType = 'int';

    /**
     * @desc       　获取版本列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @return array
     */
    public static function getList(){
        return self::query()->get()->toArray();
    }
}