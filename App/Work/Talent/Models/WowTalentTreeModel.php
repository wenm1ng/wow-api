<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-10-08 16:21
 */
namespace Talent\Models;

use EasySwoole\ORM\AbstractModel;

/**
 * 用户商品模型
 * Class UserShop
 */
class WowTalentTreeModel extends AbstractModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_talent_tree';
    protected $primary = 'wtr_id';

}