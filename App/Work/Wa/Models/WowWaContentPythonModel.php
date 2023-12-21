<?php
/**
 * @desc
 * @author     文明<736038880@qq.com>
 * @date       2023-08-18 10:56
 */
namespace Wa\Models;

use App\Common\EasyModel;

class WowWaContentPythonModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_wa_content_python';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}