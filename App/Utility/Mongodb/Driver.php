<?php

namespace App\Utility\Mongodb;

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\SyncInvoker\AbstractInvoker;
use MongoDB\Client;

class Driver
{
    private static $db;

    static function getDb($key = "")
    {
        $key = !empty($key) ? $key : "automatic_task";
        if(self::$db == null){
            $config = Config::getInstance()->getConf('mongo.automatic_task');
            $database = $config['database'];
            $client = new Client("mongodb://{$config['user']}:{$config['password']}@{$config['host']}:{$config['port']}/".$database);
            self::$db = $client->$database;
        }
        return self::$db;
    }

    protected function onException(\Throwable $throwable)
    {
        Trigger::getInstance()->throwable($throwable);
        return null;
    }
}