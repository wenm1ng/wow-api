<?php
/**
 * Guzzle 6文档：https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html
 */
namespace App\Utility;

use App\Utility\Logger\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class GuzzleHttpRequest
{
    /**
     * 请求超时时间
     * @var float
     */
    protected $timeout = 0.0;

    /**
     * 响应超时时间
     * @var float
     */
    protected $connect_timeout = 0.0;

    /**
     * 是否开启https验证
     * @var bool
     */
    protected $verify = false;

    /**
     * 设置http状态码非200也要返回，true时组件自动抛出
     * @var bool
     */
    protected $httpError = false;

    /**
     * http状态码非200时是否抛出
     * @var bool
     */
    protected $openErr = true;

    /**
     * 基础域名
     * @var string
     */
    protected $baseUri = '';

    /**
     * 请求数据格式
     * @var string
     */
    protected $dataType = '';

    /**
     * 请求方法
     * @var string
     */
    protected $method = 'GET';

    /**
     * 请求路由
     * @var string
     */
    protected $url = '';

    /**
     * 请求参数
     * @var string
     */
    protected $queryString = '';

    protected $proxy = '';

    /**
     * 请求头
     * @var array
     */
    protected $headers = [];

    protected $option = [];

    /**
     * 返回结果
     * @var
     */
    protected $response;

    protected $logErr = false;

    protected $logFile = 'GuzzleHttpRequest';

    /**
     * 默认设置
     * @return array
     */
    protected function defaultOption()
    {
        $config['verify'] = $this->verify;
        $config['http_errors'] = $this->httpError;
        if ($this->timeout > 0){
            $config['timeout'] = $this->timeout;
        }
        if ($this->connect_timeout > 0){
            $config['connect_timeout'] = $this->connect_timeout;
        }
        return $config;
    }

    /**
     * 设置请求方式
     * @param string $method
     * @return $this
     */
    protected function setMethod(string $method = 'GET')
    {
        $this->method = strtoupper($method);
        return $this;
    }

    protected function getMethod()
    {
        return $this->method;
    }

    /**
     * 设置请求路径;第二个数组的key值会替换路由中大括号对应的值
     * @param string $url
     * @param array $urlData
     * @return $this
     */
    protected function setUrl(string $url, array $urlData = [])
    {
        if (!empty($urlData)){
            foreach ($urlData as $key => $val){
                $url = str_replace('{'.$key.'}', $val, $url);
            }
        }
        $this->url = $url;
        return $this;
    }

    /**
     * 设置URL参数
     * @param array $query
     * @return $this
     */
    protected function setQueryString(array $query)
    {
        $this->queryString = http_build_query($query);
        return $this;
    }

    /**
     * 获取完整的请求URL
     * @return string
     */
    protected function getRequestUrl(): string
    {
        return $this->baseUri . $this->getUrl();
    }

    /**
     * 拼接url和query_string
     * @return string
     */
    protected function getUrl(): string
    {
        return $this->url . ($this->queryString ? '?'. $this->queryString : '');
    }

    /**
     * 获取请求参数
     * @param $data
     * @return mixed
     */
    protected function getRequestOption($data = [])
    {
        $option['headers'] = $this->getHeaders();

        if ($this->proxy){
            $option['proxy']  = $this->proxy;
        }

        if ($this->dataType){
            $option[$this->dataType] = $data;
        }
        $this->option = $option;
        return $this->option;
    }

    /**
     * 获取请求头
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * 设置请求参数：json body等
     * @param string $dataType
     * @return $this
     */
    public function setDataType(string $dataType = 'json')
    {
        $this->dataType = $dataType;
        return $this;
    }

    /**
     * GET 请求
     * @param string $url
     * @param array $data
     * @param bool $parseResult
     * @return array|string
     * @throws \Exception
     */
    public function getRequest(string $url = '', array $data = [], bool $parseResult = true)
    {
        return $this->baseRequest($url, 'GET', $data, $parseResult);
    }

    /**
     * POST 请求
     * @param $data
     * @param string $url
     * @param bool $parseResult
     * @return array|string
     * @throws \Exception
     */
    public function postRequest($data, string $url = '', bool $parseResult = true)
    {
        return $this->baseRequest($url, 'POST', $data, $parseResult);
    }

    /**
     * @param $data
     * @param string $url
     * @param bool $parseResult
     * @return array|string
     * @throws \Exception
     */
    public function putRequest($data, string $url = '', bool $parseResult = true)
    {
        return $this->baseRequest($url, 'PUT', $data, $parseResult);
    }


    /**
     * guzzleHttp封装
     * @param string $url
     * @param string $method
     * @param array $data
     * @param bool $parseResult
     * @return array|string
     * @throws \Exception
     */
    public function baseRequest(string $url = '', string $method = '', $data = [], bool $parseResult = true)
    {
        if ($method) $this->setMethod(strtoupper($method));//设置请求方式
        if ($url) $this->setUrl($url);//设置路由

        $httpClient = new Client($this->defaultOption());
        print_r($this->getRequestUrl());
        $method  = $this->getMethod();
        $fullUrl = $this->getRequestUrl();
        $option  = $this->getRequestOption($data);
        $log = ['method' => $method, 'url' => $fullUrl, 'option' => $option];
        try {
            $response   = $httpClient->request($method, $fullUrl, $option);
            $statusCode = $response->getStatusCode();
            $resp = $response->getBody()->getContents();
            print_r($resp);
            if ($statusCode != 200 && $this->openErr) {
                $errMsg = $response->getReasonPhrase();
                throw new \Exception($errMsg, $statusCode);
            }
            if ($parseResult) {
                $resp = $this->parseResponseDataFormat($resp);
            }
            $this->response = $resp;
        } catch (\Throwable $e) {
            $errCode = $e->getCode();
            $errMsg = $e->getMessage();
            $log['errCode'] = $errCode;
            $log['errMsg'] = $errMsg;
            Logger::error(json_encode($log, JSON_UNESCAPED_UNICODE), $this->logFile);
            throw new \Exception($errMsg, $errCode);
        }
        if ($this->logErr) Logger::info(json_encode(array_merge($log, ['response' => $resp]), JSON_UNESCAPED_UNICODE), $this->logFile);
        return $resp;
    }


    /**
     * 请求结果格式化
     * @param $resp
     * @return array|mixed
     */
    protected function parseResponseDataFormat($resp)
    {
        $resp = $resp ? json_decode($resp, true) : [];
        return is_array($resp) ? $resp : [];
    }

    /**
     * 批量请求处理
     * @param array $optionList
     * @param bool $parseResult
     * @return array
     * @throws \Exception
     */
    public function mergeRequest(array $optionList,$is_multiple = false, bool $parseResult = true)
    {
//        $optionList = [
//            '111' => [
//                'method'  => 'GET',
//                'url'     => '123123',
//                'option'  => [
//                    'headers' => [],
//                    'json' => [],
//                    'proxy'   => [],
//                ],
//            ]
//        ];
        $result = [];
        try{
            $config = $this->defaultOption();
            $config['base_uri'] = $this->baseUri;
            $client = new Client($config);

            $requestArr = [];
            if (!empty($optionList)){
                foreach ($optionList as $key =>  $item){
                    $itemUrl    = !empty($item['url']) ? $item['url'] : '';
                    $itemMethod = !empty($item['method']) ? $item['method'] : '';

                    if (!$itemUrl || !$itemMethod) {
                        continue;
                    }
                    $itemOption = !empty($item['option']) && is_array($item['option']) ? $item['option'] : [];
                    $itemOption['headers'] = !empty($itemOption['headers']) ? $itemOption['headers'] : [];

                    $function   = strtolower($itemMethod). 'Async';
                    $requestArr[$key] = $client->$function($itemUrl, $itemOption);
                    unset($key, $item);
                }
            }
            $responseList = Promise\Utils::unwrap($requestArr);

            if ($responseList){
                foreach ($responseList as $resKey => $resVal){
                    $rVal = $resVal->getBody()->getContents();
                    if ($parseResult){
                        $rVal = $this->parseResponseDataFormat($rVal);
                    }
                    if($is_multiple){
                        $result[$resKey][] = $rVal ? $rVal : [];
                    }else{
                        $result[$resKey] = $rVal ? $rVal : [];
                    }
                }
            }
            return $result;
        }catch (\Throwable $e){
            Logger::error('batch request error: '. $e->getMessage(). "\n" . json_encode($optionList, JSON_UNESCAPED_UNICODE));
            throw new \Exception($e->getMessage());
        }
    }
}