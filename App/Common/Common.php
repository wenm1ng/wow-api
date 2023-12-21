<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-20 22:56
 */
namespace Common;

use EasySwoole\Utility\File;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Core;
use EasySwoole\EasySwoole\Config;
use User\Service\LoginService;
use EasySwoole\Spl\SplContextArray;

Class Common{
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
     * 更换数组的键名
     * @param array $arr
     * @param string $key
     * @param bool $is_multiple
     * @param bool $get_first_item 选择第一个
     * @return array
     */
    public static function arrayKeyChange(array $arr, $key, $is_multiple = false,$get_first_item = false): array
    {
        $result = [];
        if (empty($arr)) {
            return $result;
        }
        if (empty($key)) {
            return $result;
        }
        foreach ($arr as $v) {
            if (!isset($v[$key])) {
                continue;
            }
            if ($is_multiple) {
                $result[$v[$key]][] = $v;
            } else {
                if ($get_first_item){
                    if (isset( $result[$v[$key]])){
                        continue;
                    }else{
                        $result[$v[$key]] = $v;
                    }
                }else{
                    $result[$v[$key]] = $v;
                }
            }
        }
        return $result;
    }

    /******************************很重要勿动（影响walmart刊登）***************************************/
    //是否开启刊登脚本本地调试
    public static function getIsPublishDebug(){
        return false;
    }
    /******************************很重要勿动（影响walmart刊登）***************************************/

    /**
     * 设置多主键，通过_连接
     * @param $data array
     * @param $keys array
     * @param $is_multiple bool
     * @return array
     * @author     zhy    find404@foxmail.com
     * @createTime 2020年8月20日 10:47:17
     */
    public static function arrayKeysChange(array $data, array $keys, bool $is_multiple = false): array
    {
        $result = [];
        if (empty($data)) {
            return $result;
        }
        if (empty($keys)) {
            return $result;
        }

        foreach ($data as $dataVal) {
            $newKey = '';
            foreach ($keys as $keysVal) {
                if (!isset($dataVal[$keysVal])) {
                    continue;
                }
                $newKey .= empty($newKey) ? $dataVal[$keysVal] : '_' . $dataVal[$keysVal];
            }


            if ($is_multiple) {
                $result[$newKey][] = $dataVal;
            } else {
                $result[$newKey] = $dataVal;
            }
        }
        return $result;
    }

    /**
     * 更换数组的键名多维
     * @param unknown $arr
     * @param unknown $key
     * @return multitype:unknown
     */
    public static function multiArrayKeyChanges(array $arr, $key)
    {
        $return = array();
        foreach ($arr as $k => $v) {
            $return[$v[$key]][$k] = $v;
        }
        return $return;
    }

    public static function arrayGroup(array $arr, $groupKey, $beValKey = ''){
        $return = [];
        if($beValKey){
            foreach ($arr as $key => $val) {
                $return[$val[$groupKey]][] = $val[$beValKey];
            }
        }else{
            foreach ($arr as $key => $val) {
                $return[$val[$groupKey]][] = $val;
            }
        }
        return $return;
    }

    /**
     * @desc 获取用户token
     * @return mixed
     */
    public static function getUserToken()
    {
        global $_USER_INFO;
        return $_USER_INFO['token'] ?? "";
    }

    /**
     * @desc userToken
     * @param string $token
     * @return mixed
     */
    public static function setUserToken($token)
    {
        global $_USER_INFO;
        if (!$_USER_INFO instanceof SplContextArray) {
            $_USER_INFO = new SplContextArray();
        }
        $_USER_INFO['token'] = $token;
    }

    public static function setUserId($userId)
    {
        global $_USER_INFO;
        if (!$_USER_INFO instanceof SplContextArray) {
            $_USER_INFO = new SplContextArray();
        }
        $_USER_INFO['user_id'] = $userId;
    }

    public static function getUserInfo()
    {
        $token = Common::getUserToken();
        if($token === 'test_php'){
            $userId = self::getFixUserId();
            $userInfo = ['user_id' => $userId, 'parent_id' => $userId, 'user_name' => 'test_user'];
        }else{
            $userInfo = $token ? (new LoginService())->checkToken($token) : '';
        }
        return $userInfo ?? [];
    }
    public static function getUserId()
    {
        $userInfo = self::getUserInfo();
        $userId = $userInfo['user_id'] ?? 0;

        return $userId;
    }

    public static function getUserOpenId()
    {
        $userInfo = self::getUserInfo();
        $userId = $userInfo['openId'] ?? '';

        return $userId;
    }

    public static function getFixUserId(){
        global $_USER_INFO;
        return $_USER_INFO['user_id'] ?? 2;
    }

    /**
     * 日志
     * @param string|array $content 日志内容
     * @param string $logName 日志文件名
     * @param integer $is_output 是否允许输出
     */
    public static function log($content, $logName = '', $is_allow_console = 0)
    {
        $logSuffix = date("Ymd");
        if ($is_allow_console) {
            if (is_string($content)) {
                Logger::getInstance()->info($content);
            } else {
                Logger::getInstance()->info(print_r($content));
            }
        }
        if (is_string($content)) {
            Logger::getInstance()->log(date('Y-m-d H:i:s') . '----' . $content . '----', 0, $logSuffix.'/' .$logName);
        } else {
            Logger::getInstance()->log(date('Y-m-d H:i:s') . '----' . print_r($content) . '----', 0, $logSuffix.'/' .$logName);
        }

    }

    /**
     * @desc    获取接口参数
     * @param        $obj
     * @param string $paramName
     *
     * @return mixed|string
     * @example
     */
    public static function getHttpParams($obj, $paramName = '')
    {
        $methodName = $obj->getMethod();

        $returnData = [];
        if ($methodName == 'GET') {
            if ($paramName) {
                $returnData = $obj->getRequestParam($paramName);
                $returnData = $returnData ? $returnData : json_decode($obj->getBody()->__toString(), true)[$paramName];
            }
        } else {
            if ($paramName) {
                $returnData = json_decode($obj->getBody()->__toString(), true)[$paramName] ?? '';
                $returnData = $returnData ? $returnData : $obj->getRequestParam($paramName);
            }
        }

        if(!$paramName){
            $returnData = $obj->getRequestParam();
            $jsonData = json_decode($obj->getBody()->__toString(), true);
            if(!empty($jsonData)){
                $returnData = array_merge($returnData, $jsonData);
            }
        }

        return $returnData;
    }

    /**
     * 返回随机字符
     * a-z：97-122，A-Z：65-90，0-9：48-57。
     * @param int $num
     * @param string $str
     */
    public static function getRandStr($num = 1){

        for ($i = 1; $i <= $num; $i++) {
            $str = chr(mt_rand(65, 90));
        }
        return $str;
    }

    /**
     * @return array|bool|mixed|string|null
     * @author xuanqi
     * 生成视频的oss地址
     */
    public static function getOssVideoPath()
    {
        return '/resource/video/' . date("Ymd") . '/' . date("YmdHis") . self::getRandomCode(6, 'letter');
    }

    public static function getVideoMaxSize()
    {
        return \config('app.videoMaxSize') ?: 209715200;
    }

    /**
     * 搜索key
     * @param $dataArray
     * @param $key_to_search
     * @return array
     */
    public static function searchArray($dataArray, $keSearch) {

        $ret = [];
        foreach ($dataArray as $key => $value) {

            if (is_array($value) && count($value) > 0) {
                $_item = self::searchArray($value,$keSearch);
                if($_item) {
                    return $_item;
                }
            } else {
                if(strpos($key,$keSearch) !== false) {
                    $newKey = explode('=',$key);
                    $ret[$key] = $value;
                    $ret['unitCount'] = $value;
                    $ret['countUnit'] = $newKey[1];
                    break;
                }
            }
        }

        return $ret;
    }
}