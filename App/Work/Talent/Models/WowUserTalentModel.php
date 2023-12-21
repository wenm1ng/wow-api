<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-10-09 15:06
 */
namespace Talent\Models;

use App\Work\BaseModel;
/**
 * 用户天赋模型
 */
class WowUserTalentModel extends BaseModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_user_talent';
    protected $primary = 'wut_id';
}