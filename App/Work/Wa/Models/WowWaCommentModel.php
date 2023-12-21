<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-06-09 11:51
 */
namespace Wa\Models;

use App\Common\EasyModel;

class WowWaCommentModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_wa_comment';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}