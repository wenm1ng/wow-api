<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-06 17:24
 */

namespace App\Common;

use App\Utility\Database\Db;
use App\Utility\Database\Model as LaravelModel;
use App\Utility\Logger\Logger;

class EasyModel extends LaravelModel
{

    public function throwSqlError(\Throwable $e)
    {
        $errMsg = $e->getMessage();
        Logger::error($errMsg, 'error_sql');
        throw new \Exception($errMsg);
    }

    /**
     * 事务开始
     */
    public function startTransaction()
    {
        Db::connection($this->getConnectionName())->beginTransaction();
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        Db::connection($this->getConnectionName())->commit();
    }

    /**
     * 事务结束
     */
    public function rollback()
    {
        Db::connection($this->getConnectionName())->rollback();
    }

    /**
     * @author xuanqi
     * 获取主键key
     * @return string|null
     */
    public function getPrimaryKey()
    {
        return property_exists($this, 'primaryKey') ? $this->primaryKey : null;
    }

    /**
     * @desc       　新分页列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array  $where
     * @param string $field
     * @param int    $limit
     *
     * @return mixed
     */
    public static function getPageOrderList($where = [], int $page = 1, string $field = '*', $limit = 10)
    {
        $query = self::baseQuery($where)->select(DB::raw($field));
        return $query->limit($limit)->offset($limit * ($page - 1))->get()->toArray();
    }

    /**
     * @desc       　重写查询条件where
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     *
     * @param array $where
     *
     * @return \Hyperf\Database\Model\Builder
     */
    public static function baseQuery(array $where)
    {
        $query = self::query();
        if (!empty($where['where'])) {
            foreach ($where['where'] as $val) {
                $query = $query->where(...array_values($val));
            }
        }
        //in 查询
        if (!empty($where['whereIn'])) {
            foreach ($where['whereIn'] as $val) {
                $query = $query->whereIn(...array_values($val));
            }
        }

        if(!empty($where['whereRaw'])){
            foreach ($where['whereRaw'] as $val) {
                $query->where(function($q)use($val){
                    $q->whereRaw($val);
                });
            }
        }

        //between查询
        if (!empty($where['between'])) {
            foreach ($where['between'] as $val) {
                $query = $query->whereBetween(...array_values($val));
            }
        }

        if(!empty($where['order'])){
            foreach ($where['order'] as $column => $val) {
                $query = $query->orderBy($column, $val);
            }
        }

        if(!empty($where['group'])){
            $query = $query->groupBy($where['group']);
        }

        return $query;
    }

    /**
     * @desc       　获取当前model的列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $where
     *
     * @return array
     */
    public static function getList(array $where, string $fields = '*'){
        return self::baseQuery($where)->select(Db::raw($fields))->get()->toArray();
    }

    /**
     * @desc       　根据主键id获取详情数据
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $id
     *
     * @return array
     */
    public function getInfoById($id){
        return self::query()->where($this->getPrimaryKey(), $id)->first()->toArray();
    }
}