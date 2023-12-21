<?php
/**
 * Class Logger
 * @Auhtor zp
 * @Time 2021/11/2 9:18
 */

namespace App\Utility\Logger;

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger as EasyLogger;

/**
 * 日志封装
 * Class Logger
 * @method static info($message, ?string $filename = 'app', bool $isConsole = false)
 * @method static notice($message, ?string $filename = 'app', bool $isConsole = false)
 * @method static warning($message, ?string $filename = 'app', bool $isConsole = false)
 * @method static error($message, ?string $filename = 'app', bool $isConsole = false)
 * @method static console($message)
 * @package App\Utility\Logger
 */
class Logger
{
    protected static $defaultMethod = [
        'info','notice','warning','error'
    ];

    public static function __callStatic($name, $arguments)
    {
        $logger = EasyLogger::getInstance();
        if (count($arguments) <= 0){
            throw new \Exception('Log Content Is Empty');
        }
        //日志内容
        $content = current($arguments);
        $content = is_array($content) ? var_export($content,true): $content;

        //输出到前台
        $console = self::getConsole($arguments);
        $environment = Config::getInstance()->getConf('app.environment');
        if ($console && $environment != 'pro'){
            $logger->console($content);
        }

        if ($name == 'console'){
            $logger->console($content);
            return;
        }

        $filename = 'app';
        if (isset($arguments[1]) && is_string($arguments[1]) && $arguments[1]){
            $filename = $arguments[1];
        }

        if (!in_array($name, self::$defaultMethod)){
            $filename = $name;
        }
        if ($name == 'error' && strpos(strtolower($filename),'error') === false){
            $filename .= 'Error';
        }

        $filePath = self::getDir() . '/' . $filename;

        $logger->log($content, self::getLogLevel($name), $filePath);
    }

    /**
     * 获取错误等级
     * @param string $name
     * @return int
     */
    private static function getLogLevel(string $name)
    {
        $method = self::getMethod($name);
        $level = EasyLogger::LOG_LEVEL_INFO;
        switch ($method){
            case 'notice':
                $level = EasyLogger::LOG_LEVEL_NOTICE;
                break;
            case 'warning':
                $level = EasyLogger::LOG_LEVEL_WARNING;
                break;
            case 'error':
                $level = EasyLogger::LOG_LEVEL_ERROR;
                break;
            default:
                break;
        }
        return $level;
    }

    /**
     * 获取日志类型
     * @param string $name
     * @return string
     */
    private static function getMethod(string $name): string
    {
        $name = strtolower($name);
        if (strpos($name,'notice') !== false){
            $method = 'notice';
        }elseif (strpos($name,'warning') !== false){
            $method = 'warning';
        }elseif (strpos($name,'error') !== false){
            $method = 'error';
        }else{
            $method = 'info';
        }
        return $method;
    }

    /**
     * 判断是否打印日志到前台
     * @param array $arguments
     * @return bool
     */
    private static function getConsole(array $arguments): bool
    {
        $argCount = count($arguments);
        if ($argCount == 3){
            $console = $arguments[2] === true;
        }elseif ($argCount == 2){
            $console = is_string($arguments[1]) ? false : ($arguments[1] === true);
        }else{
            $console = false;
        }
        return $console;
    }

    /**
     * 这里可以根据配置来确定日志分隔；默认按天分割日志
     * @return false|string
     */
    private static function getDir()
    {
        return date('Ymd');
    }
}