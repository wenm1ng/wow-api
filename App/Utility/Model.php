<?php

namespace App\Utility;

use App\Utility\Pool\MysqlObject;
use EasySwoole\Pool\Exception\PoolEmpty;
use EasySwoole\Pool\Exception\Exception as PoolException;

/**
 * @desc     Mysql Model 基础类
 * @author   Huangbin <huangbin2018@qq.com>
 * @date     2020/3/24 14:42
 * @package  App\Model
 */
class Model
{
    /**
     * 表前缀
     * @var
     */
    protected $pre;

    /**
     *
     * @var MysqlObject|mixed|null
     */
    protected $db = null;

    /**
     *
     * @var
     */
    protected $tableName;

    /**
     *
     * @var string
     */
    protected $primary = '';

    /**
     * @var string 相同公司代码下包含多数据库链接的情况，在继承类中使用，获取不同链接
     */
    protected $type = 'default';

    /**
     * @param MysqlObject $db 根据上下文环境设置的company_code获取数据库连接
     * @throws
     */
    protected function __construct(MysqlObject $db = null)
    {
        $this->pre = '';
        $companyCode = Company::getCompanyCode();

        if ($db instanceof MysqlObject) {
            $this->db = $db;
        } else {
            $this->db = Common::getMysql($companyCode,$this->type);
        }

        if (is_null($this->db)) {
            throw new PoolEmpty("{$companyCode} - {$this->type} pool is empty");
        }

        if (!$this->db instanceof MysqlObject) {
            throw new PoolException("{$companyCode} - {$this->type}  convert to pool error");
        }
    }

    /**
     * @desc 获取 DB 连接
     * @return MysqlObject
     */
    public function getDb():MysqlObject
    {
        return $this->db;
    }

    /**
     * @desc 设置 DB 连接
     * @return self
     */
    public function setDb($db = null)
    {
        if ($db instanceof MysqlObject) {
            $this->db = $db;
        }

        return $this;
    }

    /**
     * @desc 获取查询构造器
     * @return \EasySwoole\Mysqli\QueryBuilder
     */
    public function getQueryBuilder ():\EasySwoole\Mysqli\QueryBuilder
    {
        return $this->db->queryBuilder();
    }

    /**
     * @desc 设置表名
     * @param null $tableName
     * @return $this
     */
    public function setTable($tableName = null)
    {
        if (!empty($tableName)) {
            $this->tableName = $this->pre . $tableName;
        }
        return $this;
    }

    /**
     * @desc 获取表名
     * @return string
     */
    public function getTable()
    {
        $pre = $this->pre;
        return $pre.$this->tableName;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        // 这里不能手动释放对象了，因为获取对象的通过 defer 获取的，会在协程退出时自动回收【作废】
        // 如果这里手动回收，会导致链接被复用 ！！！！【作废】

        // 同一协程存在model嵌套调用，会提前释放掉连接，导致协程公用同一个连接导致的报错。通过引用计数器解决这个问题。
        $fastReleaseMysqlConnect = intval(Context::getContext('fastReleaseMysqlConnect' .$this->db->getPoolKey()));
        Context::setContext('fastReleaseMysqlConnect' .$this->db->getPoolKey(),--$fastReleaseMysqlConnect);

        // 非事物在模型调用完毕后，手动释放连接到池中，提升数据库利用效率
        if ($fastReleaseMysqlConnect<=0 and $this->db instanceof MysqlObject) {
            // 引用计数器矫正
            Context::setContext('fastReleaseMysqlConnect' .$this->db->getPoolKey(),0);
            $startTransactionCount = intval(Context::getContext('startTransactionCount'));
            //协程环境下次使用快速回收
            $cid = \Swoole\Coroutine::getCid();
            if($startTransactionCount == 0 and $cid > -1){
                $pool = \EasySwoole\Pool\Manager::getInstance()->get($this->db->getPoolKey());
                $pool->recycleObj($this->db);
            }
        }
    }

