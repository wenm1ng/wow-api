<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-10-08 18:01
 */
namespace Talent\Validator;
use Common\Common;
use EasySwoole\Validate\Validate as systemValidate;

class TalentValidator extends systemValidate
{
    public function checkSaveUserTalent()
    {
        $this->addColumn('openId')->notEmpty('用户信息不能为空');
        $this->addColumn('version')->notEmpty('版本号不能为空');
        $this->addColumn('oc')->notEmpty('职业不能为空');
        $this->addColumn('type')->notEmpty('类型不能为空');
        $this->addColumn('title')->notEmpty('天赋标题不能为空');
        $this->addColumn('statis')->notEmpty('天赋点数未用完');
        $this->addColumn('points')->notEmpty('天赋点数未用完');
        $this->addColumn('actPoints')->notEmpty('天赋点数未用完');
        $this->addColumn('talent_ids')->notEmpty('天赋技能不能为空');
    }

    public function checkgetTalentHallList(){
        $this->addColumn('version')->notEmpty('版本号不能为空');
        $this->addColumn('oc')->notEmpty('职业不能为空');
    }

    public function checkCreateComment(){
        $this->addColumn('version')->notEmpty('版本号不能为空');
        $this->addColumn('wut_id')->notEmpty('天赋id不能为空');
        $this->addColumn('user_id')->notEmpty('用户id不能为空');
        $this->addColumn('content')->notEmpty('请输入评论内容');
    }

    public function checkGetTalentCommentList(){
        $this->addColumn('version')->notEmpty('版本号不能为空');
        $this->addColumn('wut_id')->notEmpty('天赋id不能为空');

    }
}