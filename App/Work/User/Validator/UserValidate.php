<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-21 0:18
 */
namespace User\Validator;

use EasySwoole\Validate\Validate;

class UserValidate extends Validate
{
    public function permission() {
        $this->addColumn('functionId')->notEmpty();
        $this->addColumn('menuId')->notEmpty();
        $this->addColumn('functionName')->notEmpty();
        $this->addColumn('functionStatus')->notEmpty();
    }

    public function checkFavorites(){
        $this->addColumn('type')->notEmpty('类型不能为空');
        $this->addColumn('link_id')->notEmpty('id不能为空');
    }

    public function checkoutLikes(){
        $this->checkFavorites();
    }

    public function checkAddPushNum(){
        $this->addColumn('type')->notEmpty('类型不能为空');
        $this->addColumn('model_id')->notEmpty('模板id不能为空');
    }

    public function checkGetPushNum(){
        $this->addColumn('type')->notEmpty('类型不能为空');
    }

    public function checkPushWxMessage(){
        $this->addColumn('type')->notEmpty('类型不能为空');
        $this->addColumn('user_id')->notEmpty('用户id不能为空');
        $this->addColumn('help_id')->notEmpty('帮助id不能为空');
        $this->addColumn('model_data')->notEmpty('推送格式数据不能为空');
    }

    public function checkGetMoney(){
        $this->addColumn('type')->notEmpty('类型不能为空');
    }

    public function checkBoardGetList(){
        $this->addColumn('week')->notEmpty('周数不能为空');
        $this->addColumn('year')->notEmpty('年份不能为空');
    }

    public function checkTransformMoney(){
        //        int $originType, int $transformType, int $transformMoney
        $this->addColumn('origin_type')->notEmpty('初始类型不能为空')->inArray([1], true, '初始类型错误');
        $this->addColumn('transform_type')->notEmpty('替换类型不能为空')->inArray([2], true, '替换类型错误');
        $this->addColumn('transform_money')->notEmpty('替换金额不能为空');
    }
}