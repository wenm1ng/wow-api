<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-20 22:44
 */

use EasySwoole\EasySwoole\Config;
use Common\Extend\Warning;
use EasySwoole\RedisPool\Redis;
use EasySwoole\RedisPool\RedisPool;
use EasySwoole\Redis\Config\RedisConfig;
use App\Lib\AliFanyi\Common as AliFanyCommon;
use App\Lib\AliFanyi\Validate\TranslateValidate;
use Common\Code\CodeKey;
use App\Utility\Company;
use EasySwoole\HttpClient\HttpClient;
use App\Work\Config as workConfig;
use App\Work\Lib\SensitiveWords\TrieTree;

//use App\Pool\RedisPool;
/* 谷歌翻译CURL请求（不用代理）
 * @param $text String required  翻译的字符串
 * @param source_text String/Array required 原文 (hello)
 * @param target_lang String required 目标语种 (zh)
 * @return string/Array
 */

function handleErrorMsg($e){
    $errMsg = $e->getMessage();
    $pos = strpos($errMsg, 'called');
    if($pos !== false){
        $errMsg = substr($errMsg, 0, $pos);
    }
    return $errMsg;
}

/**
 * @desc       获取小程序周和年
 * @author     文明<736038880@qq.com>
 * @date       2022-09-14 15:11
 * @param string $dateTime
 *
 * @return array
 */
function getWowWeekYear(string $dateTime){
    $time = strtotime($dateTime);
    $year = date('Y', $time);
    $month = (int)date('m', $time);
    $week = (int)date('W', $time);
    if($week > 40 && $month == 1){
        //第二年的头几天，当成前一年最后一周算
        $year = $year - 1;
    }
    $week = $week + workConfig::$yearLinkWeek[$year];

    return ['year' => $year, 'week' => $week];
}

/**
 * @desc       二维数组根据二级键值排序
 * @author     文明<736038880@qq.com>
 * @date       2022-09-14 16:13
 * @param array  $array
 * @param string $key
 * @param bool   $isAsc
 *
 * @return array
 */
function arrayKeySort(array $array, string $key, $isAsc = false) {
    $sort = $isAsc ? SORT_ASC : SORT_DESC;
    $keysValue = [];
    foreach ($array as $k => $v) {
        $keysValue[$k] = $v[$key];
    }
    array_multisort($keysValue, $sort, $array);
    return $array;
}

/**
 * @desc       获取时间格式（多久前）
 * @author     文明<736038880@qq.com>
 * @date       2022-07-28 17:10
 * @param string $date
 *
 * @return false|string
 */
function getTimeFormat(string $date){
    $time = strtotime($date);
    $minute = 60;
    $hour = $minute * 60;
    $day = $hour * 24;
    $month = $day * 30;
    $year = $day * 365;
    $nowTime = time();
    $diffTime = $nowTime - $time;
    if ($diffTime < 0) {
      return "刚刚";
    }
    $monthEnd = $diffTime / $month;
    $weekEnd = $diffTime / (7 * $day);
    $dayEnd = $diffTime / $day;
    $hourEnd = $diffTime / $hour;
    $minEnd = $diffTime / $minute;
    $yearEnd = $diffTime / $year;
    if ($yearEnd >= 1) {
      $result = date('Y-m-d H:i:s', $time);
    } else if ($monthEnd >= 1) {
      $result = round($monthEnd)."个月前";
    } else if ($weekEnd >= 1) {
      $result = round($weekEnd)."周前";
    } else if ($dayEnd >= 1) {
      $result = round($dayEnd)."天前";
    } else if ($hourEnd >= 1) {
      $result = round($hourEnd)."小时前";
    } else if ($minEnd >= 1) {
      $result = round($minEnd)."分钟前";
    } else {
      $result = "刚刚";
    }
    return $result;
}

