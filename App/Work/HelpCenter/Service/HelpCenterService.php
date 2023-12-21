<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-28 13:54
 */
namespace App\Work\HelpCenter\Service;

use App\Work\Validator\HelpCenterValidator;
use App\Exceptions\CommonException;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use User\Service\UserService;
use User\Service\CommonService;
use App\Work\HelpCenter\Models\WowHelpAnswerModel;
use App\Work\HelpCenter\Models\WowHelpCenterModel;
use Common\Common;
use Wa\Models\WowUserLikesModel;
use App\Utility\Database\Db;
use App\Work\Config;
use App\Work\Common\File;
use Wa\Models\WowWaCommentModel;
use App\Work\WxPay\Models\WowUserWalletModel;
use User\Service\WalletService;
use User\Models\LeaderBoardModel;

class HelpCenterService
{
    protected $validator;
    public function __construct()
    {
        $this->validator = new HelpCenterValidator();
    }

    /**
     * @desc       获取帮助列表
     * @author     文明<736038880@qq.com>
     * @date       2022-07-28 14:39
     * @param array $params
     * @param array $isMyself 是否自己的数据
     *
     * @return array
     */
    public function getHelpList(array $params, int $isMyself = 0){
        $this->validator->checkPage();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        $where = [
            'where' => [
                ['status', '=', 1]
            ]
        ];
        if(!empty($params['version'])){
            $where['where'][] = ['version', '=', $params['version']];
        }
        if(!empty($params['help_type'])){
            $where['where'][] = ['help_type', '=', $params['help_type']];
        }
        if(isset($params['adopt_type']) && $params['adopt_type'] != -1){
            $where['where'][] = ['is_adopt', '=', $params['adopt_type']];
        }
        if(!empty($params['is_pay'])){
            $where['where'][] = ['is_pay', '=', $params['is_pay']];
        }
        if(empty($params['order'])){
            $where['order'] = ['modify_at' => 'desc', 'id' => 'desc'];
        }else{
            $where['order'] = [$params['order'] => 'desc', 'id' => 'desc'];
        }

        if($isMyself){
            $where['where'][] = ['user_id', '=', Common::getUserId()];
        }

        $fields = 'id,version,occupation,help_type,title,user_id,image_url,description,modify_at,favorites_num,help_num,read_num, 0 as has_favor, 0 as has_answer,is_adopt,is_pay,coin';
        $list = WowHelpCenterModel::getPageOrderList($where, $params['page'], $fields, $params['pageSize']);

        $list = (new UserService())->mergeUserInfo($list);
        $waIds = array_column($list, 'id');
        $list = $this->mergeCount($list, $waIds);
        $this->dealData($list);
        return ['list' => $list, 'page' => (int)$params['page']];
    }

    /**
     * @desc       获取回答列表
     * @author     文明<736038880@qq.com>
     * @date       2022-08-04 10:21
     * @param array $params
     *
     * @return array
     */
    public function getAnswerList(array $params, int $isMyself = 0){
        if(!$isMyself){
            $this->validator->checkId();
            if (!$this->validator->validate($params)) {
                CommonException::msgException($this->validator->getError()->__toString());
            }
        }else{
            $this->validator->checkPage();
            if (!$this->validator->validate($params)) {
                CommonException::msgException($this->validator->getError()->__toString());
            }
        }

        $where = [
            'order' => ['id' => 'asc'],
            'where' => [
                ['status', '=', 1]
            ],
        ];

        $fields = 'id,help_id,user_id,image_url,description,modify_at,favorites_num,comment_num,is_adopt_answer,wa_content';
        if($isMyself){
            $where['order'] = ['id' => 'desc'];
            $where['where'][] = ['user_id', '=', Common::getUserId()];
            $list = WowHelpAnswerModel::getPageOrderList($where, $params['page'], $fields, $params['pageSize']);
        }else{
            $where['where'][] = ['help_id', '=', $params['id']];
            $list = WowHelpAnswerModel::baseQuery($where)->select(DB::raw($fields))->get()->toArray();
        }
        $list = (new UserService())->mergeUserInfo($list);
        $waIds = array_column($list, 'id');
        $list = $this->mergeCount($list, $waIds, 4);
        $isAnswer = 0;
        $userId = Common::getUserId();
        foreach ($list as &$val) {
            $val['modify_at'] = getTimeFormat($val['modify_at']);
            $val['wa_content_show'] = !empty($val['wa_content']) ? substr($val['wa_content'], 0, 30).'...' : '';
            if(!empty($userId) && $val['user_id'] == $userId){
                $isAnswer = 1;
            }
        }

        return ['list' => $list, 'is_answer' => $isAnswer];
    }


