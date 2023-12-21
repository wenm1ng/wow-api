<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-21 21:08
 */
namespace Version\Models;

use EasySwoole\ORM\AbstractModel;

/**
 * 用户商品模型
 * Class UserShop
 */
class WowVersionModel extends AbstractModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_version';
    protected $primary = 'wv_id';

}