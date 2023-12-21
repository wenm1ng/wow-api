<?php

namespace App\Utility;

use App\Utility\Pool\MongodbObject;
use EasySwoole\EasySwoole\Core;
use EasySwoole\EasySwoole\Config;
use App\Utility\Pool\RedisObject;
use App\Utility\Pool\MysqlObject;
use EasySwoole\Pool\Manager as PoolManager;
use App\Utility\Pool\Redis\RedisPool;
use App\Utility\Pool\Mysql\MysqlPool;
use App\Utility\Pool\Mongodb\MongodbPool;
use EasySwoole\Utility\File;
use EasySwoole\EasySwoole\Logger;

/**
 * @desc     公共方法类
 * @author   Huangbin <huangbin2018@qq.com>
 * @date     2020/3/25 14:42
 * @package  App\Utility
 */
class Common
{
    /**
     * 重试次数
     * @var int 
     */
    static $tryTimes = 3;

    /**
     * 获取 Mysql 连接(从连接池中获取)
     * @param string $companyCode 配置文件标示mysql不同类别的KEY
     * @param string $type 多个数据库连接标示
     * @param \Closure $registerCallBack 注册数据库连接回调
     * @return MysqlObject|null
     * @throws
     */
    public static function getMysql($companyCode, $type = 'default',$registerCallBack = null)
    {
        $poolKey = $companyCode . $type . Defines::MYSQL_POOL_KEY;
        for ($i = 0; $i < self::$tryTimes; $i++) {
            if(PoolManager::getInstance()->get($poolKey) === null){
                if($registerCallBack !== null){
                    $registerCallBack();
                } else {
                    $redis = self::getRedis('cache');
                    $mysqlConf = $redis->get(strtolower(Config::getInstance()->getConf('SERVER_NAME')) . ':mysql:' . $companyCode);
                    if(!$mysqlConf || ($mysqlConf = @json_decode($mysqlConf,true)) === null || !isset($mysqlConf[$type])){
                        Logger::getInstance()->log("get redis config error", Logger::LOG_LEVEL_INFO, 'register_mysql_pool');
                        continue;
                    }
                    $mysqlConf = $mysqlConf[$type];
                    self::registerMysqlPool($companyCode, $mysqlConf, $type);
                }
            }

            $mysql = PoolManager::getInstance()->get($poolKey)->defer();
            if ($mysql instanceof MysqlObject) {
                $fastReleaseMysqlConnect = intval(Context::getContext('fastReleaseMysqlConnect' . $mysql->getPoolKey()));
                Context::setContext( 'fastReleaseMysqlConnect' . $mysql->getPoolKey(),++$fastReleaseMysqlConnect);
                return $mysql;
            }
        }
        return null;
    }

    /**
     * 注册数据库连接池
     * @param string $companyCode
     * @param mixed $mysqlConf
     * @throws
     */
    public static function registerMysqlPool($companyCode,$mysqlConf,$type = 'default'): void
    {
        $poolKey = $companyCode . $type. Defines::MYSQL_POOL_KEY;
        $poolConf = $mysqlConf['pool'];

        $pool = PoolManager::getInstance();
        $config = new \EasySwoole\Pool\Config();
        $config->setMaxIdleTime($poolConf['idletime'] ?: 30)
            ->setMinObjectNum($poolConf['minnum'] ?: 2)
            ->setMaxObjectNum($poolConf['maxnum'] ?: 8)
            ->setGetObjectTimeout($poolConf['timeout'] ?: 10)
            ->setIntervalCheckTime($poolConf['checktime'] ?: 60000)
            ->setAutoPing($poolConf['autoping'] ?? 10);
        $pool->register(new MysqlPool($config, $mysqlConf,$poolKey), $poolKey);
    }

