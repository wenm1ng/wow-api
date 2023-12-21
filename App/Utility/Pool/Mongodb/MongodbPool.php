<?php

namespace App\Utility\Pool\Mongodb;

use App\Utility\Pool\MongodbObject;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Config as PoolConfig;

class MongodbPool extends AbstractPool
{
    /**
     * @var null
     */
    protected $mongodbConfig = null;

    public function __construct(PoolConfig $conf, $mongodbConfig = [])
    {
        parent::__construct($conf);
        if (empty($mongodbConfig['url'])) {
            throw new \Exception('mongodb 连接配置错误');
        }

        $this->mongodbConfig = $mongodbConfig;
    }

    protected function createObject()
    {
        $obj = new MongodbObject($this->mongodbConfig['url']);

        return $obj;
    }
}
