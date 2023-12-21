<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-09-03 22:34
 */
namespace App\Work\Validator;

class OrderValidator extends BaseValidator
{
    public function checkAddOrder()
    {
        $this->addColumn('money')->notEmpty('金额不能为空')->float('金额必须为数字')->between(0.01, 100, '金额必须在[0.01, 100]之间');
        $this->addColumn('type')->notEmpty('类型不能为空');
    }

    public function checkPage(){
        $this->addColumn('page')->notEmpty('页数不能为空');
        $this->addColumn('pageSize')->notEmpty('每页数量不能为空');
    }
}