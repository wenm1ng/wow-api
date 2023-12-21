<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 11:48
 */
namespace App\Work\Validator;

class WaValidator extends BaseValidator
{
    public function checkVerision(){
        $this->addColumn('version')->notEmpty('版本号不能为空');
    }

    public function checkGetWaList(array $params){
        if(!isset($params['id']) && !isset($params['search_value'])){
            $this->checkVerision();
            $this->addColumn('page')->notEmpty('页数不能为空')->func(function($params){
                if(empty($params['tt_id']) || empty($params['oc'])){
                    return 'id和职业必选一项';
                }
                return true;
            });
        }
        $this->checkPage();
    }

    public function checkgetLabels(array $params){
        $this->checkVerision();
        if(empty($params['oc']) && empty($params['tt_id'])){
            throw new \Exception('职业和tabId必填一项');
        }
    }

    public function checkWaId(array $params){
        if(empty($params['is_all'])){
            $this->addColumn('id')->notEmpty('id不能为空');
        }
        $this->checkPage();
    }

    public function checkPage(){
        $this->addColumn('page')->notEmpty('页数不能为空');
        $this->addColumn('pageSize')->notEmpty('每页数量不能为空');
    }

    public function checkComment(array $params){
        $this->addColumn('content')->notEmpty('评论内容不能为空');
        $this->addColumn('wa_id')->notEmpty('所属wa不能为空');
        $this->checkText($params['content'], 2);
    }
}