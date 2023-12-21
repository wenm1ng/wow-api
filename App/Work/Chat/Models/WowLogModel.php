<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-25 11:35
 */
namespace App\Work\Chat\Models;

use App\Common\EasyModel;

class WowLogModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_log';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}