function postPageT($text, $targe_lang = 'en', $from_lang = 'auto')
{
    $res = '';
    try {
        $url = "https://translate.google.cn/translate_a";

        if ($url != '' && $text != '') {
            //$to = 'ja';
            //$url = $url . '/single?client=gtx&dt=t&ie=UTF-8&oe=UTF-8&sl=auto&tl=' . $to . '&q=' . $entext;
            $url = $url . '/single?client=gtx&dt=t&ie=UTF-8&oe=UTF-8&sl=' . $from_lang . '&tl=' . $targe_lang;
            $post_data = ['q' => $text];
            $headers = [
                "Content-Type: application/x-www-form-urlencoded",
                "TARGETURL: {$url}",
                "USETYPE: 0"
            ];
            // $post_data = http_build_query($post_data);
            $ch = curl_init();

            $proxyUrl = 'http://sz-listing-dynamic-proxy.eccang.com';
            curl_setopt($ch, CURLOPT_URL, $proxyUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置头信息
            // 执行后不直接打印出来
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // 设置请求方式为post
            curl_setopt($ch, CURLOPT_POST, true);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            // 请求头，可以传数组
            curl_setopt($ch, CURLOPT_HEADER, false);
            // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // 不从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

            $result = curl_exec($ch);
            curl_close($ch);

            \Common\Common::log($result, "Google_translate");
            $result = json_decode($result, true);
            if (!empty($result)) {
                foreach ($result[0] as $k) {
                    $v[] = $k[0];
                }
                $res = implode(" ", $v);
            }


        }
    } catch (\Exception $exception) {
        // 钉钉提示
        throw new \Exception($exception->getMessage());
        return false;
    }

    return $res;

}

/**
 * @desc       　阿里抠图
 * @example    　
 * @author     　文明<wenming@ecgtool.com>
 *
 * @param $imageUrl
 *
 * @return array
 */
function cutout($imageUrl)
{

    include_once(EASYSWOOLE_ROOT . '/App/Lib/AliCutout/com/alibaba/china/openapi/client/example/ExampleFacade.class.php');
    include_once(EASYSWOOLE_ROOT . '/App/Lib/AliCutout/com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyGetParam.class.php');
    include_once(EASYSWOOLE_ROOT . '/App/Lib/AliCutout/com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyPostParam.class.php');
    include_once(EASYSWOOLE_ROOT . '/App/Lib/AliCutout/com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyGetResult.class.php');
    include_once(EASYSWOOLE_ROOT . '/App/Lib/AliCutout/com/alibaba/china/openapi/client/example/param/apiexample/ExampleFamilyPostResult.class.php');

    include_once(EASYSWOOLE_ROOT . '/App/Lib/AliCutout/com/alibaba/openapi/client/entity/ByteArray.class.php');
    include_once(EASYSWOOLE_ROOT . '/App/Lib/AliCutout/com/alibaba/openapi/client/util/DateUtil.class.php');
    $exampleFacade = new ExampleFacade ();
    $exampleFacade->setAppKey("2907901");
    $exampleFacade->setSecKey("g0llQ0LebYna");
    $exampleFacade->setServerHost("gw.open.1688.com");
//you need change this refresh token when you run this example.
//        $testRefreshToken ="6291ea7b-8658-4cea-9e45-b880d66e2d11";

    try {

        $param = new ExampleFamilyGetParam ($imageUrl);
        $param->setFamilyNumber(1);
        $exampleFamilyGetResult = new ExampleFamilyGetResult ();

        $return = $exampleFacade->exampleFamilyGet($param, $exampleFamilyGetResult);

//        if(!empty($result['imageUrl'])){
//
//        }

    } catch (OceanException $ex) {
//        var_dump($ex->getMessage().'_'.$ex->getFile().'_'.$ex->getLine());
//        echo "Exception occured with code[";
//        echo $ex->getErrorCode ();
//        echo "] message [";
//        echo $ex->getMessage ();
//        echo "].";
        $return = ['code' => $ex->getErrorCode(), 'success' => false, 'message' => $ex->getMessage()];
    }
    return $return;
}

/**
 * @desc       　性能测试依赖函数
 * @example    　
 * @author     　文明<wenming@ecgtool.com>
 *
 * @param null $message
 * @param null $color
 * @param null $background
 * @param null $style
 *
 * @return string|void
 */
function terminal_style($message = null, $color = null, $background = null, $style = null)
{
    if (!$message)
        return;

    // Only for terminal
    if (php_sapi_name() !== "cli")
        return $message;

    // Only for linux not for windows (PowerShell)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
        return $message;

    // Detect custom background mode
    if (is_int($color) and $color >= 16) {
        $background = 5;
        $style = 48;
    }

    // Set default
    $color = (!$color) ? 'default' : $color;
    $background = (!$background) ? 'default' : $background;
    $style = (!$style) ? 'default' : $style;
    $code = [];

    $textColorCodes = [
        // Label
        'default' => 39,
        'primary' => 34,
        'success' => 32,
        'info' => 36,
        'warning' => 33,
        'danger' => 31,

        // Colors
        'white' => 97,
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'gray' => 37,

        // Light colors
        'light-gray' => 37,
        'light-red' => 91,
        'light-green' => 92,
        'light-yellow' => 93,
        'light-blue' => 94,
        'light-magenta' => 95,
        'light-cyan' => 96,

        // Dark colors
        'dark-gray' => 90,
    ];

    $backgroundColorCodes = [
        // Label
        'default' => 39,
        'primary' => 44,
        'success' => 42,
        'info' => 46,
        'warning' => 43,
        'danger' => 41,

        // Colors
        'white' => 39,
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'gray' => 47,

        // Light colors
        'light-gray' => 47,
        'light-red' => 101,
        'light-green' => 102,
        'light-yellow' => 103,
        'light-blue' => 104,
        'light-magenta' => 105,
        'light-cyan' => 106,

        // Dark colors
        'dark-gray' => 100,
    ];

    $styleCodes = [
        'default' => 0,
        'bold' => 1,
        'dim' => 2,
        'italic' => 3,
        'underlined' => 4,
        'blink' => 5,
        'reverse' => 7,
        'hidden' => 8,
        'password' => 8,
    ];

    // Set style
    if (is_int($style))
        $code[] = $style;
    elseif (isset($styleCodes[$style]))
        $code[] = $styleCodes[$style];
    else {
        print_r(array_keys($backgroundColorCodes));
        die(' > terminal_style(): Text style "' . $style . '" does not exist. You can only use the text styles above' . PHP_EOL);
    }

    // Set background color
    if (is_int($background))
        $code[] = $background;
    elseif (isset($backgroundColorCodes[$background]))
        $code[] = $backgroundColorCodes[$background];
    else {
        print_r(array_keys($backgroundColorCodes));
        die(' > terminal_style(): Background color "' . $background . '" does not exist. You can only use the background colors above' . PHP_EOL);
    }

    // Set text color
    if (is_int($color))
        $code[] = $color;
    elseif (isset($textColorCodes[$color]))
        $code[] = $textColorCodes[$color];
    else {
        print_r(array_keys($textColorCodes));
        die(' > terminal_style(): Text color "' . $color . '" does not exist. You can only use the following text colors' . PHP_EOL);
    }

    // Set background
    return "\e[" . implode(';', $code) . "m" . $message . "\e[0m";
}

function getIp()
{
    $ret = "0.0.0.0";
    $list = swoole_get_local_ip();

    if (array_key_exists('eth0', $list)) {
        $ret = $list['eth0'];
    } else if (array_key_exists('enp0s8', $list)) {
        $ret = $list['enp0s8'];
    } else if (array_key_exists('enp0s3', $list)) {
        $ret = $list['enp0s3'];
    } else if (count($list) > 0) {
        $ret = reset($list);
    }

    return $ret;
}

/**
 * @desc       保存网络图片
 * @author     文明<736038880@qq.com>
 * @date       2022-08-02 15:24
 * @param $url
 * @param $dir
 *
 * @return string
 */
function saveInterImage($url, $dir)
{
    $file = file_get_contents($url);

    $filend = pathinfo($url, PATHINFO_EXTENSION);
    dump($filend);
    $path = '/data/www/image' . $dir;
    if (!file_exists($path)) {
        mkdir($path, true, 0777);
    }
    $fileName = $path . '/' . random(20) . '.' . $filend;
    file_put_contents($fileName, $file);
    return $fileName;
}

/**
 * @desc       保存文件流图片
 * @author     文明<736038880@qq.com>
 * @date       2022-08-02 15:24
 * @param $file
 * @param $dir
 * @param $filend
 *
 * @return string
 */
function saveFileDataImage($file, $dir, $filend)
{
    $path = '/data/www/image' . $dir;
    if (!file_exists($path)) {
        mkdir($path, true, 0777);
    }
    $fileName = $path . '/' . random(20) . '.' . $filend;
    file_put_contents($fileName, $file);
    return $fileName;
}

/**
 * @desc       替换图片路径
 * @author     文明<736038880@qq.com>
 * @date       2022-08-02 15:29
 * @param string $imageUrl
 *
 * @return string|string[]
 */
function getInterImageName(string $imageUrl){
    $trim = workConfig::IMAGE_DIR;
    $replace = workConfig::IMAGE_HOST;
    $imageUrl = str_replace($trim, $replace, $imageUrl);
    return $imageUrl;
}

function echoTime(&$time)
{
    $dingUrl = 'https://oapi.dingtalk.com/robot/send?access_token=21ab071034f7b44079e5e41fe1d75a5cc13b49701ab264b7addbc0f5b3b74617';
    warning('日志耗时：' . round(microtime(true) - $time, 3) . '秒', $dingUrl);
    $time = microtime(true);
}

function random($length = 6, $type = 'string', $convert = 0)
{
    $config = array(
        'number' => '1234567890',
        'letter' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'string' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'all' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    );

    if (!isset($config[$type]))
        $type = 'string';
    $string = $config[$type];

    $code = '';
    $strlen = strlen($string) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $string[mt_rand(0, $strlen)];
    }
    if (!empty($convert)) {
        $code = ($convert > 0) ? strtoupper($code) : strtolower($code);
    }
    return $code;
}