    /**
     * @desc       处理返回数据
     * @author     文明<736038880@qq.com>
     * @date       2022-07-28 17:16
     * @param array $list
     */
    public function dealData(array &$list){
        $versionList = (new \Version\Service\VersionService())->getVersionList();
        $versionList = array_column($versionList, 'name', 'version');
        foreach ($list as $key => $val) {
            $list[$key]['modify_at'] = getTimeFormat($val['modify_at']);
            $list[$key]['flod'] = false;
            $list[$key]['version_name'] = $versionList[$val['version']] ?? '正式服';
            $list[$key]['help_type_name'] = Config::$helpTypeLink[$val['help_type']] ?? '插件研究';
        }
    }
    /**
     * @desc       合并是否包含当前登录用户
     * @author     文明<736038880@qq.com>
     * @date       2022-07-28 14:38
     * @param array $list
     * @param array $ids
     * @param int   $isInfo
     *
     * @return array
     */
    public function mergeCount(array $list, array $ids, int $type = 3){
        if(empty($ids)){
            dump(1);
            return $list;
        }
        $userId = Common::getUserId();
        if(empty($userId)){
            if($type !== 3){
                $commentCountLink = WowWaCommentModel::query()->whereIn('wa_id', $ids)->where('type', 2)->select(DB::raw('count(1) as total,wa_id'))->groupBy(['wa_id'])->pluck('total', 'wa_id')->toArray();
                foreach ($list as $key => $val) {
                    $list[$key]['comment_num'] = !empty($commentCountLink[$val['id']]) ? $commentCountLink[$val['id']] : 0;
                }
            }
            return $list;
        }
        $whereLikes = [
            'whereIn' => [
                ['link_id', $ids],
            ],
            'where' => [
                ['user_id', '=', $userId],
                ['type', '=', $type]
            ]
        ];
        $whereAnswer = [
            'whereIn' => [
                ['help_id', $ids]
            ],
            'where' => [
                ['user_id', '=', $userId]
            ]
        ];

        //获取点赞、评论高亮
        $likeLink = WowUserLikesModel::baseQuery($whereLikes)->pluck('id', 'link_id')->toArray();
        dump($likeLink);
        if($type === 3){
            $answerLink = WowHelpAnswerModel::baseQuery($whereAnswer)->pluck('id', 'help_id')->toArray();
        }else{
            $commentLink = WowWaCommentModel::query()->whereIn('wa_id', $ids)->where('user_id', $userId)->where('type', 2)->pluck('id', 'wa_id')->toArray();
            $commentCountLink = WowWaCommentModel::query()->whereIn('wa_id', $ids)->where('type', 2)->select(DB::raw('count(1) as total,wa_id'))->groupBy(['wa_id'])->pluck('total', 'wa_id')->toArray();
        }

        foreach ($list as $key => $val) {
            $list[$key]['has_favor'] = !empty($likeLink[$val['id']]) ? 1 : 0;
            $list[$key]['has_answer'] = !empty($answerLink[$val['id']]) ? 1 : 0;
            $list[$key]['has_comment'] = !empty($commentLink[$val['id']]) ? 1 : 0;
            $list[$key]['comment_num'] = !empty($commentCountLink[$val['id']]) ? $commentCountLink[$val['id']] : 0;
        }

        return $list;
    }

    /**
     * @desc       帮助详情
     * @author     文明<736038880@qq.com>
     * @date       2022-08-02 18:11
     * @param int $id
     *
     * @return mixed
     */
    public function getHelpInfo(int $id){
        $this->incrementHelpRead($id);
        $info = WowHelpCenterModel::query()->where('id', $id)->first();
        if(empty($info) || empty($id)){
            CommonException::msgException('该帮助不存在');
        }
        $list = [$info->toArray()];
        $list = (new UserService())->mergeUserInfo($list);
        $list = $this->mergeCount($list, [$id]);
        $this->dealData($list);

        return $list[0];
    }

    /**
     * @desc     帮助详情阅读量累加
     * @example
     * @param int $id
     */
    public function incrementHelpRead(int $id, int $num = 1){
        WowHelpCenterModel::query()->where('id', $id)->increment('read_num', $num);
    }

    public function incrementHelpFavor(int $id, int $num = 1)
    {
        WowHelpCenterModel::query()->where('id', $id)->increment('favorites_num', $num);
    }

    public function incrementAnswerFavor(int $id, int $num = 1)
    {
        WowHelpAnswerModel::query()->where('id', $id)->increment('favorites_num', $num);
    }

