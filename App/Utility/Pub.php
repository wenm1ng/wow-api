<?php

namespace App\Utility;

use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Http\Request;
use EasySwoole\EasySwoole\Core;
use EasySwoole\EasySwoole\Task\TaskManager;

/**
 * @desc     辅助类
 * @author   Huangbin <huangbin2018@qq.com>
 * @date     2020/3/22 14:43
 * @package  App\Utility
 */
class Pub
{
    /**
     * @desc 获取运行模式
     * @return string
     */
    static function getRunMode(): string
    {
        return Config::getInstance()->getConf('RUN_MODE') ?? '';
    }

    /**
     * @desc 是否为开发模式
     * @return bool
     */
    static function isDev(): bool
    {
        return Config::getInstance()->getConf('RUN_MODE') == AppConst::RM_DEV;
    }

    /**
     * @desc 格式化时间
     * @param string $format
     * @param float|null $utimestamp
     * @return string|null
     */
    static function udate(string $format = 'Y-m-d H:i:s.u', ?float $utimestamp = null): ?string
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);
        $res = date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
        return $res ? $res : null;
    }

    /**
     * @desc 客户端请求 IP
     * @param Request $request
     * @param string $headerName
     * @return mixed
     */
    static function clientIp(Request $request, $headerName = 'x-real-ip')
    {
        $server = ServerManager::getInstance()->getSwooleServer();
        $client = $server->getClientInfo($request->getSwooleRequest()->fd);
        $clientAddress = $client['remote_ip'];
        $xri = $request->getHeader($headerName);
        $xff = $request->getHeader('x-forwarded-for');
        if ($clientAddress === '127.0.0.1') {
            if (!empty($xri)) {  // 如果有xri 则判定为前端有NGINX等代理
                $clientAddress = $xri[0];
            } elseif (!empty($xff)) {  // 如果不存在xri 则继续判断xff
                $list = explode(',', $xff[0]);
                if (isset($list[0])) {
                    $clientAddress = $list[0];
                }
            }
        }
        return $clientAddress;
    }

    /**
     * 数组分组
     *
     * @param array  $array
     * @param string $column
     *
     * @return mixed
     */
    static function arrayByGroup(array $array,string $column)
    {
        $newArray=[];
        foreach ($array as $key=>$value){
            $newArray[$value[$column]][]=$value;
        }
        return $newArray;
    }

    /**
     * 重试投递任务
     *
     * @param       $task
     * @param int   $retryNum 重试次数
     * @param float $sleepTime 间隔时间（秒）
     */
    static function retryTaskAsysc($task,$retryNum=0,$sleepTime=0.5)
    {
        go(function () use ($task,$retryNum,$sleepTime) {
            action:
            $rs = TaskManager::getInstance()->async($task);
            // 重试投递
            if($rs<=0 && $retryNum>0){
                \co::sleep($sleepTime);
                $retryNum--;
                goto action;
            }
        });
    }

    /**
     * @desc 慢请求日志
     * @param Request $request
     */
    static function saveSlowLog(Request $request): void
    {
        $nowTime = microtime(true);
        $reqTime = $request->getAttribute('request_time');
        $second = Config::getInstance()->getConf('app.slow_log.second');
        if (($nowTime - $reqTime) > $second || Core::getInstance()->isDev() || self::isDev()) {
            // 计算一下运行时间
            $runTime = round($nowTime - $reqTime, 6) . ' s';
            // 获取用户IP地址
            $ip = $request->getAttribute('remote_ip');
            // 拼接日志内容
            $data = ['ip' => $ip, 'time' => date('Y-m-d H:i:s', $reqTime), 'runtime' => $runTime, 'uri' => $request->getUri()->__toString()];
            $userAgent = $request->getHeader('user-agent');
            if (is_array($userAgent) && count($userAgent) > 0) {
                $data['user_agent'] = $userAgent[0];
            }
            Logger::getInstance()->log(var_export($data, true), -1, 'Request');
        }
    }

    /**
     * @desc 钉钉信息
     * @param string $msg
     * @param string $type
     * @param int|null $time
     * @param string|null $file
     * @param int|null $line
     * @param string|null $ip
     * @param string|null $uri
     * @param string|null $userAgent
     * @throws \EasySwoole\HttpClient\Exception\InvalidUrl
     */
    static function pushDingtalkMsg(string $msg, string $type = 'debug', int $time = null, string $file = null
        , int $line = null, string $ip = null, string $uri = null, string $userAgent = null): void
    {
        $cf = Config::getInstance();
        $env = $cf->getConf('RUN_MODE');
        $appName = $cf->getConf('app.name');
        $title = "[{$appName}/{$env}: {$type}] {$msg}";
        $text = ["### {$msg}", '> `App:` ' . $appName, '`Env:` ' . $env, '`Type:` ' . $type,
                 '`Time:` ' . date('Y.n.j H:i:s', isset($time) ? $time : time()),
        ];
        if (isset($file)) {
            $text[] = '`File:` ' . $file;
        }
        if (isset($line)) {
            $text[] = '`Line:` ' . $line;
        }
        if (isset($ip)) {
            $text[] = '`IP:` ' . $ip;
        }
        if (isset($uri)) {
            $text[] = '`Uri:` ' . $uri;
        }
        if (isset($userAgent)) {
            $text[] = '`UserAgent:` ' . $userAgent;
        }
        $body = [
            'msgtype' => 'markdown', 
            'markdown' => [
                'title' => $title, 
                'text' => join('  ' . PHP_EOL, $text)
            ]
        ];
        \App\Utility\HttpClient::getInstance()->post($cf->getConf('app.dingtalk.uri'), [
            'body' => json_encode($body),
            'timeout' => 3, 'headers' => [
                'Content-Type' => 'application/json; charset=utf-8'
            ],
        ]);
    }
    
    // =========================== 密码相关 ===========================
    public static function getPasswordSalt()
    {
        return substr(str_pad(dechex(mt_rand()), 8, '0', STR_PAD_LEFT), -8);
    }

    public static function getPasswordHash($salt, $password)
    {
        return $salt . (hash('whirlpool', $salt . $password));
    }

    public static function comparePassword($password, $hash)
    {
        $salt = substr($hash, 0, 8);
        return $hash == self::getPasswordHash($salt, $password);
    }

    public static function getHash($password)
    {
        return self::getPasswordHash(self::getPasswordSalt(), $password);
    }
    // =========================== 密码相关 END ===========================

    /**
     * @desc 字符串解密加密
     * @author Huangbin <huangbin2018@qq.com>
     * @param string $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @return bool|string
     */
    public static function authCode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 6; // 随机密钥长度 取值 0-32;
        // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // 当此值为 0 时，则不产生随机密钥

        $key = md5($key ? $key : 'EC');
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
}
