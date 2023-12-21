<?php

namespace App\Utility;

use EasySwoole\Component\Singleton;
use EasySwoole\Spl\SplContextArray;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class GlobalHookHttp
{
    use Singleton;

    /**
     *
     * @var array 
     */
    private $onRequest = [];

    /**
     *
     * @var array 
     */
    private $afterRequest = [];

    public function addOnRequest(callable $call)
    {
        $this->onRequest[] = $call;
    }

    public function addAfterRequest(callable $call)
    {
        $this->afterRequest[] = $call;
    }

    public function onRequest(Request $request,Response $response)
    {
        foreach ($this->onRequest as $call){
            call_user_func($call,$request,$response);
        }
    }

    public function afterRequest(Request $request,Response $response)
    {
        foreach ($this->afterRequest as $call){
            call_user_func($call,$request,$response);
        }
    }

    public function hookDefault()
    {
        global $_GET;
        if(!$_GET instanceof SplContextArray){
            $_GET = new SplContextArray();
        }
        global $_COOKIE;
        if(!$_COOKIE instanceof SplContextArray){
            $_COOKIE = new SplContextArray();
        }
        global $_POST;
        if(!$_POST instanceof SplContextArray){
            $_POST = new SplContextArray();
        }
        
        global $_COMPANY_INFO;
        if(!$_COMPANY_INFO instanceof SplContextArray){
            $_COMPANY_INFO = new SplContextArray();
        }
        $this->addOnRequest(function (Request $request){
            global $_GET;
            $_GET->loadArray($request->getQueryParams());
            global $_COOKIE;
            $_COOKIE->loadArray($request->getCookieParams());
            global $_POST;
            $_POST->loadArray($request->getParsedBody());
            global $_COMPANY_INFO;
            $companyCode = $request->getAttribute('_companycode_');
            $isSaaS = $request->getAttribute('_isSaaS_');
            $ip = $request->getAttribute('_ip_');
            $customData = [
                'code' => $companyCode,
                'is_saas' => $isSaaS,
                'ip' => $ip,
            ];
            $_COMPANY_INFO->loadArray($customData);
        });
        return $this;
    }

    public function hookCustom($key = '', $value = null)
    {
        global $_HOOK_CUSTOM;
        if(!$_HOOK_CUSTOM instanceof SplContextArray){
            $_HOOK_CUSTOM = new SplContextArray();
        }
        $customData = $_HOOK_CUSTOM->toArray();
        $customData[$key] = $value;
        $_HOOK_CUSTOM->loadArray($customData);
        return $this;
    }
}
