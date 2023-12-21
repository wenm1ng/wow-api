<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-28 13:58
 */
namespace App\Work\HelpCenter\Models;

use App\Common\EasyModel;

class WowHelpCenterModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_help_center';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}