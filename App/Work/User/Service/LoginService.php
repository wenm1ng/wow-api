<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-07-20 23:50
 */

namespace User\Service;

use Common\Common;
use Common\CodeKey;
use EasySwoole\Jwt\Jwt;
use User\Models\WowUserModelNew;
use EasySwoole\EasySwoole\Config;
use App\Exceptions\CommonException;

class LoginService
{
    protected $userModel;
    protected $secretKey = 'wow_wenming'; //token加密秘钥
    protected $expirationTime = 3600 * 24 * 2; //token过期时间 暂定2天
    protected $keySign = 'yixiaoUserId'; //用户拼接标签

    public function __construct()
    {
    }

    /**
     * @desc       　根据user_id设置token
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     *
     * @param $params
     *
     * @return array
     */
    public function setToken($params)
    {
        if (empty($params['user_id'])) {
            throw new \Exception('用户信息不能为空');
        }

        $userInfo = WowUserModelNew::query()->where('user_id', $params['user_id'])->first();
        if (empty($userInfo)) {
            throw new \Exception('用户不存在');
        }
        $userInfo = $userInfo->toArray();

        $jwtObject = Jwt::getInstance()
            ->setSecretKey($this->secretKey)// 秘钥
            ->publish();

        $jwtObject->setAlg('HMACSHA256'); // 加密方式
        $jwtObject->setAud($params['user_id']); // 用户
        $jwtObject->setExp(time() + $this->expirationTime); // 过期时间
        $jwtObject->setIat(time()); // 发布时间
        $jwtObject->setIss('easyswoole'); // 发行人
        $jwtObject->setJti(md5($this->keySign . $params['user_id'])); // jwt id 用于标识该jwt
        $jwtObject->setNbf(time() + 5); // 在此之前不可用
        $jwtObject->setSub('主题'); // 主题

        // 自定义数据
        $jwtObject->setData([
            'user_name' => $userInfo['nickName'],
            'avatar_url' => $userInfo['avatarUrl'],
            'openId' => $userInfo['openId']
        ]);

        // 最终生成的token
        $token = $jwtObject->__toString();

        return ['Authorization' => $token];
    }

    /**
     * @desc       　验证token获取user_id
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     *
     * @param string $token
     *
     * @return int|mixed
     */
    public function checkToken(string $token)
    {
        if (empty($token)) {
            throw new \Exception('非法访问, 缺少Authorization.');
        }
        $userId = 0;
        try {
            $jwtObject = Jwt::getInstance()->setSecretKey($this->secretKey)->decode($token);
            $status = $jwtObject->getStatus();
//            $jwt = Jwt::getInstance();
//            // 如果encode设置了秘钥,decode 的时候要指定
//             $status = $jwt->setSecretKey($this->secretKey)->decode($token);
            switch ($status) {
                case  1:
                    $userInfo['user_id'] = $jwtObject->getAud();
//                    $jwtObject->getAlg();
//                    $jwtObject->getAud();
                    $data = $jwtObject->getData();
                    if(empty($data['openId'])){
                        throw new \Exception('Authorization过期，请重新登录', CodeKey::EXPIRED_TOKEN);
                    }
                    $userInfo = array_merge($userInfo, $data);
//                    $jwtObject->getAlg();
//                    $jwtObject->getAud();
//                    $jwtObject->getData();
//                    $jwtObject->getExp();
//                    $jwtObject->getIat();
//                    $jwtObject->getIss();
//                    $jwtObject->getNbf();
//                    $jwtObject->getJti();
//                    $jwtObject->getSub();
//                    $jwtObject->getSignature();
//                    $jwtObject->getProperty('alg');
                    break;
                case  -1:
                    throw new \Exception('Authorization无效', CodeKey::INVALID_TOKEN);
                    break;
                case  -2:
                    throw new \Exception('Authorization过期，请重新登录', CodeKey::EXPIRED_TOKEN);
                    break;
            }
        } catch (\EasySwoole\Jwt\Exception $e) {

            throw new \Exception($e->getMessage(), CodeKey::INVALID_TOKEN);
        }
        return $userInfo;
    }

    /**
     * @desc       校验签名
     * @author     文明<736038880@qq.com>
     * @date       2022-09-06 15:58
     * @param string $sign
     */
    public function checkSign(string $sign, string $time){
        if (empty($sign)) {
            CommonException::msgException('签名有误', CodeKey::SIGN_ERROR);
        }

        $systemSign = Config::getInstance()->getConf('app.SYSTEM_SIGN');

        if(md5($systemSign. '_'. $time) !== $sign){
            CommonException::msgException('签名有误', CodeKey::SIGN_ERROR);
        }
    }
}