<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-20 23:30
 */
namespace User\Models;

use EasySwoole\ORM\AbstractModel;

/**
 * 用户商品模型
 * Class UserShop
 */
class WowUserModel extends AbstractModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_user';
    protected $primary = 'user_id';

}