/**
 * 告警推送
 *
 * @param string $msg
 * @param string $webhook
 */
function warning($msg = '', $webhook = '')
{
    try {
        $config = [
            'webhook' => !empty($webhook) ? $webhook : Config::getInstance()->getConf('app.dingtalk.url'),
        ];

        Warning::getInstance($config)->createWarning($msg);
    } catch (\Exception $e) {
    }

}

/**
 * 告警推送
 *
 * @param string $msg
 * @param string $webhook
 */
function warnings($msg = '', $webhook = '')
{
    try {
        $config = [
            'webhook' => !empty($webhook) ? $webhook : Config::getInstance()->getConf('app.dingtalk.url'),
        ];

        Warning::getInstance($config)->createWarnings($msg);
    } catch (\Exception $e) {
        if ($e->getCode() == 130101) {
            //需要休息1分钟
            return false;
        }
    }
    return true;
}

/**
 * redis调用
 *
 * @return \EasySwoole\Redis\Redis|null
 */
function redis($pool = "cache")
{
    $redis = RedisPool::defer($pool);
    if(empty($redis)) {
        $config = new RedisConfig(Config::getInstance()->getConf('redis.'.$pool));
        RedisPool::getInstance()->register($config, $pool);
        $redis = RedisPool::getInstance()->getPool($pool)->getObj();
    }
    return $redis;
//    $redis = RedisPool::defer($pool);
//    if(empty($redis)) {
//        return \EasySwoole\Pool\Manager::getInstance()->get($pool)->getObj();
//    }
//    return $redis;
}

