<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-09 14:33
 */
namespace Wa\Models;

use App\Common\EasyModel;

class WowWaContentHistoryModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_wa_content_history';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}