    /**
     * @desc 获取 Redis 连接(从连接池中获取)
     * @param mixed $key 配置文件标示redis不同类别的KEY
     * @return RedisObject|null
     * @throws
     */
    public static function getRedis($key = 'cache')
    {
        $poolKey = $key . Defines::REDIS_POOL_KEY;
        for ($i = 0; $i < self::$tryTimes; $i++) {
            if(PoolManager::getInstance()->get($poolKey) === null){
                self::registerRedisPool($key);
            }

            $redis = PoolManager::getInstance()->get($poolKey)->defer();
            if ($redis instanceof RedisObject) {
                return $redis;
            }
        }
        
        return null;
    }

    /**
     * 注册redis连接池
     * @param mixed $key
     * @throws \EasySwoole\Pool\Exception\Exception
     */
    public static function registerRedisPool($key = null): void
    {
        $pool = PoolManager::getInstance();
        $config = new \EasySwoole\Pool\Config();
        $poolKey = $key . Defines::REDIS_POOL_KEY;
        $redisConf = Config::getInstance()->getConf('redis.' . $key);
        $poolConf = $redisConf['pool'];

        $config->setMaxIdleTime($poolConf['idletime'])
            ->setMinObjectNum($poolConf['minnum'])
            ->setMaxObjectNum($poolConf['maxnum'])
            ->setGetObjectTimeout($poolConf['timeout'])
            ->setIntervalCheckTime($poolConf['checktime']);
        $pool->register(new RedisPool($config, $redisConf), $poolKey);
    }


    /**
     * @desc 获取 mongodb 连接(从连接池中获取)
     * @param mixed $key
     * @return MongodbObject
     * @throws
     */
    public static function getMongodb($key = 'default')
    {
        $poolKey = $key . Defines::MONGODB_POOL_KEY;
        for ($i = 0; $i < self::$tryTimes; $i++) {
            if(PoolManager::getInstance()->get($poolKey) === null){
                self::registerMongodbPool($key);
            }

            $mongodb = PoolManager::getInstance()->get($poolKey)->defer();
            if ($mongodb instanceof MongodbObject) {
                return $mongodb;
            }
        }

        return null;
    }

    /**
     * 注册mongodb连接池
     * @param mixed $key
     * @throws \EasySwoole\Pool\Exception\Exception
     */
    public static function registerMongodbPool($key = null): void
    {
        $pool = PoolManager::getInstance();
        $config = new \EasySwoole\Pool\Config();
        $poolKey = $key . Defines::MONGODB_POOL_KEY;
        $mongodbConf = Config::getInstance()->getConf('mongodb.' . $key);
        $poolConf = $mongodbConf['pool'];

        $config->setMaxIdleTime($poolConf['idletime'])
            ->setMinObjectNum($poolConf['minnum'])
            ->setMaxObjectNum($poolConf['maxnum'])
            ->setGetObjectTimeout($poolConf['timeout'])
            ->setIntervalCheckTime($poolConf['checktime']);
        $pool->register(new MongodbPool($config, $mongodbConf), $poolKey);
    }

