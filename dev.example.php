<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-21 16:27
 */


use EasySwoole\Log\LoggerInterface;

return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9909,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8,
            'reload_async' => true,
            'max_wait_time' => 3
        ],
        'TASK' => [
            'workerNum' => 4,
            'maxRunningNum' => 128,
            'timeout' => 15
        ]
    ],
    "LOG" => [
        'dir' => null,
        'level' => LoggerInterface::LOG_LEVEL_DEBUG,
        'handler' => null,
        'logConsole' => true,
        'displayConsole' => true,
        'ignoreCategory' => []
    ],
    'TEMP_DIR' => '/tmp',
    'LOG_DIR' => './Log',
    'DEFAULT_LANG' => 'zh-cn',
    'redis' => [
        'cache' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'auth' => '',
            'pool' => [
                'maxnum' => 16, // 最大连接数
                'minnum' => 2, // 最小连接数
                'timeout' => 3, // 获取对象超时时间，单位秒
                'idletime' => 30, // 连接池对象存活时间，单位秒
                'checktime' => 60000, // 多久执行一次回收检测，单位毫秒
            ],
        ],
    ]
];
