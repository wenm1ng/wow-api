<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-02 17:55
 */
namespace App\Work\Validator;

use Common\CodeKey;
use App\Exceptions\CommonException;
use EasySwoole\Validate\Validate as systemValidate;
use User\Service\CommonService;
use Common\Common;

class BaseValidator extends systemValidate
{
    /**
     * @desc       敏感词校验
     * @author     文明<736038880@qq.com>
     * @date       2022-09-02 18:53
     * @param string $description
     * @param int $scene 2评论 3论坛
     *
     * @return bool|string
     */
    public function checkText(string $description, int $scene){
//        $checkInt = (new CommonService())->wxCheckText($description, Common::getUserOpenId(), $scene);
//        if($checkInt < 0){
//            if(!empty(searchSensitiveWords($description))){
//                CommonException::msgException('你填写的信息里面包含敏感词汇，请修改', CodeKey::WORDS_SENSITIVE);
//            }
//        }else if($checkInt === 0){
//            CommonException::msgException('你填写的信息里面包含敏感词汇，请修改', CodeKey::WORDS_SENSITIVE);
//        }
        if(!empty(searchSensitiveWords($description))){
            CommonException::msgException('你填写的信息里面包含敏感词汇，请修改', CodeKey::WORDS_SENSITIVE);
        }
    }

    public function checkPage(){
        $this->addColumn('page')->notEmpty('页数不能为空');
        $this->addColumn('pageSize')->notEmpty('每页数量不能为空');
    }
}