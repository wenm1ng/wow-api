<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-10-06 11:16
 */
namespace App\Work\Common\Models;

use App\Common\EasyModel;

class WowToolChildModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_tool_child';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}