/**
 * 获取配置
 *
 * @param string $key
 *
 * @return array|mixed|null
 */
function config(string $key)
{
    return Config::getInstance()->getConf($key);
}

/**
 * 解密
 *
 * @param $string string
 *
 * @return void
 * @author     zhy    find404@foxmail.com
 * @createTime 2021年3月18日 11:44:38
 */
function decodeBase64String($string)
{
//    if (empty($string)) {
//        return $string;
//    }
//
//    if($string == base64_encode(base64_decode($string))){
//        $string = base64_decode($string);
//    }
//
//    return $string;
    $class = new \CloudKit\Tools\Service\StringCompileService();
    return $class->decodeBase64String($string);
}

/**
 * 加密
 *
 * @param $string string
 *
 * @return void
 * @author     zhy    find404@foxmail.com
 * @createTime 2021年3月18日 11:44:31
 */
function encodeBase64String($string)
{
    $class = new \CloudKit\Tools\Service\StringCompileService();
    return $class->encodeBase64String($string);
}

/**
 * CURL请求
 *
 * @param string     $url        请求url地址
 * @param array      $method     请求方法 get post
 * @param null       $postfields post数据数组
 * @param array      $headers    请求header信息
 * @param bool|false $debug      调试开启 默认false
 *
 * @return mixed
 */
