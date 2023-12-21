<?php


namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\Db\Config;
use EasySwoole\EasySwoole\Config as SettingConfig;
use App\Utility\Common;
use App\Work\Chat\Service\ChatService;
use Common\Common as CommonCommon;
use EasySwoole\EasySwoole\Crontab\Crontab;
use App\Crontab\WowLeaderBoardCrontab;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');
//        $config = new Config(SettingConfig::getInstance()->getConf('mysql.service'));
//        DbManager::getInstance()->addConnection(new Connection($config));
//        $redisConfig1 = new \EasySwoole\Redis\Config\RedisConfig(SettingConfig::getInstance()->getConf('redis.cache'));
//        \EasySwoole\Pool\Manager::getInstance()->register(new \App\Pool\RedisPool($config, $redisConfig1), 'cache');
//        // 载入Config文件夹中的配置文件
//        Common::loadConf();

        // 载入Config文件夹中的配置文件
        Common::loadConf();
        // 注册 Redis 连接池
        Common::registerRedisPool('cache');

    }

    public static function mainServerCreate(EventRegister $register)
    {
        //助手函数
        require_once "App/Common/function.php";
        //只有线上跑排行榜定时任务
        if(config('app.environment') === 'online'){
            Crontab::getInstance()->addTask(WowLeaderBoardCrontab::class);
        }

        self::hotReload();
        //开启聊天websocket
//        $register->set($register::onOpen, function ($ws, $request) {
////            var_dump($request->fd, $request->server);
//            $ws->push($request->fd, "hello, welcome\n");
//        });
//
//        $register->set($register::onMessage, function (\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame) {
//            echo "Message: {$frame->data}\n";
//            $server->push($frame->fd, "server: {$frame->data}");
//        });
//
//        $register->set($register::onClose, function ($ws, $fd) {
//            echo "client-{$fd} is closed\n";
//        });
//        $ChatService = new ChatService();
//        $ChatService->run($register);
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // 跨域
        $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->withHeader('Access-Control-Allow-Origin', '*');
        $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, OPTIONS, DELETE');
        $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        if ($request->getMethod() === 'OPTIONS') {
            $response->withStatus(200);
            return false;
        }

        // 设置请求的参数
        $request->withAttribute('request_time', microtime(true));

        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }

    /**
     * @desc 代码热加载
     * @author Huangbin <huangbin2018@qq.com>
     */
    protected static function hotReload()
    {
        $hotReloadOptions = new \EasySwoole\HotReload\HotReloadOptions;
        // 虚拟机中可以关闭Inotify检测
        $hotReloadOptions->disableInotify(true);
        // 可以设置多个监控目录的绝对路径
        $hotReloadOptions->setMonitorFolder([dirname(__FILE__) . '/App']);
        // 忽略某些后缀名不去检测
        $hotReloadOptions->setIgnoreSuffix(['log', 'txt']);
        // 自定义检测到变更后的事件
        $hotReloadOptions->setReloadCallback(function (\Swoole\Server $server) {
            echo "File change event triggered" . PHP_EOL;  // 可以执行如清理临时目录等逻辑
            $server->reload();  // 接管变更事件 需要自己执行重启
        });
        $hotReload = new \EasySwoole\HotReload\HotReload($hotReloadOptions);
        $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App']);
        $server = ServerManager::getInstance()->getSwooleServer();
        $hotReload->attachToServer($server);
    }
}