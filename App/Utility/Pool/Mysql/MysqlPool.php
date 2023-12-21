<?php

namespace App\Utility\Pool\Mysql;

use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Config as PoolConfig;

class MysqlPool extends AbstractPool
{
    /**
     * @var \EasySwoole\Mysqli\Config
     */
    protected $mysqlConfig;

    /**
     * @var 数据库连接归属那个池
     */
    protected $poolKey;

    public function __construct(PoolConfig $conf, $mysqlConfig = [],$poolKey = 'mysql_pool_key')
    {
        parent::__construct($conf);
        if (empty($mysqlConfig['host']) || empty($mysqlConfig['database'])) {
            throw new \Exception('数据库连接配置错误');
        }
        $this->mysqlConfig = new \EasySwoole\Mysqli\Config($mysqlConfig, true);
        $this->poolKey = $poolKey;
    }

    protected function createObject()
    {
        $obj = new \App\Utility\Pool\MysqlObject($this->mysqlConfig);
        $obj->setPoolKey($this->poolKey);
        return $obj;
    }
}