function httpRequest($url, $method = 'POST', $postfields = null, $headers = [], $debug = false, $postFieldsType = "json", $ProxyArr = [])
{
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (null !== $postfields) {
                if ($postFieldsType == "json") {
                    $postfields = json_encode($postfields);
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);

                } else {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
            }
            break;
        case 'PUT':
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            if (null !== $postfields) {
                if ($postFieldsType == "json") {
                    $postfields = json_encode($postfields);
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                } else {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? true : false;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/


    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);


    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */


    if (!empty($ProxyArr)) {
        curl_setopt($ci, CURLOPT_PROXY, $ProxyArr['ip']);
        curl_setopt($ci, CURLOPT_PROXYPORT, $ProxyArr['port']);
    }

    $response = curl_exec($ci);
    dump($response);
    $requestinfo = curl_getinfo($ci);
    curl_close($ci);
    if ($debug) {
        echo "=====post data======<br>\r\n";
        dump($postfields);
        echo "=====info===========<br>\r\n";
        dump($requestinfo);
        echo "=====response=======<br>\r\n";
        dump($response);
    }
    // if ($requestinfo['http_code'] == '200') {
    //     return $response;
    // }
    return $response;
}

/**
 * @param source_text String/Array required 原文 (hello)
 * @param target_lang String required 目标语种 (zh)
 * @param source_lang String required 源语种 (en)
 * @param extra_params[identifier] String  eccang 定制用户id
 * @param extra_params[source_format] String 原文格式(text/html)
 * @param extra_params[identifier_type] String 定制用户类型 (eccang)
 * @param extra_params[field_type] String 原文类型(title: 标题/offer: 详描/message: 消息)
 * @param extra Extra 扩展信息
 *
 * @return string/Array
 */
function trans($source_text, $target_lang, $source_lang, $extra_params = [])
{
    return $source_text;
    try {

        $translateLanguageValidate = new TranslateValidate();
        $translateLanguageValidate->change();
        if (!$translateLanguageValidate->validate($extra_params)) {
            throw new \Exception($translateLanguageValidate->getError()->__toString());
        }

        // 数组格式
        if (is_array($source_text)) {
            $separator = isset($extra_params['separator']) ? $extra_params['separator'] : '$;';
            $source_text = implode($separator, $source_text);
        }
        $translateResult = AliFanyCommon::translate(
            isset($extra_params['identifier']) ? $extra_params['identifier'] : 'eccang',
            $target_lang,
            $source_lang,
            $source_text,
            isset($extra_params['source_format']) ? $extra_params['source_format'] : 'text',
            isset($extra_params['identifier_type']) ? $extra_params['identifier_type'] : 'eccang',
            isset($extra_params['field_type']) ? $extra_params['field_type'] : 'title'
        );
        $translateResult = json_decode(json_encode($translateResult), true);

        if (!isset($translateResult['result'])) {
            throw new \Exception($translateResult['msg']);
        }
        $trans_result = isset($separator) ? explode($separator . ' ', $translateResult['result']) : $translateResult['result'];
    } catch (\Exception $exception) {
        // 钉钉提示
        throw new \Exception($exception->getMessage());
    }

    return $trans_result;
}

/**
 * @param string $string 需要加密的字符串
 * @param string $key    密钥
 *
 * @return string
 */

function encrypt($input, $key = "listing", $iv = "eccang")
{
    $key = substr(md5($key), 0, 16);
    $iv = substr(md5($iv), 0, 16);
    $data = base64_encode(openssl_encrypt($input, 'AES-128-CBC', $key, 1, $iv));
    return $data;
}


/**
 * @param string $string 需要解密的字符串
 * @param string $key    密钥
 *
 * @return string
 */
function decrypt($input, $key = "listing", $iv = "eccang")
{
    $key = substr(md5($key), 0, 16);
    $iv = substr(md5($iv), 0, 16);

    $data = openssl_decrypt($input, 'AES-128-CBC', $key, 0, $iv);
    return $data;
}


/**
 * 中间件 CURL 请求
 *
 * @access public
 *
 * @param str $url 发送接口地址
 * @param array/json   $data    要发送的数据
 * @param false/true   $json    false $data数组格式  true $data json格式
 *
 * @return  返回json数据
 */
function httpApiRequest($url, $method = 'POST', $data = null, $json = FALSE, $headers = [])
{
    //创建了一个curl会话资源，成功返回一个句柄；
    $curl = curl_init();
    //设置url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置为FALSE 禁止 cURL 验证对等证书（peer’s certificate）
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    //设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, true);
            break;
        default:
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }

    if (!empty($data)) {
        //设置请求为POST
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300); //最长的可忍受的连接时间
        //设置POST的数据域

        if ($json) {
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data)
                )
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    //设置是否将响应结果存入变量，1是存入，0是直接输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    if ($headers) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    //然后将响应结果存入变量
    $output = curl_exec($curl);
    if ($output === FALSE) {
        $err = curl_errno($curl);
        $inf = curl_getinfo($curl);
        throw new \Exception("CURL FAIL:TIMEOUT=,CURL_ERRNO={$err},CURL_INFO={$inf}");
    }
    curl_close($curl);
    $requestinfo = json_decode($output, true);
    if ($requestinfo['code'] == '200') {
        return $requestinfo;
    } else {
        return $requestinfo;
    }
    return null;
}

/**
 * @desc    获取excel列数对应的列名
 * @example
 *
 * @param $num
 *
 * @return string
 */
function numCovertLetter($num)
{
    if ($num <= 0) {
        throw new \Exception('数字必须大于0');
    } else {
        $str = '';
        while ($num > 0) {
            $res = $num % 26;
            if ($res == 0) {
                $res = 26;
            }
            $str = strtoupper(chr($res + 64)) . $str;
            $num = ($num - $res) / 26;
        }
        return $str;
    }

}

/**
 * @desc    获取最大列数的列名数组
 * @example
 *
 * @param $num
 *
 * @return array
 */
function getExcelColumnByNum($num)
{
    $result = [];
    try {
        for ($i = 1; $i <= $num; $i++) {
            $result[] = numCovertLetter($i);
        }
    } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
    }
    return $result;
}

/**
 * @desc       同时处理多个请求
 * @author     文明<wenming@ecgtool.com>
 * @date       2020-07-03 16:06
 * @example
 *
 * @param array $url_arr
 * @param array $add_args
 * @param array $headers
 *
 * @return array
 */