    /**
     * @desc       添加求助
     * @author     文明<736038880@qq.com>
     * @date       2022-07-29 14:52
     * @param array $params
     *
     * @return int
     */
    public function addHelp(array $params, \EasySwoole\Http\Request $request){
        $this->validator->checkAddHelp($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        $file = $request->getUploadedFile('file');
        $imageUrl = '';
        if (!empty($file) && $file->getSize()) {
            $fileName = $file->getClientFileName();
            $filend = pathinfo($fileName, PATHINFO_EXTENSION);
            $data = file_get_contents($file->getTempName());
            $fileName = saveFileDataImage($data, '/help', $filend);
            $imageUrl = getInterImageName($fileName);
        }
        try{
            DB::beginTransaction();
            $userId = Common::getUserId();
            $insertData = [
                'title' => $params['title'],
                'description' => $params['description'],
                'help_type' => $params['help_type'],
                'version' => $params['version'],
                'image_url' => $imageUrl,
                'user_id' => $userId,
                'status' => 1,
                'is_pay' => $params['is_pay'],
                'coin' => !empty($params['coin']) ? (int)$params['coin'] : 0
            ];
            $helpId = WowHelpCenterModel::query()->insertGetId($insertData);
            if($params['is_pay'] == 1){
                //如果是有偿求助，走付费流程
                (new WalletService())->operateMoney(-$params['coin'], (int)$userId, 2, $helpId);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Common::log($e->getMessage().'_'.$e->getFile().'_'.$e->getLine(), 'sqlTransaction');
            CommonException::msgException('系统错误');
        }

        return $helpId;
    }

    /**
     * @desc  添加回答
     * @example
     * @param array                    $params
     * @param \EasySwoole\Http\Request $request
     *
     * @return int
     */
    public function addAnswer(array $params, \EasySwoole\Http\Request $request){
        $this->validator->checkAddAnswer($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        $file = $request->getUploadedFile('file');
        $imageUrl = '';
        if (!empty($file) && $file->getSize()) {
            $fileName = $file->getClientFileName();
            $filend = pathinfo($fileName, PATHINFO_EXTENSION);
            $data = file_get_contents($file->getTempName());
            $fileName = saveFileDataImage($data, '/help', $filend);
            $imageUrl = getInterImageName($fileName);
        }
        $userInfo = Common::getUserInfo();
        $userId = $userInfo['user_id'];

        try{
            DB::beginTransaction();
            $insertData = [
                'help_id' => $params['help_id'],
                'description' => $params['description'],
                'description_num' => call_user_func(function()use($params){
                    return mb_strlen(str_replace(' ', '', $params['description']));
                }),
                'wa_content' => $params['wa_content'] ?? '',
                'image_url' => $imageUrl,
                'user_id' => $userId
            ];

            $id = WowHelpAnswerModel::query()->insertGetId($insertData);
            //添加回答数量
            $this->incrementHelpAnswerNum($params['help_id'], 1);
            //积分记录
            LeaderBoardModel::incrementScore($userId, 2, date('Y-m-d H:i:s'), 1, $insertData['description_num']);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            Common::log($e->getMessage().'_'.$e->getFile().'_'.$e->getLine(), 'sqlTransaction');
            CommonException::msgException('系统错误');
        }


        $info = WowHelpCenterModel::query()->where('id', $params['help_id'])->first();
        if(!empty($info)){
            $info = $info->toArray();
            //推送通知给用户
            $pushData = [
                'help_id' => $params['help_id'],
                'type' => 1,
                'user_id' => $info['user_id'],
                'model_data' => [mbSubStr($info['title'], 15), $userInfo['user_name'], mbSubStr($params['description'], 15), date('Y-m-d H:i:s')]
            ];
            try{
                (new CommonService())->pushWxMessage($pushData);
            }catch(\Exception $e){
                Common::log('errMsg:'.$userId.'--'.$e->getMessage(), 'pushMessage');
            }
        }

        return $id;
    }

    public function incrementHelpAnswerNum(int $id, int $num = 1){
        WowHelpCenterModel::query()->where('id', $id)->increment('help_num', $num);
    }


    /**
     * @desc       删除求助
     * @author     文明<736038880@qq.com>
     * @date       2022-07-29 14:52
     * @param array $params
     *
     * @return null
     */
    public function deleteHelp(array $params){
        $this->validator->checkId();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        $info = WowHelpAnswerModel::query()->where('help_id', $params['id'])->select(['id'])->first();
        if(!empty($info)){
            CommonException::msgException('已有帮助人进行回答，无法删除');
        }
        $info = WowHelpCenterModel::query()->where('id', $params['id'])->select(['image_url'])->first();
        if(empty($info)){
            CommonException::msgException('数据不存在');
        }
        $info = $info->toArray();
        WowHelpCenterModel::query()->where('id', $params['id'])->update(['status' => 0]);
        (new File())->delImage($info['image_url']);

        return null;
    }


    /**
     * @desc       修改求助回答
     * @author     文明<736038880@qq.com>
     * @date       2022-07-29 15:29
     * @param array $params
     *
     * @return null
     */
    public function updateAnswer(array $params){
        $this->validator->checkUpdateAnswer($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        $updateData = [
            'description' => $params['description'],
            'image_url' => !empty($params['image_url']) ? $params['image_url'] : '',
            'modify_at' => date('Y-m-d H:i:s')
        ];

        WowHelpAnswerModel::query()->where('id', $params['id'])->update($updateData);

        return null;
    }

    /**
     * @desc       提交回答（状态改为1）
     * @author     文明<736038880@qq.com>
     * @date       2022-07-29 15:34
     * @param array $params
     *
     * @return null
     */
    public function setAnswerStatus(array $params){
        $this->validator->checkId();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        $updateData = [
            'status' => 1,
            'modify_at' => date('Y-m-d H:i:s')
        ];

        WowHelpAnswerModel::query()->where('id', $params['id'])->update($updateData);

        return null;
    }

    /**
     * @desc       采纳答案
     * @author     文明<736038880@qq.com>
     * @date       2022-08-06 16:23
     * @param array $params
     *
     * @return null
     *
     */
    public function adoptAnswer(array $params){
        $this->validator->checkAdopt();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        $info = WowHelpAnswerModel::query()->where('id', $params['id'])->first();
        if(empty($info)){
            CommonException::msgException('该回答不存在');
        }
        $helpInfo = WowHelpCenterModel::query()->where('id', $params['help_id'])->first();
        if(empty($helpInfo)){
            CommonException::msgException('该提问不存在');
        }
        $info = $info->toArray();

        try{
            //设置回答为已采纳
            $updateData = [
                'is_adopt_answer' => 1,
                'modify_at' => date('Y-m-d H:i:s')
            ];

            WowHelpAnswerModel::query()->where('id', $params['id'])->update($updateData);
            //设置提问为已采纳提问
            $updateData = [
                'is_adopt' => 1,
                'modify_at' => date('Y-m-d H:i:s')
            ];
            WowHelpCenterModel::query()->where('id', $params['help_id'])->update($updateData);
            //操作记录相关日志
            (new WalletService())->operateMoney((float)$helpInfo['coin'], (int)$info['user_id'], 3, $params['help_id']);
            //积分记录
            LeaderBoardModel::incrementScore($info['user_id'], 1, date('Y-m-d H:i:s'));

            $userInfo = Common::getUserInfo();
            //推送通知给用户
            $pushData = [
                'help_id' => $params['help_id'],
                'type' => 1,
                'user_id' => $info['user_id'],
                'model_data' => [mbSubStr($helpInfo['title'], 15), $userInfo['user_name'], mbSubStr($helpInfo['description'], 15), date('Y-m-d H:i:s')]
            ];
            try{
                (new CommonService())->pushWxMessage($pushData);
            }catch(\Exception $e){
                Common::log('errMsg:'.$userInfo['user_id'].'--'.$e->getMessage(), 'pushMessage');
            }
        }catch (\Exception $e){
            CommonException::msgException('异常错误');
        }

        return null;
    }

    /**
     * @desc    获取回答详情
     * @example
     * @param int $id
     *
     * @return mixed
     */
    public function getAnswerInfo(int $id){
        $info = WowHelpAnswerModel::query()->where('id', $id)->first();
        if(empty($info) || empty($id)){
            CommonException::msgException('该回答不存在');
        }
        $list = [$info->toArray()];
        $list = (new UserService())->mergeUserInfo($list);
        $list = $this->mergeCount($list, [$id], 4);
        $list[0]['wa_content_show'] = !empty($info['wa_content']) ? substr($info['wa_content'], 0, 30).'...' : '';

        return $list[0];
    }

    /**
     * @desc    删除回答
     * @example
     * @param int $id
     *
     * @return array
     */
    public function delAnswer(array $params){
        $info = WowHelpAnswerModel::query()->where('id', $params['id'])->first(['user_id','is_adopt_answer','create_at','description_num']);
        if(empty($info)){
            CommonException::msgException('回答不存在');
        }
        $userId = Common::getUserId();
        if($info['user_id'] != $userId){
            CommonException::msgException('删除失败');
        }
        if(!empty($info['is_adopt_answer'])){
            CommonException::msgException('该回答已被采纳,无法删除');
        }
        WowHelpAnswerModel::query()->where('id', $params['id'])->delete();

        $this->incrementHelpAnswerNum($params['help_id'], -1);

        LeaderBoardModel::incrementScore($userId, 2, $info['create_at'], -1, $info['description_num']);
        return [];
    }

    /**
     * @desc       获取有偿帮忙数量
     * @author     文明<736038880@qq.com>
     * @date       2022-09-08 17:21
     * @return array
     */
    public function getPayHelpNum(){
        $count = WowHelpCenterModel::query()->where('is_pay', 1)->where('is_adopt', 0)->where('status', 1)->count();
        return ['count' => $count];
    }
}