<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 11:14
 */
namespace Occupation\Models;

use App\Common\EasyModel;

class WowOccupationModelNew extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_occupation';

    protected $primaryKey = 'wo_id';

    protected $keyType = 'int';
}