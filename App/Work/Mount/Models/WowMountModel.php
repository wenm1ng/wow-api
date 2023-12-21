<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-16 14:22
 */
namespace App\Work\Mount\Models;

use App\Common\EasyModel;

class WowMountModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_mount';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}