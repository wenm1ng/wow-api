<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-01 12:08
 */
namespace User\Models;

use App\Common\EasyModel;

class WowUserPushModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_user_push';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}