<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-11-18 19:57
 */
namespace Talent\Models;

use App\Work\BaseModel;
/**
 * 用户天赋模型
 */
class WowUserTalentCommentModel extends BaseModel
{
    /**
     * @var string
     */
    protected $tableName = 'wow_user_talent_comment';
    protected $primary = 'comment_id';
}