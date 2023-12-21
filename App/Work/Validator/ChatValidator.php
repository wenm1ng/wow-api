<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-23 16:32
 */
namespace App\Work\Validator;

class ChatValidator extends BaseValidator
{
    public function checkRoom()
    {
        $this->addColumn('room_id')->notEmpty('房间号不能为空');
        $this->checkPage();
    }

    public function checkPage(){
        $this->addColumn('page')->notEmpty('页数不能为空');
        $this->addColumn('pageSize')->notEmpty('每页数量不能为空');
    }

    public function checkRecord(){
        $this->addColumn('content')->notEmpty('日志内容不能为空');
    }
}