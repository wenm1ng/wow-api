<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-10-06 15:01
 */
namespace App\Work\Common\Models;

use App\Common\EasyModel;

class WowMacroLogModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_macro_log';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}