    /**
     * @desc 设置错误显示
     * @author Huangbin <huangbin2018@qq.com>
     */
    public static function setErrorReporting()
    {
        if (Core::getInstance()->isDev()) {
            ini_set('display_errors', 'On');
            error_reporting(-1);
        } else {
            ini_set('display_errors', 'Off');
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
    }

    /**
     * 加载配置
     */
    public static function loadConf()
    {
        if( Core::getInstance()->isDev()){
            $files = File::scanDirectory(EASYSWOOLE_ROOT . '/App/ConfigDev');
        } else {
            $files = File::scanDirectory(EASYSWOOLE_ROOT . '/App/Config');
        }

        if (is_array($files)) {
            foreach ($files['files'] as $file) {
                $fileNameArr = explode('.', $file);
                $fileSuffix = end($fileNameArr);
                if ($fileSuffix == 'php') {
                    Config::getInstance()->loadFile($file);
                }
            }
        }
    }

    /**
     * @desc  返回成功
     * @param string $msg
     * @param null $data
     * @param array $options
     * @return array
     */
    public static function returnSuccess($msg = '', $data = null, $options= [])
    {
        $return = [
            'state'     => Code::IS_SUCCESS,
            'message'   => $msg ?: 'isSuccess',
            'data'      => $data ?: [],
        ];

        if ($options) {
            $return = array_merge($return, $options);
        }
        
        return $return;
    }

    /**
     * @desc  返回失败
     * @param string $msg
     * @param null $errors
     * @param null $data
     * @param array $options
     * @return array
     */
    public static function returnFail($msg = '', $errorCode = null, $errors = null, $data = null, $options= [])
    {
        $return = [
            'state'         => Code::IS_FAIL,
            'error_code'    => $errorCode ?: 0,
            'message'       => $msg ?: 'isFail',
            'data'          => $data ?: [],
            'error'         => $errors ?: [],
        ];

        if ($options) {
            $return = array_merge($return, $options);
        }

        return $return;
    }

    /**
     * @desc  校验结果
     * @param array $result
     * @return bool
     */
    public static function checkResult($result = [])
    {
        return isset($result['state']) && $result['state'] == Code::IS_SUCCESS ? true : false;
    }

    /**
     * @desc 除法运算
     * eg：div('105', '6.55957', 3);  // 16.007
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $leftOperand 除数
     * @param string $rightOperand 被除数
     * @param int $scale 保留小数位数
     * @return int|string|null
     */
    public static function div($leftOperand = '', $rightOperand = '', $scale = 4)
    {
        $ret = bcdiv((string)$leftOperand, (string)$rightOperand, $scale);
        return $ret === null ? 0 : $ret;
    }

    /**
     * @desc 乘法运算
     * eg：mul('1.34747474747', '35', 3); // 47.161
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $leftOperand 乘数
     * @param string $rightOperand 被乘数
     * @param int $scale 保留小数位数
     * @return int|string|null
     */
    public static function mul($leftOperand = '', $rightOperand = '', $scale = 4)
    {
        $ret = bcmul((string)$leftOperand, (string)$rightOperand, $scale);
        return $ret === null ? 0 : $ret;
    }

    /**
     * @desc 加法运算
     * eg：add('1.234', '5', 4);  // 6.2340
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $leftOperand 左操作数
     * @param string $rightOperand 右操作数
     * @param int $scale 保留小数位数
     * @return int|string|null
     */
    public static function add($leftOperand = '', $rightOperand = '', $scale = 4)
    {
        $ret = bcadd((string)$leftOperand, (string)$rightOperand, $scale);
        return $ret;
    }

    /**
     * @desc 减法运算，左操作数减去右操作数.
     * eg：sub('1.234', '5', 4);  // -3.7660
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $leftOperand 左操作数
     * @param string $rightOperand 右操作数
     * @param int $scale 保留小数位数
     * @return int|string|null
     */
    public static function sub($leftOperand = '', $rightOperand = '', $scale = 4)
    {
        $ret = bcsub((string)$leftOperand, (string)$rightOperand, $scale);
        return $ret;
    }

    /**
     * @desc 比较两个任意精度的数字，把 $leftOperand 与 $rightOperand 作比较, 并且返回一个整数的结果
     * eg：comp('1', '2');   // -1
     * eg：comp('1.00001', '1', 3); // 0
     * eg：comp('1.00001', '1', 5); // 1
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $leftOperand 左操作数
     * @param string $rightOperand 右操作数
     * @param int $scale 保留小数位数
     * @return int 如果两个数相等返回 0, 左边的数 $leftOperand 比右边的数 $rightOperand 大返回 1, 否则返回 -1
     */
    public static function comp($leftOperand = '', $rightOperand = '', $scale = 4)
    {
        $ret = bccomp((string)$leftOperand, (string)$rightOperand, $scale);
        return $ret;
    }

    /**
     * @desc 检查路径是否为 URL 格式
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $path
     * @param bool $canConnect 检查是否有效
     * @return bool
     */
    public static function isUrl($path = '', $canConnect = false)
    {
        $flag = false;
        $pattern = "/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
        if (preg_match($pattern, $path)) {
            $flag = true;
        }

        if ($flag && $canConnect) {
            $flag = self::checkHostConnection($path);
        }

        return $flag;
    }

    /**
     * @desc 检测 URL 是否可连接
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $url
     * @return bool
     */
    public static function checkHostConnection($url = '')
    {
        try {
            $host = $url;
            if (empty($host)) {
                return false;
            }
            $ch = curl_init();
            $timeout = 2;
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_URL, $host);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            return $httpCode == 200;
        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 获取远程文件url文件的大小
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $url 远程文件 url 地址
     * @return int 返回 -1 为失败
     */
    public static function curlGetFileSize($url = '') {
        $result = -1;
        if (empty($url)) {
            return $result;
        }
        
        if (!self::isUrl() && file_exists($url)) {
            return filesize($url);
        }
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        if($data) {
            $contentLength = -1;
            $status = '--';
            if(preg_match("/^HTTP\/1.[01] (\d\d\d)/", $data, $matches)) {
                $status = (int)$matches[1];
            }
            if(preg_match("/Content-Length: (\d+)/", $data, $matches)) {
                $contentLength = (int)$matches[1];
            }
            if($status == 200 || ($status> 300 && $status <= 308)) {
                $result = $contentLength;
            }
        }

        return $result;
    }

    /**
     * @desc 获取文件内容
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $file 文件路径, url or 本地文件路径
     * @param int $timeOut 超时时间
     * @param array $proxy 支持代理 ['proxy' => '127.0.0.1:8888', 'proxy_auth' => 'user:password', 'proxy_type' => CURLPROXY_HTTP]
     * @return bool|false|string 文件内容
     */
    public static function getFileContents($file = '', $timeOut = 600, $proxy = [])
    {
        $content = '';
        if (self::isUrl($file)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $file);
            curl_setopt($ch, CURLOPT_TIMEOUT,$timeOut);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            if (isset($proxy['proxy']) && !empty($proxy['proxy'])) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy['proxy']);
                if (isset($proxy['proxy_auth']) && !empty($proxy['proxy_auth'])) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['proxy_auth']);
                }
                if (isset($proxy['proxy_type']) && !empty($proxy['proxy_type'])) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy['proxy_type']);
                }
                // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }
            $content = curl_exec($ch);
            curl_close($ch);
        } elseif (file_exists($file)) {
            $content = file_get_contents($file);
        }

        return $content ? $content : '';
    }

    /**
     * @desc
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $file 文件路径, url or 本地文件路径
     * @param int $tryCount 尝试次数
     * @param array $proxy 支持代理 ['proxy' => '127.0.0.1:8888', 'proxy_auth' => 'user:password', 'proxy_type' => CURLPROXY_HTTP]
     * @return bool|false|string 成功则返回文件内容，失败返回 false
     */
    public static function getFileContentsAndCheck($file = '', $tryCount = 3, $proxy = [])
    {
        $orgFile = $file;
        $lenCache = [];
        // 没有使用代理、且为配置关闭替换时，进行 url 替换
        if (!isset($proxy['proxy']) || empty($proxy['proxy'])) {
            // OAPI增加了代理，仓配只替换标签和发票链接的域名即可
            $pos = stripos($file, '/us_oapi/');
            if ($pos !== false) {
                $fileTmp = 'http://us-oss.oapi.eccang.com' . substr($file, $pos);
                $len = self::curlGetFileSize($fileTmp);
                if ($len > 500) {
                    $file = $fileTmp;
                    $lenCache[md5($file)] = $len;
                }
            }
        }

        $tryCount = is_numeric($tryCount) ? $tryCount : 3;
        do {
            $tryCount --;
            // 最后一次尝试使用原 URL
            if ($tryCount == 0) {
                $file = $orgFile;
            }
            try {
                $len = 0;
                $content = self::getFileContents($file, 600, $proxy);
                if (!$content) {
                    throw new \Exception('下载文件内容为空 => ' . $file);
                }
                if (self::isUrl($file)) {
                    if (isset($lenCache[md5($file)]) && !empty($lenCache[md5($file)])) {
                        $len = $lenCache[md5($file)];
                    } else {
                        // URL
                        $len = self::curlGetFileSize($file);
                        $lenCache[md5($file)] = $len;
                    }
                    if ($len == -1) {
                        $len = strlen(self::getFileContents($file, 600, $proxy));
                    }
                } elseif (file_exists($file)) {
                    // 文件
                    $len = filesize($file);
                }

                if (strlen($content) != $len) {
                    throw new \Exception('下载文件内容长度校验失败', 200);
                }

                return $content;
            } catch (\Exception $e) {
                $code = $e->getCode();
                self::myEcho($code . ' => ' . $e->getMessage());
            }

            if ($tryCount <= 0) {
                break;
            }
        } while ($tryCount > 0);

        return false;
    }

    /**
     * @desc 控制台输出
     * @author Huangbin <huangbin2018@qq.com>
     * @param mixed $str
     * @param int $level
     */
    public static function myEcho($str, $level = 0) {
        if (empty($str) || PHP_SAPI != 'cli') {
            return;
        }
        if (!is_string($str) && !is_numeric($str)) {
            $str = print_r($str, true);
        }
        if ($level == 1) {
            Logger::getInstance()->setLogConsole(false)->notice($str);
        } elseif ($level == 2) {
            Logger::getInstance()->setLogConsole(false)->waring($str);
        } elseif ($level == 3) {
            Logger::getInstance()->setLogConsole(false)->error($str);
        } else {
            Logger::getInstance()->setLogConsole(false)->info($str);
        }
        Logger::getInstance()->setLogConsole(true);
    }

    /**
     * @desc 通过 OSS 链接返回区域 
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $url
     * @return string [huanan, huadong, hk, us]
     */
    public static function getOssRegion($url = '')
    {
        // http://erp-huanan.oss-cn-shenzhen.aliyuncs.com/wms/sky/pdf/2020/07/09/data/pdf/spe20070891662.pdf
        $bool = preg_match('/oss-(.+)\.com/i', $url, $match);
        if (!$bool) {
            return '';
        }
        $host = $match[0] ?? '';
        if (empty($host)) {
            return ''; 
        }
        switch (true) {
            case stripos($host, 'cn-shenzhen') !== false:
                return 'huanan';
            case stripos($host, 'cn-hangzhou') !== false:
                return 'huadong';
            case stripos($host, 'cn-hongkong') !== false:
                return 'hk';
            case stripos($host, 'us') !== false:
                return 'us';
            default:
                return '';
        }
    }

    /**
     * job 处理任务锁定，避免多集群并发，k8s中避免多pod并发
     * @param string $key 锁使用的key
     * @param string $type (cluster,pod)
     * @param int $exp 锁定时间 单位：秒
     * @param string $redisConfigKey
     * @return bool
     */
    public static function getClusterLock($key,$type= 'cluster', $exp = 60, $redisConfigKey = 'cache')
    {
        $redis = Common::getRedis($redisConfigKey);
        if($type == 'cluster'){
            $rKey = strtolower(Config::getInstance()->getConf('SERVER_NAME')) . ':job_cluster_lock:' . $key;
        } else {
            $clusterId = Config::getInstance()->getConf('CLUSTER.CLUSTER_ID');
            $rKey = strtolower(Config::getInstance()->getConf('SERVER_NAME')) . ":job_pod_lock_{$clusterId}:" . $key;
        }

        $lockIdVal = $redis->incr($rKey);
        if($lockIdVal > 1){
            return false;
        } else {
            $redis->expire($rKey,$exp);
            return true;
        }
    }
}
