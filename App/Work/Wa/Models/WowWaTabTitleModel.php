<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 10:52
 */
namespace Wa\Models;

use App\Common\EasyModel;

class WowWaTabTitleModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_wa_tab_title';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}