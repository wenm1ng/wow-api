<?php

namespace App\Utility;

use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Core;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Http\AbstractInterface\Controller;

/**
 * @desc     全局基本控制器
 * @author   Huangbin <huangbin2018@qq.com>
 * @date     2020/3/23 9:06
 * @package  App\Utility
 */
class BaseController extends Controller
{

    /**
     *
     * @var int 
     */
    protected $page = 1;

    /**
     *
     * @var int 
     */
    protected $pageSize = 20;
    
    public function index()
    {
        $this->actionNotFound('index');
    }


    /**
     * 获取用户的真实IP
     * @param string $headerName 代理服务器传递的标头名称
     * @return string
     */
    protected function clientRealIP($headerName = 'x-real-ip')
    {
        $server = ServerManager::getInstance()->getSwooleServer();
        $client = $server->getClientInfo($this->request()->getSwooleRequest()->fd);
        $clientAddress = $client['remote_ip'];
        $xri = $this->request()->getHeader($headerName);
        $xff = $this->request()->getHeader('x-forwarded-for');
        if ($clientAddress === '127.0.0.1') {
            if (!empty($xri)) {  // 如果有xri 则判定为前端有NGINX等代理
                $clientAddress = $xri[0];
            } elseif (!empty($xff)) {  // 如果不存在xri 则继续判断xff
                $list = explode(',', $xff[0]);
                if (isset($list[0])) $clientAddress = $list[0];
            }
        }
        return $clientAddress;
    }

    protected function input($name, $default = null) {
        $value = $this->request()->getRequestParam($name);
        return $value ?? $default;
    }

    /**
     * @desc 统一封装返回接口
     * @param string $msg
     * @param array $data 列表：['list' => [], 'paginator' => ['current', 'pageSize', 'total']]
     * @param int $code
     * @param int $httpCode
     * @return bool
     */
    protected function writeJson($msg = 'Success', $data = [], $code = 200, $httpCode = 200)
    {
        if (!$this->response()->isEndResponse()) {
            $result = [
                'code' => $code,
                'message' => $msg,
                'data' => $data,
            ];
            $response = $this->response();
            $response->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $response->withHeader('Content-type', 'application/json;charset=utf-8');
            $response->withStatus($httpCode);
            $this->response()->end();
            return true;
        }

        return false;
    }

    /**
     * @desc 响应成功
     * @param null $data
     * @param string $msg
     * @return bool
     */
    protected function returnSuccess($data = null, $msg = '')
    {
        $code = \App\Utility\Code::OK;
        return $this->writeJson($msg, $data, $code);
    }

    /**
     * @desc 响应失败
     * @param string $msg
     * @param int $code
     * @param null $data
     * @param int $httpCode
     * @return bool
     */
    protected function returnFail($msg = '', $code = null, $data = null, $httpCode = 200)
    {
        $code = $code === null ? \App\Utility\Code::FAIL : $code;
        return $this->writeJson($msg, $data ?: null, $code, $httpCode);
    }

    /**
     * @desc 未捕获的异常处理 (当控制器逻辑抛出异常时将调用该方法进行处理异常)
     * @param \Throwable $throwable
     */
    protected function onException(\Throwable $throwable): void
    {
        if ($throwable instanceof \Exception) {
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

    /**
     * @desc 当控制器方法执行结束之后将调用该方法,可自定义数据回收等逻辑
     * @param string|null $actionName
     */
    public function afterAction(?string $actionName) :void
    {
    }
}
