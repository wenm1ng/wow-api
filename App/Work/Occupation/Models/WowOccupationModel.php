<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-22 0:02
 */
namespace Occupation\Models;

use EasySwoole\ORM\AbstractModel;

/**
 * 用户商品模型
 * Class UserShop
 */
class WowOccupationModel extends AbstractModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_occupation';
    protected $primary = 'wo_id';

}