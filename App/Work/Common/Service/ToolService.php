<?php
namespace App\Work\Common\Service;

use App\Work\Common\Models\WowToolModel;
use App\Work\Common\Models\WowToolChildModel;
use App\Utility\Database\Db;
use App\Exceptions\CommonException;

class ToolService
{
    /**
     * @desc       获取工具列表
     * @author     文明<736038880@qq.com>
     * @date       2022-09-29 18:10
     * @return array
     */
    public function getToolList(){
        $fields = 'id,name,icon_name,page_path';
        $list = WowToolModel::query()->select(Db::raw($fields))->orderBy('sort', 'asc')->orderBy('id', 'asc')->get()->toArray();
        return $list;
    }

    /**
     * @desc       获取工具子列表
     * @author     文明<736038880@qq.com>
     * @date       2022-10-06 11:19
     * @param array $params
     *
     * @return array
     */
    public function getToolChildList(array $params){
        if(empty($params['id'])){
            CommonException::msgException('id不能为空');
        }
        $fields = 'id,name,icon_name,page_path,is_login';
        $list = WowToolChildModel::query()->where('tool_id', $params['id'])->where('status', 1)
            ->select(Db::raw($fields))->orderBy('sort', 'asc')->orderBy('id', 'asc')->get()->toArray();
        return $list;
    }

}