function curl_mrequest($url_arr = array(), $headers = array(), $add_args = array())
{
    $add_args_default = array('timeout' => 7, 'method' => 'post');
    $add_args = array_merge($add_args_default, $add_args);

    $mh = curl_multi_init(); #初始化一个curl_multi句柄

    //增加多个句柄
    $ch_arr = array();
    foreach ($url_arr as $key => $param) {
        $ch_arr[$key] = curl_init(); #初始化一个curl句柄
        $url = $param["url"];
        $data = $param["data"];
        $proxy = isset($param["proxy"]) ? $param["proxy"] : array(); //是否使用代理

        #根据method参数判断是post还是get方式提交数据
        if (strtolower($add_args['method']) === "get") {
            if ($data) $url = "$url" . (strpos($url, "?") !== false ? "&" : "?") . http_build_query($data);
        } else {
            if ($headers) curl_setopt($ch_arr[$key], CURLOPT_HTTPHEADER, $headers);//
            curl_setopt($ch_arr[$key], CURLOPT_POST, true);
            curl_setopt($ch_arr[$key], CURLOPT_POSTFIELDS, json_encode($data));
        }

        $ch_sets = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 7,
            CURLOPT_TIMEOUT => $add_args['timeout'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0",

        );
        $ssl = preg_match('/^https:\/\//i', $url) ? true : false;
        if ($ssl) {
            $ch_sets[CURLOPT_SSL_VERIFYPEER] = false;
            $ch_sets[CURLOPT_SSL_VERIFYHOST] = false;
        }
        //设置代理
        if ($proxy) {
            $ch_sets[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
            $ch_sets[CURLOPT_PROXY] = $proxy[0]; //代理服务器地址
            $ch_sets[CURLOPT_PROXYPORT] = $proxy[1]; //代理服务器端口
            $ch_sets[CURLOPT_PROXYUSERPWD] = $proxy[2]; //代理验证 账号:密码
        }

        curl_setopt_array($ch_arr[$key], $ch_sets);
        curl_multi_add_handle($mh, $ch_arr[$key]);
    }

    //处理多个请求
    $running = null;
    $curls = array(); #curl数组用来记录各个curl句柄的返回值
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);

    //获取请求后的数据
    $result = array();
    foreach ($ch_arr as $key => $ch) {
        $result[$key] = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
    }

    curl_multi_close($mh); #关闭curl_multi句柄
    return $result;
}

/**
 * @desc       　httpClient客户端下载图片
 * @example    　
 * @author     　文明<wenming@ecgtool.com>
 * @param string $url
 *
 * @return string
 */
function download(string $url, string $saveDir){
    $client = new HttpClient($url);
    $client->setTimeout(20);
    $client->setHeaders([
        'Host' => $url,
        'User-Agent' => 'Chrome/49.0.2587.3',
        'Accept' => '*',
        'Accept-Encoding' => 'gzip'
    ]);
    $filend = pathinfo($url, PATHINFO_EXTENSION);
    if(!file_exists($saveDir)){
        mkdir($saveDir,true, 0777);
    }
    $fileName = $saveDir.'/'.random(11).'.'.$filend;
    $client->download($fileName);
    return $fileName;
}

/**
 * @desc       截取字符串
 * @author     文明<736038880@qq.com>
 * @date       2022-09-01 18:31
 * @param string $str
 * @param int    $len
 *
 * @return string
 */
function mbSubStr(string $str, int $len){
    if(mb_strlen($str) <= $len){
        return $str;
    }
    return mb_substr($str, 0, $len).'...';
}

function httpClientCurl($url, $data = [], $add_args = [], $headers = [])
{
    $client = new HttpClient();
    $client->setContentTypeJson();
    if (isset($add_args['timeout'])) {
        $client->setTimeout($add_args['timeout']);
    }
    if (empty($headers)) {
        $headers = ['Accept:' . '*/*', 'Content-Type:application/json'];
    }
    $newHeader = [];
    foreach ($headers as $header) {
        $temp = explode(':', $header);
        $newHeader[$temp[0]] = $temp[1];
    }
    $client->setHeaders($newHeader, false, false);
//    if (isset($params['cookies'])) {
//        $client->addCookies($params['cookies']);
//    }
    $client->setUrl($url);

    if (!empty($add_args['method']) && $add_args['method'] == 'get') {
        if (!empty($data)) {
            $rs = $client->get($data);
        } else {
            $rs = $client->get();
        }
    } else {
        if (!empty($data)) {
            $rs = $client->post($data);
        } else {
            $rs = $client->post();
        }
    }
    $rsBody = json_decode(strval($rs->getBody()), true);

    return $rsBody;
}

/**
 * @desc       过滤关键词
 * @author     文明<736038880@qq.com>
 * @date       2022-09-02 16:04
 * @param string $txt
 */
function filterSensitiveWords(string $txt){
    $disturbList = workConfig::$sensitiveDisturb;

    $wordObj = new TrieTree($disturbList);

//    $words = $wordObj->search($txt);

    $txt = $wordObj->filter($txt);
    return $txt;
}

/**
 * @desc       搜索关键词
 * @author     文明<736038880@qq.com>
 * @date       2022-09-02 17:40
 * @param string $txt
 *
 * @return array
 */
function searchSensitiveWords(string $txt){
    $disturbList = workConfig::$sensitiveDisturb;

    $wordObj = new TrieTree($disturbList);

    $words = $wordObj->search($txt);

    return $words;
}