    /**
     * @desc 查询单行记录
     * @param $value
     * @param string $field
     * @param string|array $columns
     * @param array $option
     * @return array
     * @throws \Throwable
     */
    public function getOne($value, $field = '', $columns = '*', $option = []): array
    {
        if (empty($field)) {
            $field = $this->primary;
        }
        $this->getQueryBuilder()->where($field, $value);
        if (isset($option['for_update']) && $option['for_update'] === true) {
            // $this->getQueryBuilder()->setQueryOption("FOR UPDATE");
            $this->getQueryBuilder()->selectForUpdate(true);
        }
        $this->getQueryBuilder()->getOne($this->getTable(), $columns);
        $data = $this->getDb()->execBuilder();

        return empty($data) ? [] : $data[0];
    }

    /**
     * @desc 根据条件查询单行记录
     * @param array $where
     * @param string|array $columns
     * @return array|null
     * @throws \Throwable
     */
    public function getOneByWhere(array $where, $columns = '*'): ?array
    {
        foreach ($where as $whereField => $whereProp) {
            if (is_array($whereProp)) {
                $this->getQueryBuilder()->where($whereField, ...$whereProp);
            } else {
                $this->getQueryBuilder()->where($whereField, $whereProp);
            }
        }
        $this->getQueryBuilder()->getOne($this->getTable(), $columns);
        $data = $this->getDb()->execBuilder();

        return empty($data) ? [] : $data[0];
    }

    public function getByField($value, $field = '', $columns = '*', $option = [])
    {
        return $this->getOne($value, $field, $columns, $option);
    }

    /**
     * @desc 根据条件查询记录
     * @param array $where
     * @param string|array $columns
     * @param array $option
     * @return array|null
     * @throws \Throwable
     */
    public function getAll(array $where, $columns = '*', $option = []): ?array
    {
        foreach ($where as $whereField => $whereProp) {
            if (is_array($whereProp)) {
                $this->getQueryBuilder()->where($whereField, ...$whereProp);
            } else {
                $this->getQueryBuilder()->where($whereField, $whereProp);
            }
        }
        if (isset($option['for_update']) && $option['for_update'] === true) {
            $this->getQueryBuilder()->selectForUpdate(true);
        }
        $this->getQueryBuilder()->get($this->getTable(), null, $columns);
        $data = $this->getDb()->execBuilder();

        return $data ?: [];
    }


    /**
     * @desc 添加单行记录
     * @param array $row
     * @return bool|null
     * @throws \Throwable
     */
    public function add(array $row = [])
    {
        $this->getQueryBuilder()->insert($this->getTable(), $row);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->insert_id;
        }

