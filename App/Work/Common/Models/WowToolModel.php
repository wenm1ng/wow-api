<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-29 18:03
 */
namespace App\Work\Common\Models;

use App\Common\EasyModel;

class WowToolModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_tool';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}