function curl_mrequests($url_arr = [], $headers = [], $add_args = [])
{
    $time1 = microtime(true);
    $result = [];
    foreach ($url_arr as $key => $val) {
        dump("当前时间{$time1}******************************************************");
        $client = new HttpClient();
        $client->setContentTypeJson();
        if (isset($add_args['timeout'])) {
            $client->setTimeout($add_args['timeout']);
        }
        if (!empty($headers)) {
            $newHeader = [];
            foreach ($headers as $header) {
                $temp = explode(':', $header);
                $newHeader[$temp[0]] = $temp[1];
            }
            $client->setHeaders($newHeader, false, false);
        }
//    if (isset($params['cookies'])) {
//        $client->addCookies($params['cookies']);
//    }
        $client->setUrl($val['url']);

        if (!empty($add_args['method']) && $add_args['method'] == 'get') {
            if (!empty($val['data'])) {
                $rs = $client->get($val['data']);
            } else {
                $rs = $client->get();
            }
        } else {
            if (!empty($val['data'])) {
                $rs = $client->post($val['data']);
            } else {
                $rs = $client->post();
            }
        }

        $rsBody = json_decode(strval($rs->getBody()), true);

        if (is_array($rsBody) and isset($rsBody['code'])) {
            $result[$key] = json_encode($rsBody);
        }
        $time2 = microtime(true) - $time1;
        dump("耗时{$time2}秒**************************************************");
    }

    return $result;
}

function errReturn($msg = '')
{
    return ['status' => Codekey::FAIL, "data" => [], "msg" => $msg];
}

function sucReturn($msg = '', $data = [])
{
    return ['status' => Codekey::SUCCESS, "data" => $data, "msg" => $msg];
}

function returnSuccess($msg = '', $data = [], $code = CodeKey::SUCCESS)
{
    return [CodeKey::STATE => $code, CodeKey::DATA => $data, CodeKey::MSG => $msg];
}

function returnError($msg, $data = [], $code = CodeKey::FAIL)
{
    return [CodeKey::STATE => $code, CodeKey::DATA => $data, CodeKey::MSG => $msg];
}

/**
 * 判断是否是任务执行器服务器  产品库和刊登
 *
 * @param int  $part  端口
 * @param bool $isDev 是否是测试线
 *
 * @return bool
 */
function isExecutor($part = 9711, $isDev = false)
{

    //目前只让sandbox 生成环境production 跑任务
    $isExecutor = false;
    if ($isDev) {
        $inExecutorPart = [9711, 9701, 9751, 9741];
    } else {
        $inExecutorPart = [9711, 9701, 9751];
    }
    if (in_array($part, $inExecutorPart)) {
        $isExecutor = true;
    }

    return $isExecutor;
}

/**
 * @spec 创建目录
 *
 * @param     $path  路径
 * @param int $mode  规定权限
 *
 * @return bool
 */
function makeUploadPath($path, $mode = 0777)
{
    if (is_dir($path)) {
        return true;
    } else {
        if (mkdir($path, $mode, true)) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 随机字符串
 *
 * @param      $len
 * @param bool $special
 *
 * @return string
 */
function getRandomStr($len, $special = false, $speaialChars = [])
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );

    if ($special) {
        $speaialChars = !empty($speaialChars) ? $speaialChars : [
            "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
            "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
            "}", "<", ">", "~", "+", "=", ",", "."
        ];
        $chars = array_merge($chars, $speaialChars);
    }

    $charsLen = count($chars) - 1;
    shuffle($chars);                            //打乱数组顺序
    $str = '';
    for ($i = 0; $i < $len; $i++) {
        $str .= $chars[mt_rand(0, $charsLen)];    //随机取出一位
    }
    return $str;
}

/**
 * CURL POST
 *
 * @param $url
 * @param $data
 *
 * @return bool|string
 */
function postCurl($url, $data, $headerData = [])
{

    /*$header = array(
        'Accept: multipart/form-data',
    );*/

    $header = array(
        'Content-Type:application/json',
    );
    if (!empty($headerData)) {
        $headerKeys = array_keys($headerData);
        $headerValues = array_values($headerData);
        $headerStr = $headerKeys[0] . ":" . $headerValues[0];
        array_push($header, $headerStr);
    }

    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // 超时设置
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    // 超时设置，以毫秒为单位
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, 5000);

    // 设置请求头
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    try {
        //执行命令
        return curl_exec($curl);
    } finally {
        curl_close($curl);
    }
}

/**
 * 获取系统时间
 *
 * @return int
 * @author     zhy    find404@foxmail.com
 * @createTime 2021年6月7日 14:55:36
 */
function getSystemTime()
{
    $time = 0;
    date_default_timezone_set('Asia/Shanghai');
    $phpTime = time();
    $systemTime = @exec('date +%s');
    if (substr($systemTime, 0, 9) == substr($phpTime, 0, 9)) {
        $time = $phpTime;
    } else {
        $time = $systemTime;
    }

    return $time;
}

/**
 * 获取当前的运行环境
 *
 * @author: Liu kunming
 * Date:2021/6/10
 * @return string
 */
function getRunEnvironment(): string
{
    $environment = [
        'dev' => 'sandbox',
        'pro' => 'production'
    ];
    $configEnv = config('app.environment');
    $envKey = $configEnv == 'pro' ? $configEnv : 'dev';
    return $environment[$envKey];
}