        return false;
    }

    /**
     * @desc 添加多行记录
     * @param array $row
     * @return bool|int
     * @throws \Throwable
     */
    public function addMulti(array $row = [])
    {
        // $this->getQueryBuilder()->insertMulti($this->getTable(), $row);
        $this->getQueryBuilder()->insertAll($this->getTable(), $row);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return false;
    }

    /**
     * @desc 更新记录
     * @param array $row
     * @param $value
     * @param string $field
     * @param null $limit
     * @return bool|int
     * @throws \Throwable
     */
    public function update(array $row, $value, $field = '', $limit = null)
    {
        if (empty($field)) {
            $field = $this->primary;
        }
        $this->getQueryBuilder()->where($field, $value)->update($this->getTable(), $row, $limit);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return false;
    }

    /**
     * @desc 根据条件更新记录
     * @param array $row
     * @param array $where 键值对条件，支持操作符(IN, NOT IN, LIKE, >, <, <>, <=, >= 等)，操作符与字段以空格分隔作为键，如 ['col1 in' => ['val1', 'val2'], 'col2' => 'val3']
     * @param null $limit
     * @return bool|int
     * @throws \Throwable
     */
    public function updateByWhere(array $row, array $where = [], $limit = null)
    {
        if (empty($where)) {
            throw new \Exception('$where 参数不能为空');
        }
        $builder = $this->getQueryBuilder();
        foreach ($where as $whereField => $whereProp) {
            // 解析出操作符 operator (IN, NOT IN, LIKE, >, <, <>, <=, >= 等)
            $fields = explode(' ', trim($whereField));
            $field = $fields[0];
            if (count($fields) == 1) {
                $operator = '=';
            } else {
                array_shift($fields);
                $operator = implode(' ', $fields);
            }
            $builder->where($field, $whereProp, $operator);
        }
        $this->getQueryBuilder()->update($this->getTable(), $row, $limit);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return false;
    }

    /**
     * @desc 删除单行记录
     * @param $value
     * @param string $field
     * @param null $limit
     * @return bool|int
     * @throws \Throwable
     */
    public function delete($value, $field = '', $limit = null)
    {
        if (empty($field)) {
            $field = $this->primary;
        }
        $this->getQueryBuilder()->where($field, $value)->delete($this->getTable(), $limit);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return false;
    }
    
    /**
     * @desc 根据条件删除记录
     * @param array $where 键值对条件，支持操作符(IN, NOT IN, LIKE, >, <, <>, <=, >= 等)，操作符与字段以空格分隔作为键，如 ['col1 in' => ['val1', 'val2'], 'col2' => 'val3']
     * @param null $limit
     * @return bool|int
     * @throws \Throwable
     */
    public function deleteByWhere(array $where = [], $limit = null)
    {
        if (empty($where)) {
            throw new \Exception('$where 参数不能为空');
        }
        $builder = $this->getQueryBuilder();
        foreach ($where as $whereField => $whereProp) {
            // 解析出操作符 operator (IN, NOT IN, LIKE, >, <, <>, <=, >= 等)
            $fields = explode(' ', trim($whereField));
            $field = $fields[0];
            if (count($fields) == 1) {
                $operator = '=';
            } else {
                array_shift($fields);
                $operator = implode(' ', $fields);
            }
            $builder->where($field, $whereProp, $operator);
        }
        $this->getQueryBuilder()->delete($this->getTable(), $limit);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return false;
    }

    /**
     * @desc 开启事务
     * @throws \Throwable
     */
    public function startTransaction()
    {
        $startTransactionCount = intval(Context::getContext('startTransactionCount'));
        if(empty($startTransactionCount)){
            $this->db->connect();
            $this->getDb()->mysqlClient()->begin();
        }
        Context::setContext('startTransactionCount',++$startTransactionCount);
    }

    /**
     * @desc 提交事务
     * @throws \Throwable
     */
    public function commit()
    {
        $startTransactionCount = intval(Context::getContext('startTransactionCount'));
        Context::setContext('startTransactionCount',--$startTransactionCount);
        if(empty($startTransactionCount)){
            $this->getDb()->mysqlClient()->commit();
        }
    }

    /**
     * @desc 回滚事务
     * @throws \Throwable
     */
    public function rollback()
    {
        $this->db->connect();
        $this->getDb()->mysqlClient()->rollback();
    }

    /**
     * @desc 执行 SQL
     * @param string $sql
     * @param array $params
     * @return bool|int|null
     * @throws \Throwable
     */
    public function query(string $sql, array $params = [])
    {
        $this->getQueryBuilder()->raw($sql, $params);
        $res = $this->getDb()->execBuilder();
        if ($res === true) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return $res;
    }

    /**
     * @desc 多条件查询
     * @param array|string $condition
     * @param string|array $type
     * @param int $pageSize
     * @param int $page
     * @param string|array $order ['a.id desc', 'b.age asc']
     * @param string|array $group ['a.id', 'b.name']
     * @return bool|null|array
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    public function getByCondition($condition = [], $type = '*', $pageSize = 0, $page = 1, $order = '', $group = '')
    {
        $builder = $this->getQueryBuilder();
        if (is_string($condition) && !empty($condition)) {
            $builder->where(' ('. $condition .') ');
        } elseif (is_array($condition)) {
            foreach ($condition as $field => $item) {
                //空查下所有
//                if (is_array($item) && empty($item)) {
//                    continue;
//                }
                if ($item === '' || $item === null) {
                    continue;
                }

                // 可以执行 sql，如 (id > 10 or id <2)
                if ($field == 'sql') {
                    if (is_array($item)) {
                        foreach ($item as $sql) {
                            $builder->where(' ('. $sql .') ');
                        }
                    } else {
                        $builder->where(' ('. $item .') ');
                    }
                    continue;
                }

                // 解析出操作符 operator (IN, NOT IN, LIKE 等)
                $fields = explode(' ', trim($field));
                $field = $fields[0];
                if (count($fields) == 1) {
                    $operator = '=';
                } else {
                    array_shift($fields);
                    $operator = implode(' ', $fields);
                }

                // 数组形式
                if (is_array($item)) {
                    $builder->where($field, $item, $operator);
                } else {
                    $builder->where($field, $item, $operator);
                }
            }
        }

        $table = $this->getTable();
        if ($type == 'count(*)') {
            if ($group) {
                // $type = 'distinct ' . $this->primary;
            }
            $builder->getScalar($table, $type);
            $count = $this->getDb()->execBuilder();
            return $count[0][$type] ?: 0;
        } else {
            if ($group) {
                if (is_array($group)) {
                    foreach ($group as $gv) {
                        $builder->groupBy($gv);
                    }
                } else {
                    $builder->groupBy($group);
                }
            }

            if ($order) {
                if (is_array($order)) {
                    foreach ($order as $ov) {
                        $orderArr = explode(' ', $ov);
                        $builder->orderBy($orderArr[0], $orderArr[1] ?: 'ASC');
                    }
                } else {
                    $orderArr = explode(' ', $order);
                    $builder->orderBy($orderArr[0], $orderArr[1] ?: 'ASC');
                }
            }

            if ($page > 0 && $pageSize > 0) {
                $limitRow = [];
                $offset = ($page - 1) * $pageSize;
                $limitRow[0] = $offset;
                $limitRow[1] = $pageSize;
            } else {
                $limitRow = null;
            }

            $builder->get($this->getTable(), $limitRow, $type);
            $rows = $this->getDb()->execBuilder();
            return $rows;
        }
    }

    /**
     * @desc 多条件 Join 查询
     * @param array|string $condition
     * @param string|array $type
     * @param int $pageSize
     * @param int $page
     * @param array $join ['join_table' => 'table2', 'join_on' => 'table2.col1 = getTable.col2', 'join_type' => 'LEFT']
     * @param string|array $order
     * @param string $group
     * @return bool|null|array
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    public function getJoinByCondition($condition = [], $type = '*', $pageSize = 0, $page = 1, $join = [], $order = '', $group = '')
    {
        if (empty($join)) {
            return $this->getByCondition($condition, $type, $pageSize, $page, $order, $group);
        }
        $builder = $this->getQueryBuilder();
        if (is_string($condition) && !empty($condition)) {
            $builder->where(' ('. $condition .') ');
        } elseif (is_array($condition)) {
            foreach ($condition as $field => $item) {
                if (is_array($item) && empty($item)) {
                    continue;
                }
                // 可以执行 sql，如 (id > 10 or id <2)
                if ($field == 'sql') {
                    if (is_array($item)) {
                        foreach ($item as $sql) {
                            $builder->where(' ('. $sql .') ');
                        }
                    } else {
                        $builder->where(' ('. $item .') ');
                    }
                    continue;
                }

                // 解析出操作符 operator (IN, NOT IN, LIKE 等)
                $fields = explode(' ', trim($field));
                $field = $fields[0];
                if (count($fields) == 1) {
                    $operator = '=';
                } else {
                    array_shift($fields);
                    $operator = implode(' ', $fields);
                }

                // 数组形式
                if (is_array($item)) {
                    $builder->where($field, $item, $operator);
                } else {
                    $builder->where($field, $item, $operator);
                }
            }
        }

        // 处理 join 的表
        foreach ($join as $t => $value) {
            $table2 = $value['join_table'] ?: $t;
            $builder->join($table2, $value['join_on'], $value['join_type'] ?: 'LEFT');
        }

        if ($type == 'count(*)') {
            if ($group) {
                // $type = 'distinct ' . $this->primary;
            }
            $builder->getScalar($this->getTable(), $type);
            $count = $this->getDb()->execBuilder();
            return $count[0][$type] ?: 0;
        } else {
            if ($group) {
                if (is_array($group)) {
                    foreach ($group as $gv) {
                        $builder->groupBy($gv);
                    }
                } else {
                    $builder->groupBy($group);
                }
            }

            if ($order) {
                if (is_array($order)) {
                    foreach ($order as $ov) {
                        $orderArr = explode(' ', $ov);
                        $builder->orderBy($orderArr[0], $orderArr[1] ?: 'ASC');
                    }
                } else {
                    $orderArr = explode(' ', $order);
                    $builder->orderBy($orderArr[0], $orderArr[1] ?: 'ASC');
                }
            }

            if ($page > 0 && $pageSize > 0) {
                $limitRow = [];
                $offset = ($page - 1) * $pageSize;
                $limitRow[0] = $offset;
                $limitRow[1] = $pageSize;
            } else {
                $limitRow = null;
            }

            $builder->get($this->getTable(), $limitRow, $type);
            $rows = $this->getDb()->execBuilder();
            return $rows;
        }
    }

    // =====================================================================
    // =========================== SQL 查询构造 =============================
    // =====================================================================
    /*
     // 使用示例
     $userModel = new UserModel();
        $rows = $userModel->where('user_id', 1, '>=')
            ->whereIn('user_name', ['admin', 'eccang'])
            ->whereNotIn('user_name', ['abc', 'def'])
            ->fields(['user_id', 'user_name'])
            ->limit(1, 20)
            ->get();
        $sql = $userModel->getLastQuery();
        // SELECT user_id, user_name FROM `user` WHERE `user_id` >= 1 AND `user_name` IN ( 'admin', 'eccang' ) AND `user_name` NOT IN ( 'abc', 'def' ) LIMIT 0, 20
        $userModel->between($userModel->getTable() .'.user_id', 1, 20)
            ->join('user_log', $userModel->getTable() . '.user_id = user_log.user_id', 'LEFT')
            ->orderBy($userModel->getTable() . '.user_id', 'desc')
            ->orderBy('user_log.ul_time', 'asc')
            ->groupBy($userModel->getTable() . '.user_id')
            ->fields(true)
            ->get();
        $sql = $userModel->getLastQuery();
        // SELECT * FROM `user` LEFT JOIN user_log on user.user_id = user_log.user_id WHERE `user`.`user_id` between 1 AND 20 GROUP BY user.user_id ORDER BY user.user_id DESC, user_log.ul_time ASC 
        
     */
    /**
     * @desc 查询条件构造
     * <code>
     * $model->where('col1', 1) // where `col1` = 1
     * ->where('(id = ? or id = ?)', [1,3]) // where  (id = 1 or id = 3)
     * ->where('col3', 2, '>') // where  `col3` > 2
     * ->where('col2', [1,2,3], 'IN') // where `col2` in (1, 2, 3)
     * ->where('find_in_set(?, test)', [1], 'IN') // where find_in_set(1, test) ## ?为参数绑定
     * </code>
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $field 查询字段
     * @param array|string $value 查询条件值,需要根据 $operator 类型传入对应的参数
     * @param string $operator 查询操作符，支持 =, !=, <, >, <>, <=, >=, IN, NOT IN, LIKE, not between, between, not exists, exists
     * @param string $cond 条件，AND 、OR
     * @return $this
     */
    public function where(string $field, $value, $operator = '=', $cond = 'AND')
    {
        $builder = $this->getQueryBuilder();
        if (empty($field)) {
            $field = $this->primary;
        }

        $builder->where($field, $value, $operator, $cond);

        return $this;
    }

    /**
     * @desc OR 查询条件构造
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $field 查询字段
     * @param array|string $value 查询条件值,需要根据 $operator 类型传入对应的参数
     * @param string $operator 查询操作符，支持 =, !=, <, >, <>, <=, >=, IN, NOT IN, LIKE, not between, between, not exists, exists
     * @return $this
     */
    public function whereOr(string $field, $value, $operator = '=')
    {
        $builder = $this->getQueryBuilder();
        if (empty($field)) {
            $field = $this->primary;
        }

        $builder->orWhere($field, $value, $operator);

        return $this;
    }

    /**
     * @desc 快捷 IN 查询
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function whereIn(string $field = '', array $value = [])
    {
        $builder = $this->getQueryBuilder();
        if (empty($field)) {
            $field = $this->primary;
        }
        if (!empty($value) && is_array($value)) {
            $builder->where($field, $value, 'IN');
        }
        return $this;
    }

    /**
     * @desc 快捷 NOT IN 查询
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $field
     * @param array $value
     * @return $this
     */
    public function whereNotIn(string $field = '', array $value = [])
    {
        $builder = $this->getQueryBuilder();
        if (empty($field)) {
            $field = $this->primary;
        }
        if (!empty($value) && is_array($value)) {
            $builder->where($field, $value, 'NOT IN');
        }
        return $this;
    }

    /**
     * @desc 快捷 between 查询
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $field 字段
     * @param string $start 开始值
     * @param string $end 结束值
     * @return $this
     */
    public function between(string $field = '', $start = '', $end = '')
    {
        $builder = $this->getQueryBuilder();
        if (empty($field)) {
            $field = $this->primary;
        }

        $value = [$start, $end];
        $builder->where($field, $value, 'between');
        return $this;
    }

    /**
     * @desc 快捷 not between 查询
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $field 字段
     * @param string $start 开始值
     * @param string $end 结束值
     * @return $this
     */
    public function betweenNot(string $field = '', $start = '', $end = '')
    {
        $builder = $this->getQueryBuilder();
        if (empty($field)) {
            $field = $this->primary;
        }

        $value = [$start, $end];
        $builder->where($field, $value, 'not between');
        return $this;
    }

    /**
     * @desc join 连表查询
     * <code>
     * $model->join('tableName2', $model->getTable() . '.col2 = tableName2.col1', 'INNER')
     * </code>
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $joinTable 连表表名, 如 table2
     * @param string $joinCondition 连表条件，如 table2.col1 = thisTable.col2
     * @param string $joinType 连表类型，如 LEFT 、 INNER
     * @return $this
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    public function join($joinTable, $joinCondition, $joinType = 'LEFT')
    {
        if (!$joinTable || !$joinCondition) {
            throw new \Exception('join 参数错误');
        }
        $builder = $this->getQueryBuilder();
        $builder->join($joinTable, $joinCondition, $joinType);

        return $this;
    }

    /**
     * @desc
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $orderByField 排序字段
     * @param string $direction 排序方式 ASC/DESC
     * @return $this
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    public function orderBy($orderByField, $direction = 'ASC')
    {
        $builder = $this->getQueryBuilder();
        $builder->orderBy($orderByField, $direction);

        return $this;
    }

    /**
     * @desc having 查询
     * <code>
     * $model->having('col1', 1, '>') // (having col1 > 1 OR having col2 > 1)
     * ->having('col2', 1, '>', 'OR')
     * </code>
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $field 查询字段
     * @param string $havingValue 查询值
     * @param string $operator 操作符，可以是 = 、> 、< 等等
     * @param string $cond 条件 AND 、OR
     * @return $this
     */
    public function having($field, $havingValue = '', $operator = '=', $cond = 'AND')
    {
        $builder = $this->getQueryBuilder();
        $builder->having($field, $havingValue, $operator, $cond);

        return $this;
    }

    /**
     * @desc group by 查询
     * <code>
     * $model->groupBy('table2.col')->groupBy($model->getTable() . '.col2')
     * </code>
     * @author Huangbin <huangbin2018@qq.com>
     * @param string|array $group
     * @return $this
     * @throws \Exception
     */
    public function groupBy($group)
    {
        $builder = $this->getQueryBuilder();
        if (!$group) {
            throw new \Exception('groupBy 参数错误');
        }
        if (!is_array($group)) {
            $groupArr = [$group];
        } else {
            $groupArr = $group;
        }

        foreach ($groupArr as $group) {
            $builder->groupBy($group);
        }

        return $this;
    }

    /**
     * @desc Limit 方法，用于分页， 例如 ->limit(3, 20) 实际生成  limit 40, 20
     * <code>
     * $model->limit(1, 20);
     * </code>
     * @author Huangbin <huangbin2018@qq.com>
     * @param int $page 页码，从 1 开始
     * @param int $pageSize 页显示数
     * @return $this
     * @throws \Exception
     */
    public function limit($page = 1, $pageSize = 20)
    {
        if (!is_integer($page) || !is_integer($pageSize)) {
            throw new \Exception('limit 参数类型错误');
        }
        $offset = ($page - 1) * $pageSize;
        $builder = $this->getQueryBuilder();
        $builder->limit($offset, $pageSize);

        return $this;
    }

    /**
     * @desc 查询字段
     * <code>
     * $userModel->fields('user_id')->fields(['user_name', 'user_nickname as nickname']);
     * </code>
     * @author Huangbin <huangbin2018@qq.com>
     * @param array|string|bool $fields
     * @return $this
     */
    public function fields($fields = true)
    {
        $builder = $this->getQueryBuilder();
        if ($fields === true) {
            $fields = '*';
        }
        $builder->fields($fields);

        return $this;
    }

    /**
     * @desc 执行查询, 需要在执行 where/fields 等方法构造查询器后执行
     * @author Huangbin <huangbin2018@qq.com>
     * @return bool|null|array
     * @throws \Exception
     */
    public function get()
    {
        $builder = $this->getQueryBuilder();
        $builder->get($this->getTable());
        $rows = $this->getDb()->execBuilder();
        return $rows;
    }

    /**
     * @desc 执行查询获取单行记录, 需要在执行 where/fields 等方法构造查询器后执行
     * @author Huangbin <huangbin2018@qq.com>
     * @return bool|null|array
     * @throws \Exception
     */
    public function getRow()
    {
        $this->getQueryBuilder()->getOne($this->getTable());
        $data = $this->getDb()->execBuilder();

        return empty($data) ? [] : $data[0];
    }

    /**
     * @desc 执行查询，获取单个字段的值, 需要在执行 where/fields 等方法构造查询器后执行
     * @author Huangbin <huangbin2018@qq.com>
     * @return bool|null|string|mixed
     * @throws \Exception
     */
    public function getValue($field = null)
    {
        if (empty($field) || !is_string($field)) {
            $field = $this->primary;
        }
        $this->getQueryBuilder()->getScalar($this->getTable(), $field);
        $data = $this->getDb()->execBuilder();

        return empty($data) ? null : current($data[0]);
    }

    /**
     * @desc 删除 N 行记录, 前提是先调用 ->where 系列方法，否则会全表删除！！！！！
     * <code>
     * $model = new UserModel();
     * $model->where('col1', 1)->whereIn('col2', [2, 4])->execDelete();
     * </code>
     * @param null $limit 限制行数 N, null 不限制
     * @return bool|int
     * @throws \Throwable
     */
    public function execDelete($limit = null)
    {
        $this->getQueryBuilder()->delete($this->getTable(), $limit);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return false;
    }

    /**
     * @desc 执行更新，前提是先调用 ->where 系列方法，否则会全表更新！！
     * <code>
     * $model = new UserModel();
     * $model->whereIn('user_id', [1, 2])->where('user_status', '1', '<>')->where('user_name', 'admin', 'like')
     * ->execUpdate(['user_update_time' => time(), 'user_status' => 2]);
     * </code>
     * @author Huangbin <huangbin2018@qq.com>
     * @param array $row
     * @param null $limit
     * @return bool|int
     * @throws \Exception
     */
    public function execUpdate($row = [], $limit = null)
    {
        $this->getQueryBuilder()->update($this->getTable(), $row, $limit);
        $res = $this->getDb()->execBuilder();
        if ($res) {
            return $this->getDb()->mysqlClient()->affected_rows;
        }

        return false;
    }
    
    /**
     * @desc 获取查询构造器生成的 SQL,需要在执行完 get 方法后调用才有值
     * @author Huangbin <huangbin2018@qq.com>
     * @return mixed|string
     */
    public function getLastQuery()
    {
        $builder = $this->getQueryBuilder();
        return $builder->getLastQuery();
    }

    /**
     * @desc 重置查询构造
     * @author Huangbin <huangbin2018@qq.com>
     */
    public function reset()
    {
        $this->getDb()->reset();
    }

    /**
     * @desc 检查数据库连接是否正常
     * @author Ezio <xubo@eccang.com>
     */
    final function ping($conf): bool
    {
        //尝试连接
        $config = new \EasySwoole\Mysqli\Config([
            'host'          => $conf['host'],
            'port'          => $conf['port'],
            'user'          => $conf['user'],
            'password'      => $conf['password'],
            'database'      => $conf['database'],
            'timeout'       => 0.5,
            'charset'       => 'utf8mb4',
        ]);
        $client = new \EasySwoole\Mysqli\Client($config);
        $isConnect = $client->connect();
        if ($isConnect)
        {
            //关闭连接
            // $client->close();
            unset($client);
            return true;
        }

        return false;
    }
}
