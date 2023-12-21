<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-10-11 11:53
 */
namespace Damage\Models;

use EasySwoole\ORM\AbstractModel;

/**
 * 用户商品模型
 * Class UserShop
 */
class WowSkillModel extends AbstractModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_skill';
    protected $primary = 'ws_id';

}