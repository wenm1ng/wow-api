<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 10:50
 */
namespace Wa\Models;

use App\Common\EasyModel;

class WowWaImageModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_wa_image';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}