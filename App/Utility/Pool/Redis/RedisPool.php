<?php

namespace App\Utility\Pool\Redis;

use App\Utility\Pool\RedisObject;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Config as PoolConfig;

class RedisPool extends AbstractPool
{
    /**
     * @var null 
     */
    protected $redisConfig = null;
    
    public function __construct(PoolConfig $conf, $redisConfig = [])
    {
        parent::__construct($conf);
        if (empty($redisConfig['host'])) {
            throw new \Exception('Redis 连接配置错误');
        }
        
        $this->redisConfig = $redisConfig;
    }
    
    protected function createObject()
    {
        $obj = new RedisObject();
        $conf = $this->redisConfig;
        if (!$obj->connect($conf['host'], $conf['port'])) {
            return null;
        }
        if (!empty($conf['auth']) && !$obj->auth($conf['auth'])) {
            return null;
        }
        
        return $obj;
    }
}
