<?php

namespace App\Utility;

use EasySwoole\Spl\SplContextArray;

/**
 * @desc 自定义上下文环境
 */
class Context
{
    /**
     * @desc 获取上下文环境
     * @param string $key
     * @return mixed
     */
    public static function getContext($key)
    {
        if (!$key){
            return null;
        }
        global $_MY_CONTEXT;
        return $_MY_CONTEXT[$key];
    }

    /**
     * @desc 设置上下文环境
     * @param string $key
     * @param mixed $val
     */
    public static function setContext($key,$val)
    {
        global $_MY_CONTEXT;
        if(!$_MY_CONTEXT instanceof SplContextArray){
            $_MY_CONTEXT = new SplContextArray();
        }
        $_MY_CONTEXT[$key] = $val;
    }

    /**
     * 批量设置上下文环境
     * @param array $arr
     */
    public static function loadArray($arr = [])
    {
        global $_MY_CONTEXT;
        if(!$_MY_CONTEXT instanceof SplContextArray){
            $_MY_CONTEXT = new SplContextArray();
        }
        $_MY_CONTEXT->loadArray($arr);
    }
}
