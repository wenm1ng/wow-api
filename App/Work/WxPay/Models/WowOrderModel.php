<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-09-03 22:29
 */
namespace App\Work\WxPay\Models;

use App\Common\EasyModel;

class WowOrderModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_order';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}