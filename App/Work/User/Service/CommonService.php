<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-01 11:52
 */
namespace User\Service;

/**
 * UserService不要去掉会报错
 */

use Common\Common;
use Common\CodeKey;
use User\Validator\UserValidate;
use App\Work\Config;
use User\Models\WowUserPushModel;
use User\Models\WowUserModelNew;
use App\Exceptions\CommonException;
use EasySwoole\EasySwoole\Config as EasyConfig;

class CommonService
{

    public function __construct($token = "")
    {
        $this->validate = new UserValidate();
    }

    /**
     * @desc       添加用户推送次数
     * @author     文明<736038880@qq.com>
     * @date       2022-09-01 12:15
     * @param array $params
     *
     * @return array
     */
    public function addPushNum(array $params)
    {
        $this->validate->checkAddPushNum();
        if (!$this->validate->validate($params)) {
            CommonException::msgException($this->validate->getError()->__toString());
        }

        $userId = Common::getUserId();
        $info = WowUserPushModel::query()->where('user_id', $userId)->where('model_id', $params['model_id'])->first();
        if(empty($info)){
            $insertData = [
                'user_id' => $userId,
                'model_id' => $params['model_id'],
                'push_num' => 1,
                'type' => $params['type']
            ];
            WowUserPushModel::query()->insert($insertData);
        }else{
            WowUserPushModel::query()->where('user_id', $userId)->where('model_id', $params['model_id'])->increment('push_num', 1);
        }

        return [];
    }

    /**
     * @desc       获取用户推送数
     * @author     文明<736038880@qq.com>
     * @date       2022-09-01 13:47
     * @param array $params
     *
     * @return \Hyperf\Utils\HigherOrderTapProxy|int|mixed|void
     */
    public function getPushNum(array $params)
    {
        $this->validate->checkGetPushNum();
        if (!$this->validate->validate($params)) {
            CommonException::msgException($this->validate->getError()->__toString());
        }
        if(empty(Config::$pushModels[$params['type']])){
            CommonException::msgException('推送类型错误');
        }
        $userId = Common::getUserId();
        $pushNum = WowUserPushModel::query()->where('user_id', $userId)->where('model_id', Config::$pushModels[$params['type']])->value('push_num');
        $pushNum = !empty($pushNum) ? $pushNum : 0;
        return $pushNum;
    }

    /**
     * @desc       获取推送模板id
     * @author     文明<736038880@qq.com>
     * @date       2022-09-01 14:38
     * @param array $params
     *
     * @return string
     */
    public function getModelId(array $params)
    {
        if(empty($params['type'])){
            CommonException::msgException('推送类型不能为空');
        }
        if(empty(Config::$pushModels[$params['type']])){
            CommonException::msgException('推送类型错误');
        }

        return Config::$pushModels[$params['type']];
    }

    /**
     * @desc       小程序推送
     * @author     文明<736038880@qq.com>
     * @date       2022-09-01 17:08
     * @param array $params
     */
    public function pushWxMessage(array $params){
        $this->validate->checkPushWxMessage();
        if (!$this->validate->validate($params)) {
            CommonException::msgException($this->validate->getError()->__toString());
        }

        if(empty(Config::$pushModels[$params['type']])){
            CommonException::msgException('推送类型不存在');
        }
        $pushNum = WowUserPushModel::query()->where('user_id', $params['user_id'])->where('model_id', Config::$pushModels[$params['type']])->value('push_num');
        if($pushNum <= 0){
            CommonException::msgException('推送数量不足');
        }

        $openId = WowUserModelNew::query()->where('user_id', $params['user_id'])->value('openId');
        if(empty($openId)){
            CommonException::msgException('open_id为空');
        }
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        $modelData = Config::getModelFormat($params['type'], $params['model_data']);
        $data = [
            'access_token' => $accessToken,
            'touser' => $openId,
            'template_id' => Config::$pushModels[$params['type']],
            'page' => 'pages/help-detail/index?id='. $params['help_id'],
            'data' => $modelData,
            'miniprogram_state' => 'developer',
        ];
        Common::log('requestData:'.json_encode($data, JSON_UNESCAPED_UNICODE), 'pushMessage');
        $return = httpClientCurl($url, json_encode($data));
        Common::log('responseData:'.json_encode($return, JSON_UNESCAPED_UNICODE), 'pushMessage');

        if($return['errcode'] === 0){
            //减掉用户推送数量
            try {
                WowUserPushModel::query()->where('user_id', Common::getUserId())->where('model_id', Config::$pushModels[$params['type']])->increment('push_num', -1);
            }catch(\Exception $e){
                if(strpos($e->getMessage(), 'Numeric value out of range') === false){
                    CommonException::msgException('sql错误');
                }
            }
        }
        if($return['errcode'] === 43101){
            //拒绝推送，将数量修改为0
            WowUserPushModel::query()->where('user_id', Common::getUserId())->where('model_id', Config::$pushModels[$params['type']])->update(['push_num' => 0]);
        }
    }

    /**
     * @desc       wx检测文本是否规格
     * @author     文明<736038880@qq.com>
     * @date       2022-09-15 10:22
     * @param string $text
     * @param string $openId
     * @param int    $scene 2评论 3论坛
     *
     * @return int
     */
    public function wxCheckText(string $text, string $openId, int $scene){

        $accessToken = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token='. $accessToken;
        $requestData = [
            'version' => 2,
            'openid' => $openId,
            'scene' => $scene,
            'content' => $text,
        ];
        Common::log('requestData:'.json_encode($requestData, JSON_UNESCAPED_UNICODE), 'wxCheckText');
        $result = httpClientCurl($url, json_encode($requestData));
        Common::log('responseData:'.json_encode($result, JSON_UNESCAPED_UNICODE), 'wxCheckText');
        if($result['errcode'] === 0){
            if($result['detail']['label'] == 100){
                //成功
                return 1;
            }else{
                //包含违规内容
                return 0;
            }
        }
        //请求失败
        return -1;
    }

    /**
     * @desc       微信违规图片校验
     * @author     文明<736038880@qq.com>
     * @date       2022-09-15 15:06
     * @param string $imageUrl
     * @param string $openId
     *
     * @return array
     */
    public function wxCheckImage(string $imageUrl, string $openId){
        $accessToken = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/media_check_async?access_token='. $accessToken;
        $requestData = [
            'version' => 2,
            'openid' => $openId,
            'media_type' => 2,
            'scene' => 3,
            'media_url' => $imageUrl,
        ];
        Common::log('requestData:'.json_encode($requestData, JSON_UNESCAPED_UNICODE), 'wxCheckImage');
        $result = httpClientCurl($url, json_encode($requestData));
        Common::log('responseData:'.json_encode($result, JSON_UNESCAPED_UNICODE), 'wxCheckImage');
        return [];
    }

    /**
     * @desc       获取access_token
     * @author     文明<736038880@qq.com>
     * @date       2022-09-01 10:10
     * @return mixed|string
     */
    public function getAccessToken(){
        $accessToken = redis()->get(Config::ACCESS_TOKEN_KEY);
        if(!empty($accessToken)){
            return $accessToken;
        }
        $appId = EasyConfig::getInstance()->getConf('app.APP_KEY');
        $secret = EasyConfig::getInstance()->getConf('app.APP_SECRET');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$secret}";
        $return = httpClientCurl($url);
        $accessToken = '';
        if(!empty($return['access_token'])){
            $accessToken = $return['access_token'];
            redis()->set(Config::ACCESS_TOKEN_KEY, $return['access_token'], 7100);
        }
        return $accessToken;
    }
}