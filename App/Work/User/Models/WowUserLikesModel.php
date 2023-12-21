<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-09 16:40
 */
namespace User\Models;

use App\Common\EasyModel;

class WowUserLikesModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_user_likes';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}