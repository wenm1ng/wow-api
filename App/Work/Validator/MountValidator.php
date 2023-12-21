<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-20 14:08
 */
namespace App\Work\Validator;

use User\Service\WalletService;
use App\Exceptions\CommonException;

class MountValidator extends BaseValidator
{
    public function checkPage()
    {
        $this->addColumn('page')->notEmpty('页数不能为空');
        $this->addColumn('pageSize')->notEmpty('每页数量不能为空');
    }

    public function checkLottery(array $params)
    {
        if(empty($params['is_all'])){
            $this->addColumn('id')->notEmpty('ID不能为空')->isArray('ID必须为数组');
        }
        $this->addColumn('type')->notEmpty('类型不能为空')->inArray([1,2,'1','2'], true, '类型不合法');
    }
}
