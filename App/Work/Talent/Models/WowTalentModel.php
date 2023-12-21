<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-09-04 11:19
 */
namespace Talent\Models;

use EasySwoole\ORM\AbstractModel;

/**
 * 用户商品模型
 * Class UserShop
 */
class WowTalentModel extends AbstractModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_talent';
    protected $primary = 'wt_id';

}