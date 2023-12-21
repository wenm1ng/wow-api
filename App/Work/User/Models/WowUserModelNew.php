<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-09 11:10
 */
namespace User\Models;

use App\Common\EasyModel;

class WowUserModelNew extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_user';

    protected $primaryKey = 'wu_id';

    protected $keyType = 'int';
}