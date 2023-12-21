<?php

namespace App\Utility;

use EasySwoole\EasySwoole\Core;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Http\Exception\Exception;
use EasySwoole\Http\Message\Status;

/**
 * @desc     api基类控制器,用于拦截注解异常,以及api异常,给用户返回一个json格式错误信息
 * @author   Huangbin <huangbin2018@qq.com>
 * @date     2020/3/21 16:37
 * @package  App\Utility
 */
abstract class AclBase extends BaseController
{
    /**
     * 登录用户信息
     * ['user_id' => 1, 'user_code' => 'admin', 'user_name' => '管理员']
     * @var null | array
     */
    protected $userInfo = null;
    
    public function index()
    {
        $this->actionNotFound('index');
    }

    protected function actionNotFound(?string $action)
    {
        $this->writeJson($action . ' not found', null, Status::CODE_NOT_FOUND);
    }

    public function onRequest(?string $action): ?bool
    {
        if (!parent::onRequest($action)) {
            return false;
        };
        try {
            // todo... 根据需要自行修改
            /*
            // 鉴权
            $token = $this->request()->getHeader('authorization');
            if (empty($token)) {
                $token = $this->request()->getRequestParam('token');
            }

            if (empty($token)) {
                throw new \Exception('token 不能为空', Code::NOT_LOGIN);
            }

            if (isset($token[0])) {
                $token = $token[0];
            }
            
            $userInfo = (new JwtService())->check($token);
            if (!is_array($userInfo)) {
                throw new \Exception('授权失败：' . ($userInfo ?: ''), Code::NOT_LOGIN);
            }
            
            // 设置用户信息
            $this->setUser($userInfo);
            */
            
        } catch (\Exception $e) {
            $this->writeJson($e->getMessage(), null, $e->getCode() ?: 0);
            return false;
        }
        
        return true;
    }

    protected function onException(\Throwable $throwable): void
    {
        if ($throwable instanceof Exception) {
            $msg = $throwable->getMessage();
            $this->writeJson($msg, null, 200);
        } else {
            if (Core::getInstance()->isDev()) {
                echo $throwable->__toString() . PHP_EOL;
                $this->writeJson($throwable->getMessage(), null, 500);
            } else {
                Trigger::getInstance()->throwable($throwable);
                $this->writeJson('系统内部错误，请稍后重试', null, 500);
            }
        }
    }
    
    public function getUserId()
    {
        return $this->userInfo['user_id'] ?: 0;
    }
    
    public function getUser()
    {
        return $this->userInfo ?: [];
    }
    
    public function setUser($userInfo = [])
    {
        $this->userInfo = $userInfo ?: [];
        GlobalHookHttp::getInstance()->hookCustom('user_info', $userInfo);
    }
}
