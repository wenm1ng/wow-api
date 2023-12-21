<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-10-06 14:52
 */
namespace App\Work\Common\Service;

use App\Work\Common\Models\WowMacroLogModel;
use App\Work\Common\Models\WowToolChildModel;
use App\Utility\Database\Db;
use App\Exceptions\CommonException;
use App\Work\Common\MacroConfig;
use Common\CodeKey;
use Common\Common;

class MacroService
{
    /**
     * @desc       技能组合宏
     * @author     文明<736038880@qq.com>
     * @date       2022-10-06 15:14
     * @param array $params
     *
     * @return array
     */
    public function group(array $params)
    {
        $enum = MacroConfig::$groupEnum;
        $str = "#showtooltip\r\n";
        foreach ($params as $key => $val) {
            if(isset($enum[$key]) && !empty($val)){
                $str .= $enum[$key].$val."\r\n";
            }
        }
        $logId = $this->addLog(Common::getUserId(), $str);
        return ['content' => $str, 'id' => $logId];
    }

    /**
     * @desc       记录宏日志
     * @author     文明<736038880@qq.com>
     * @date       2022-10-06 15:24
     * @param int    $userId
     * @param string $macroContent
     */
    private function addLog(int $userId, string $macroContent, string $name = ''){
        if($macroContent === "#showtooltip\r\n"){
            return 0;
        }
        return WowMacroLogModel::query()->insertGetId(['user_id' => $userId, 'macro_content' => $macroContent, 'macro_name' => $name]);
    }

    /**
     * @desc       保存用户宏记录
     * @author     文明<736038880@qq.com>
     * @date       2022-10-06 17:43
     * @param array $params
     *
     * @return array
     */
    public function save(array $params){
        if(empty($params['id'])){
           CommonException::msgException('id不能为空');
        }
        if(empty($params['name'])){
            CommonException::msgException('名称不能为空');
        }
        if(empty($params['macro_content'])){
            CommonException::msgException('宏内容不能为空', CodeKey::SIGN_ERROR);
        }
        if(empty(trim($params['macro_content'], ' '))){
            CommonException::msgException('宏内容不能为空', CodeKey::SIGN_ERROR);
        }

        WowMacroLogModel::query()->where('id', $params['id'])->update(['status' => 1, 'user_id' => Common::getUserId(), 'macro_name' => $params['name'], 'macro_content' => $params['macro_content']]);
        return [];
    }

    /**
     * @desc       删除宏
     * @author     文明<736038880@qq.com>
     * @date       2022-10-10 17:18
     * @param array $params
     *
     * @return array
     */
    public function del(array $params){
        if(empty($params['id'])){
            CommonException::msgException('id不能为空');
        }
        WowMacroLogModel::query()->where('id', $params['id'])->update(['status' => 2]);
        return [];
    }

    /**
     * @desc       获取手动创建宏菜单列表
     * @author     文明<736038880@qq.com>
     * @date       2022-10-07 14:43
     * @return array
     */
    public function getHandMacroList(){
        return MacroConfig::getHandList();
    }

    /**
     * @desc       组合手动创建宏
     * @author     文明<736038880@qq.com>
     * @date       2022-10-09 10:54
     * @param array $params
     *
     * @return array
     */
    public function handCombine(array $params){
        if(empty($params['action'])){
            CommonException::msgException('动作不能为空');
        }
        if(!isset($params['macro_str'])){
            CommonException::msgException('参数有误');
        }
        if(!isset($params['id'])){
            CommonException::msgException('参数有误');
        }
        $handList = $this->getHandMacroList();

        $actionArr = $params['action'];
        $action = $handList['list'][$actionArr[0]]; //动作指令最外层
        $actionCode = $action['code']; // /cast 等
        //因为会有最外层的动作指令为空，下一次才是指令，故判断
        if(empty($actionCode)){
            //下一级
            $actionCode = $action['child'][$actionArr[1]]['code'];
            $targetCode = !empty($action['child'][$actionArr[1]]['child'][$actionArr[2]]['code']) ? $action['child'][$actionArr[1]]['child'][$actionArr[2]]['code'] : '';
        }else{
            //本级
            $targetCode = !empty($action['child'][$actionArr[1]]['code']) ? $action['child'][$actionArr[1]]['code'] : ''; // @focus 等
        }
        $campCode = $buttonCode = $statusCode = $commonCode = ''; //条件

        if(!empty(MacroConfig::$checkboxCamp[$params['camp_index']]['code'])){
            //help
            $campCode = MacroConfig::$checkboxCamp[$params['camp_index']]['code'];
        }
        if(!empty(MacroConfig::$checkboxButton[$params['button_index']]['code'])){
            //mod:shift
            $buttonCode = MacroConfig::$checkboxButton[$params['button_index']]['code'];
        }
        if(!empty(MacroConfig::$checkboxStatus[$params['status_index']]['code'])){
            //exists
            $statusCode = MacroConfig::$checkboxStatus[$params['status_index']]['code'];
        }
        if(!empty(MacroConfig::$checkboxCommon[$params['common_index']]['code'])){
            //common 公共
            $commonCode = MacroConfig::$checkboxCommon[$params['common_index']]['code']."\r\n";
        }

        $conditionStr = '['. implode(',', array_filter([$targetCode, $campCode, $buttonCode, $statusCode])). ']';
        //技能名称
        $content = !empty($params['content']) ? $params['content'] : '';
        //喊话和普通宠物命令不需要条件
        if($actionArr[0] == 2 || ($actionArr[0] == 3 && $actionArr[1] != 0)){
            $conditionStr = $content = '';
        }

        //组合宏
        $macroStr = strpos($params['macro_str'], '#showtooltips') !== false ? $params['macro_str'] : "#showtooltips {$content}\r\n";
        $macroStr .= "\r\n{$commonCode}{$actionCode} {$conditionStr} {$params['content']}";

        $id = $params['id'];
        if(empty($params['id'])){
            //新增
            $insertData = [
                'user_id' => Common::getUserId(),
                'macro_content' => $macroStr
            ];
            $id = WowMacroLogModel::query()->insertGetId($insertData);
        }else{
            WowMacroLogModel::query()->where('id', $id)->update(['macro_content' => $macroStr]);
        }
        return ['content' => $macroStr, 'id' => $id];
    }

    /**
     * @desc       用户宏列表
     * @author     文明<736038880@qq.com>
     * @date       2022-10-09 17:49
     * @param array $params
     *
     * @return array
     */
    public function getList(array $params){
        $where = [
            'where' => [
                ['user_id', '=', Common::getUserId()],
                ['status', '=', 1]
            ]
        ];
        if (!empty($params['name'])) {
            $where['where'][] = ['macro_name', 'like', "%{$params['name']}%"];
        }
        if (!empty($params['order']) && !empty($params['sort'])) {
            if(!in_array($params['order'], ['update_at'])){
                CommonException::msgException('排序参数有误');
            }
            if(!in_array($params['sort'], ['desc','asc'])){
                CommonException::msgException('排序参数有误');
            }
            $where['order'] = [$params['order'] => $params['sort'], 'id' => 'desc'];
        } else {
            $where['order'] = ['update_at' => 'desc', 'id' => 'desc'];
        }
        $fields = 'id,macro_name,macro_content,update_at';
        $list = WowMacroLogModel::getPageOrderList($where, $params['page'], $fields, $params['pageSize']);
        if($params['page'] == 1){
            array_unshift($list, []);
            array_unshift($list, []);
        }
        return ['list' => $list];
    }
}