function multiRequest($urlArr, $headers, $param)
{
    $add_args_default = array('timeout' => 7);
    $add_args = array_merge($add_args_default, $param);

    $mh = curl_multi_init(); #初始化一个curl_multi句柄

    //增加多个句柄
    $ch_arr = array();
    $proxy = isset($param["proxy"]) ? $param["proxy"] : []; //是否使用代理

    foreach ($urlArr as $key => $value) {
        $ch_arr[$key] = curl_init(); #初始化一个curl句柄
        $url = $value;
        $data = isset($param["data"]) ? $param["data"] : [];

        #根据method参数判断是post还是get方式提交数据
        $ch_sets = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_TIMEOUT => $add_args['timeout'],
            //CURLOPT_FOLLOWLOCATION => true,
        );

        $ch_sets[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
        $ch_sets[CURLOPT_USERAGENT] = "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0";
        $ch_sets[CURLINFO_HEADER_OUT] = true;

        switch ($param['method']) {
            case "POST":
                $ch_sets[CURLOPT_POST] = $param['method']; /* //设置请求方式 */
                break;
            case 'PUT':
                $ch_sets[CURLOPT_CUSTOMREQUEST] = $param['method']; /* //设置请求方式 */
                break;
            default:
                $ch_sets[CURLOPT_CUSTOMREQUEST] = $param['method']; /* //设置请求方式 */
                break;
        }

        $ch_sets[CURLOPT_MAXREDIRS] = 2;
        $ch_sets[CURLOPT_HTTPHEADER] = $headers[$key];
        $ch_sets[CURLOPT_POSTFIELDS] = json_encode([]);
        $ch_sets[CURLOPT_FOLLOWLOCATION] = 1;
        $ssl = preg_match('/^https:\/\//i', $url) ? true : false;
        if ($ssl) {
            $ch_sets[CURLOPT_SSL_VERIFYPEER] = false;
            $ch_sets[CURLOPT_SSL_VERIFYHOST] = false;
        }
        //设置代理
        if ($proxy) {
            $ch_sets[CURLOPT_PROXY] = $proxy['ip']; //代理服务器地址
            $ch_sets[CURLOPT_PROXYPORT] = $proxy['port']; //代理服务器端口
        }
        curl_setopt_array($ch_arr[$key], $ch_sets);
        curl_multi_add_handle($mh, $ch_arr[$key]);
    }
    //处理多个请求
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);

    //获取请求后的数据
    $result = array();
    foreach ($ch_arr as $key => $ch) {
        $result[$key] = json_decode(curl_multi_getcontent($ch), true);
        curl_multi_remove_handle($mh, $ch);
    }

    curl_multi_close($mh); #关闭curl_multi句柄

    return $result;
}

/**
 * @notes:经常用于sqk主表和扩展表的拼接。根据同一个key，将第二个二维数组拆分并归纳到第一个二维数组的对应元素下面，注意两个二维数组的每一个元素都要有同一个key名。
 * @param string $list1_Name 组装依据的数组一key名
 * @param string $list2_Name 组装依据的数组二key名
 * @param array $list1 第一个二维数组
 * @param array $list2 第二个二维数组
 * @param string $mergeKeyName 归纳的方式，空字符串表示两个素组的元素都在同一级，不为空则表示用一个字段去归纳第二个二维数组的元素
 * @param bool $one2Batch 第一个二维数组和第二个二维数组的对应关系是否是一对多
 * @return array
 */
function mergeList(string $list1_Name = 'id', string $list2_Name = 'id', array $list1 = [], array $list2 = [], string $mergeKeyName = '', bool $one2Batch = false, int $isString = 0)
{
    //两个二维数组的键名替换成对应元素的归纳依据key的值
    $tmpList1 = array_column($list1, null, $list1_Name);
    if ($one2Batch) {
        $tmpList2 = [];
        foreach ($list2 as $v) {
            if (isset($v[$list2_Name])) {
                $tmpList2[$v[$list2_Name]][] = $v;
            }
        }
        $mergeKeyName == '' && $mergeKeyName = 'children';
    } else {
        $tmpList2 = array_column($list2, null, $list2_Name);
    }
    //将第二个二维数组元素归纳到第一个二维数组里面去
    foreach ($tmpList1 as $k => $v) {
        if ($mergeKeyName === '') {
            isset($tmpList2[$k]) && is_array($tmpList2[$k]) && $tmpList1[$k] = array_merge($v, $tmpList2[$k]);
        } else {
            $tmpList1[$k][$mergeKeyName] = $isString ? implode('#', array_column($tmpList2[$k],$mergeKeyName) ?? []) : $tmpList2[$k] ?? [];
        }
    }

    return is_array($tmpList1) ? array_values($tmpList1) : [];
}

