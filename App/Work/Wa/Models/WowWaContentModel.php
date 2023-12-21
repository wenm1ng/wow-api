<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 10:43
 */
namespace Wa\Models;

use App\Common\EasyModel;

class WowWaContentModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_wa_content';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}