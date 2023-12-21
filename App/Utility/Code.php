<?php

namespace App\Utility;

class Code
{
    /**
     * 内部业务响应码
     */
    const IS_SUCCESS = 1; //刊登成
    const IS_FAIL = 0; //刊登失败
    const IS_CHECK = 2; //刊登可能卡住，需要检查
    const IS_GET = 3; //走的全球商品，需要二次确认获取结果（第一次失败，需要走第二次）
    /**
     * 系统级别错误码
     */
    const OK = 200;
    const FAIL = 0;
    const BAD_REQUEST = 400;
    const NOT_LOGIN = 401;
    const UNAUTHORIZED = 403;
    const NOT_FOUND = 404;
    const INTERNAL_ERR = 500;
    const PARAM_INVALID = 501;
    const GATEWAY_ERR = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;

    /**
     * 业务级别错误码 500 xx
     */
    const SIGN_INVALID = 50001;
    const TOKEN_TIMEOUT = 50002;

    /**
     * 用户错误码 100 xx
     */
    const USER_INVALID = 10001;

    /**
     * 数据库错误 200 xx
     */
    const SQL_ERROR = 20001;

    /**
     * 权限错误
     */
    const AUTH_ERROR = 606;


    /**
     * 平台授权错误 300 xx
     */
    const PLATFORM_AUTH_ERROR = 30001;
    const PLATFORM_CHECK_AUTH_ERROR = 30002;

    /**
     * 状态名称
     * @var string
     */
    const STATE_NAME ='state';
    /**
     * @var string
     */
    const MESSAGE_NAME ="message";
    /**
     * 警告
     * @var string
     */
    const  WARNING ='2';
    /**
     * 忽略
     * @var string
     */
    const  IGNORE ='3';

    /**
     *
     * @var array 
     */
    private static $errMessage = [
        self::OK => '服务器成功返回请求的数据。',
        self::FAIL => '操作失败。',
        self::BAD_REQUEST => '发出的请求有错误，服务器没有进行新建或修改数据的操作。',
        self::NOT_LOGIN => '用户没有权限（令牌、用户名、密码错误）。',
        self::UNAUTHORIZED => '用户得到授权，但是访问是被禁止的。',
        self::NOT_FOUND => '发出的请求针对的是不存在的记录，服务器没有进行操作。',
        self::INTERNAL_ERR => '服务器发生错误，请检查服务器。',
        self::PARAM_INVALID => '参数缺少或非法',
        self::GATEWAY_ERR => '网关错误。',
        self::SERVICE_UNAVAILABLE => '服务不可用，服务器暂时过载或维护。',
        self::GATEWAY_TIMEOUT => '网关超时。',
        
        // 业务...
        self::SIGN_INVALID => '签名无效',
        self::TOKEN_TIMEOUT => 'token 超时',
        self::USER_INVALID => '用户无效',

        //数据库..
        self::SQL_ERROR => 'SQL错误',

        //平台授权..
        self::PLATFORM_AUTH_ERROR => '平台授权异常',

    ];

    static function getReasonPhrase($statusCode): string
    {
        return isset(self::$errMessage[$statusCode]) ? self::$errMessage[$statusCode] : '';
    }
}
