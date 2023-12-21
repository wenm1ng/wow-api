<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-28 13:56
 */
namespace App\Work\Validator;

use User\Service\WalletService;
use App\Exceptions\CommonException;

class HelpCenterValidator extends BaseValidator
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

    public function checkAddHelp(array $params){
        $this->addColumn('title')->notEmpty('求助标题不能为空');
        $this->addColumn('version')->notEmpty('版本不能为空');
        $this->addColumn('help_type')->notEmpty('求助类型不能为空');
        $this->addColumn('description')->notEmpty('求助详细描述不能为空');
        $this->addColumn('is_pay')->required('是否有偿求助不能为空');
        $this->checkText($params['description'], 3);
        $this->checkText($params['title'], 3);
        if($params['is_pay'] == 1){
            if(empty($params['coin'])){
                CommonException::msgException('奖励币数不能为空');
            }
            $return = (new WalletService())->getMoney(['type' => 1]);
            if(!empty($return['coin']) && $return['coin'] < $params['money'] ){
                CommonException::msgException('奖励币数不足');
            }
        }
    }

    public function checkId(){
        $this->addColumn('id')->notEmpty('id不能为空');
    }

    public function checkAdopt(){
        $this->addColumn('id')->notEmpty('id不能为空');
        $this->addColumn('help_id')->notEmpty('帮助id不能为空');
    }

    public function checkAddAnswer(array $params){
        $this->addColumn('help_id')->notEmpty('求助id不能为空');
        $this->addColumn('description')->notEmpty('描述不能为空');
        $this->checkText($params['description'], 3);

        //wa检测
        if(!empty($params['wa_content'])){
            if(strpos($params['wa_content'], '!') !== 0){
                CommonException::msgException('WA字符串格式有误', 408);
            }
            $checkWaContent = mb_substr($params['wa_content'], 0, 30);
            $this->checkText($checkWaContent, 3);
        }
    }

    public function checkUpdateAnswer(array $params){
        $this->checkId();
        $this->addColumn('help_id')->notEmpty('求助id不能为空');
        $this->addColumn('description')->notEmpty('描述不能为空');
        $this->checkText($params['description'], 3);
    }
}