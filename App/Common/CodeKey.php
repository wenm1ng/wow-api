<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-20 23:53
 */
namespace Common;

class CodeKey
{
    //授权错误
    const SESSION_FAIL = 101; //授权时获取session信息失败
    //TODO: 全局
    const SUCCESS             = 200; //成功
    const FAIL                = 400; //失败
    const FAIL_403            = 403; //非法访问
    const SERVER_ERROR        = 500; //缺少token
    const INVALID_TOKEN       = 501; //无效的token
    const EXPIRED_TOKEN       = 50002; //token已过期
    const WORDS_SENSITIVE     = 50003; //含有敏感词
    const SIGN_ERROR          = 20001; //签名错误
    const HEADER_ERROR        = 800; //请求头错误
    const SIGN_FAIL           = 995; //签名错误
    const SYSTEM_UNLOGIN      = 996; //请登录
    const NOT_FOUND           = 997; //未找到信息
    const SYSTEM_ERROR        = 998; //系统错误
    const PARAMS_ERROR        = 1000; //参数错误
    const NOT_LEAF            = 1001; //目录非法删除
    const IDENTICAL_RECORD    = 1002; //存在相同记录
    const LOGIN_FILE          = 1003; //登入失败
    const ENCRYPT_ERROR       = 1004; //请求错误，稍后重试
    const VERIFY_CODE_FILE    = 1006; //验证码失败
    const REQUIRE_EXPIRED     = 1008; // 请求过期
    const ILLEGAL_DELETE      = 1009; // 非法删除
    const INVALID_ID          = 1010; // 无效的id
    const SECRET_NOT_MATCH    = 1011; // secret不匹配
    const FILE_UPLOAD_FAIL    = 1012; // 文件上传失败

    const WXPAY_ERROR     = 60001; //含有敏感词
    const COIN_NOT_ENOUGH = 40001; //幸运币不足


    const SUCCESSMSG = 'Success';

    //返回状态值名称
    const STATE = 'state';

    //返回数据名称
    const DATA = 'data';

    //返回消息名称
    const MSG = 'msg';
    public static function result(){
        return [
            self::STATE => self::FAIL,
            self::DATA => [],
            self::MSG => self::SUCCESSMSG,
        ];
    }
    // 产品号码池状态码

}