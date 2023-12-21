<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-05-07 10:52
 */
namespace Wa\Service;

use Common\Common;
use Wa\Models\WowWaTabTitleModel;
use Wa\Models\WowWaTabModel;
use Version\Models\WowVersionModelNew;
use Wa\Models\WowWaImageModel;
use Wa\Models\WowWaImagePythonModel;
use Wa\Models\WowWaContentModel;
use Wa\Models\WowWaContentPythonModel;
use Wa\Models\WowWaContentHistoryModel;
use Wa\Models\WowUserLikesModel;
use Wa\Models\WowWaCommentModel;
use App\Exceptions\CommonException;
use App\Work\Validator\WaValidator;
use Occupation\Models\WowOccupationModelNew;
use Occupation\Service\OccupationService;
use User\Service\UserService;
use App\Utility\Database\Db;
use Talent\Models\WowTalentModelNew;
use App\Work\Common\File;

class WaService
{
    protected $validator;

    public function __construct()
    {
        $this->validator = new WaValidator();
    }

    /**
     * @desc       　获取tab列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $params
     *
     * @return array
     */
    public function getTabList(array $params){
        $this->validator->checkVerision();
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        $version = (int)$params['version'];
        $tabList = WowWaTabModel::getEnableList($version);
        $where = [
            'where' => [
                ['version', '=', $version],
                ['status', '=', 1]
            ]
        ];
        $ocList = (new OccupationService())->getOcListByVersion($version);
        $titleList = WowWaTabTitleModel::getList($where, 'id as tt_id,version,type,title,image_url,description');
        $newTitleList = [];
        foreach ($titleList as &$val) {
            $temp = explode('#', $val['description']);
            $val['description'] = [];
            $val['occupation'] = '';
            foreach ($temp as $description) {
                $val['description'][] = ['description' => $description];
            }
            $newTitleList[$val['type']][] = $val;
        }

        foreach ($tabList as &$tabVal) {
            if($tabVal['type'] == 1){
                $tabVal['child'] = $ocList;
            }else{
                $tabVal['child'] = $newTitleList[$tabVal['type']] ?? [];
            }
        }
        return $tabList;
    }

    /**
     * @desc       　获取wa列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $params
     *
     * @return array
     */
    public function getWaList(array $params){
        $this->validator->checkGetWaList($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        if(!empty($params['tt_id'])){
            $where = [
                'where' => [
                    ['tt_id', '=', $params['tt_id']]
                ],
            ];
            if(!empty($params['talent_name']) && $params['talent_name'] !== '全部'){
                $where['whereRaw'][] = "FIND_IN_SET('{$params['talent_name']}',`tips`)";
            }
        }elseif(!empty($params['oc'])){
            $where = [
                'where' => [
                    ['version', '=', $params['version']],
                    ['occupation', '=', $params['oc']],
                ],
            ];
            if(!empty($params['talent_name']) && $params['talent_name'] !== '全部'){
                $where['whereRaw'][] = "FIND_IN_SET('{$params['talent_name']}',`tips`)";
            }
        }elseif(!empty($params['id'])){
            $where = [
                'whereIn' => [
                    ['id', $params['id']]
                ],
            ];
        }elseif(!empty($params['search_value'])){
            $where = [
                'where' => [
                    ['title', 'like', "%{$params['search_value']}%"]
                ],
            ];
        }
        if(empty($params['order'])){
            $where['order'] = ['create_at' => 'desc', 'id' => 'desc'];
        }else{
            $where['order'] = [$params['order'] => 'desc', 'id' => 'desc'];
        }
        $where['where'][] = ['status', '=', 1];
        if(!empty($params['order'])){
            $where['order'] = [$params['order'] => 'desc'];
        }
        $list = WowWaContentModel::getPageOrderList($where, $params['page'], 'id,title,user_id,create_at,description,read_num,tips', $params['pageSize']);
        $list = (new UserService())->mergeUserName($list);
        $list = $this->mergeWaImage($list, 3);
        $waIds = array_column($list, 'id');
        $list = $this->mergeWaCount($list, $waIds);
        return ['list' => $list, 'page' => (int)$params['page']];
    }

    /**
     * @desc        将消息改为已读
     * @example
     * @param array $commentIds
     */
    public function updateNoReadNum(array $list){
        if(!Common::getUserId()){
            dump(1111);
            return '';
        }
        $commentIds = [];
        dump($list);
        foreach($list as $val){
            if(empty($val['is_read'])){
                $commentIds[] = $val['id'];
            }
        }
        if(empty($commentIds)){
            dump(22222);
            return '';
        }
        WowWaCommentModel::query()->whereIn('id', $commentIds)->update(['is_read' => 1]);
        return count($commentIds);
    }
    /**
     * @desc       　合并wa相关数量信息（点赞、评论等）
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $info
     * @param array $waId
     * @param int   $isInfo
     *
     * @return array
     */
    private function mergeWaCount(array $info, array $waId, int $isInfo = 0){
        if(empty($waId)){
            return $info;
        }
        $userId = Common::getUserId();
        $whereLikes = [
            'whereIn' => [
                ['link_id', $waId]
            ],
            'where' => [
                ['type', '=', 2]
            ]
        ];
        $whereComment = [
            'whereIn' => [
                ['wa_id', $waId]
            ],
            'where' => [
                ['status', '=', 1]
            ]
        ];
        $whereFavor = [
            'whereIn' => [
                ['link_id', $waId]
            ],
            'where' => [
                ['type', '=', 1]
            ]
        ];

        //获取点赞、评论高亮
        $likesLink = WowUserLikesModel::baseQuery($whereLikes)->select(Db::raw('count(1) as total,link_id'))->groupBy(['link_id'])->pluck('total','link_id')->toArray();
        $favorLink = WowUserLikesModel::baseQuery($whereFavor)->select(Db::raw('count(1) as total,link_id'))->groupBy(['link_id'])->pluck('total','link_id')->toArray();
        $commentLink = WowWaCommentModel::baseQuery($whereComment)->select(Db::raw('count(1) as total,wa_id'))->groupBy(['wa_id'])->pluck('total','wa_id')->toArray();

        if(!empty($userId)){
            $whereLikes['where'][] = $whereComment['where'][] = ['user_id', '=', $userId];
            $likesLinkUser = WowUserLikesModel::baseQuery($whereLikes)->select(Db::raw('count(1) as total,link_id'))->groupBy(['link_id'])->pluck('total','link_id')->toArray();
            $favorLinkUser = WowUserLikesModel::baseQuery($whereFavor)->select(Db::raw('count(1) as total,link_id'))->groupBy(['link_id'])->pluck('total','link_id')->toArray();
            $commentLinkUser = WowWaCommentModel::baseQuery($whereComment)->select(Db::raw('count(1) as total,wa_id'))->groupBy(['wa_id'])->pluck('total','wa_id')->toArray();
        }
        if(!$isInfo){
            foreach ($info as $key => $val) {
                $info[$key]['flod'] = false;
                $info[$key]['likes_count'] = $likesLink[$val['id']] ?? 0;
                $info[$key]['favor_count'] = $favorLink[$val['id']] ?? 0;
                $info[$key]['comment_count'] = $commentLink[$val['id']] ?? 0;
                $info[$key]['has_likes'] = !empty($likesLinkUser[$val['id']]) ? 1 : 0;
                $info[$key]['has_favor'] = !empty($favorLinkUser[$val['id']]) ? 1 : 0;
                $info[$key]['has_comment'] = !empty($commentLinkUser[$val['id']]) ? 1 : 0;
                $info[$key]['tips'] = explode(',', $info[$key]['tips']);
            }
        }else{
            $info['likes_count'] = $likesLink[$info['id']] ?? 0;
            $info['favor_count'] = $favorLink[$info['id']] ?? 0;
            $info['comment_count'] = $commentLink[$info['id']] ?? 0;
            $info['has_likes'] = !empty($likesLinkUser[$info['id']]) ? 1 : 0;
            $info['has_favor'] = !empty($favorLinkUser[$info['id']]) ? 1 : 0;
            $info['has_comment'] = !empty($commentLinkUser[$info['id']]) ? 1 : 0;
            $info['tips'] = explode(',', $info['tips']);
        }
        return $info;
    }
    /**
     * @desc       　获取wa详情
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param int $waId
     *
     * @return array|mixed
     */
    public function getWaInfo(int $waId){
        //浏览量+1
        WowWaContentModel::query()->where('id', $waId)->increment('read_num', 1);
        $list = WowWaContentModel::query()->where('id', $waId)->where('status', 1)->select(['id','title','description','update_description','wa_content','create_at','user_id','read_num','favorites_num','likes_num','talent_name as label','tips','origin_url'])->get()->toArray();
        if(empty($list)){
            CommonException::msgException('该wa不存在');
        }
        $list = $this->mergeWaImage($list);

        $userService = new UserService();
        $list = UserService::mergeUserNameAvatarUrl($list);
        $list = $this->mergeWaHistory($list);

        $info = $list[0] ?? [];
        $info = array_merge($info, $userService->getIsLikes((int)$info['id']));
        $info = $this->mergeWaCount($info, [$waId], 1);
        return $info;
    }

    /**
     * @desc       　获取wa标签
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $params
     *
     * @return array
     */
    public function getLabels(array $params){
        $this->validator->checkgetLabels($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }

        if(!empty($params['oc'])){
            $labels = WowTalentModelNew::getTalentByVersionOc($params['version'], $params['oc']);
        }elseif(!empty($params['tt_id'])){
            $info = WowWaTabTitleModel::query()->where('id', $params['tt_id'])->first();
            $labels = [];
            if(!empty($info)){
                $labels = explode('#', $info->toArray()['description']);
            }
        }

        return ['oc' => $params['oc'], 'labels' => $labels];
    }

    /**
     * @desc       　合并wa图片
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $list
     * @param array $num
     *
     * @return array
     */
    public function mergeWaImage(array $list, int $num = 0){
        $waIds = array_column($list, 'id');
        $imageLink = [];
        if(!empty($waIds)){
            $imageLink = WowWaImageModel::query()->whereIn('wa_id', $waIds)->select(Db::raw('image_url, wa_id'))->get()->toArray();
            $imageLink = Common::arrayGroup($imageLink, 'wa_id');
        }

        foreach ($list as &$val) {
            $val['images'] = $imageLink[$val['id']] ?? [];
            if(!empty($num)){
                $val['images'] = array_slice($val['images'],0, 3);
            }
        }
        return $list;
    }

    /**
     * @desc       　合并wa版本历史记录
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $list
     *
     * @return array
     */
    public function mergeWaHistory(array $list){
        $waIds = array_column($list, 'id');
        $historyLink = [];
        if(!empty($waIds)){
            $historyLink = WowWaContentHistoryModel::query()->whereIn('wa_id', $waIds)->get(['version_number', 'wa_content', 'wa_id', 'create_at'])->toArray();
            foreach ($historyLink as &$val) {
                $val['create_at'] = date('Y-m-d H:i', strtotime($val['create_at']));
            }
            $historyLink = Common::arrayGroup($historyLink, 'wa_id');
        }
        foreach ($list as &$val) {
            $val['history_version'] = $historyLink[$val['id']] ?? [];
        }
        return $list;
    }

    /**
     * @desc       　标记wa收藏
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param int $id
     * @param int $num
     */
    public function incrementWaFavorites(int $id, int $num){
        WowWaContentModel::query()->where('id', $id)->increment('favorites_num', $num);
    }

    /**
     * @desc       　标记wa喜欢
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param int $id
     * @param int $num
     */
    public function incrementWaLikes(int $id, int $num){
        WowWaContentModel::query()->where('id', $id)->increment('likes_num', $num);
    }

    /**
     * @desc       　获取wa评论列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $params
     *
     * @return array
     */
    public function getWaComment(array $params){
        $this->validator->checkWaId($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        //获取点赞、评论高亮
        $where = [
            'where' => [
                ['status', '=', 1]
            ],
            'order' => [
                'id' => 'desc'
            ]
//            'comment_id' => 0
        ];
        if(!empty($params['is_all'])){
            $where['where'][] = ['user_id', '=', Common::getUserId()];
        }else{
            $where['where'][] = ['wa_id', '=', $params['id']];
        }
        if(!empty($params['is_all'])){
            $where['whereIn'][] = ['type', [1,2]];
        }else{
            $type = 1;
            if(!empty($params['type'])){
                $type = $params['type'];
            }
            $where['where'][] = ['type', '=', $type];
        }


        $fields = 'id,user_id,comment_id,content,create_at,reply_user_id,wa_id,is_read,type';
//        $commentList = WowWaCommentModel::query()->where($where)->select(Db::raw($fields))->orderBy('create_at')->get()->toArray();
        $commentList = WowWaCommentModel::getPageOrderList($where, $params['page'], $fields, $params['pageSize']);
        $reduceReadNum = $this->updateNoReadNum($commentList);
//        $commentIds = array_column($commentList, 'id');
        $commentList = UserService::mergeUserNameAvatarUrl($commentList);
        return ['list' => $commentList, 'page' => (int)$params['page'], 'reduce_read_num' => $reduceReadNum];
//        $replyList = [];
//        if(!empty($commentIds)){
//            unset($where['comment_id']);
//            $replyList = WowWaCommentModel::query()->where($where)->whereIn('comment_id', $commentIds)->select(Db::raw($fields))->orderBy('create_at', 'asc')->get()->toArray();
//            $replyList = UserService::mergeUserNameAvatarUrl($replyList);
//            $replyList = Common::arrayGroup($replyList, 'comment_id');
//        }
//
//        //重新二维数组排序
//        $newCommentList = [];
//        foreach ($commentList as $comment) {
//            $newCommentList[] = $comment;
//            if(isset($replyList[$comment['id']])){
//                foreach ($replyList[$comment['id']] as $childComment) {
//                    $newCommentList[] = $childComment;
//                }
//            }
//        }
//
//        return $newCommentList;
    }

    /**
     * @desc       　进行评论
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param array $params
     *
     * @return int
     */
    public function toComment(array $params){
        $this->validator->checkComment($params);
        if (!$this->validator->validate($params)) {
            CommonException::msgException($this->validator->getError()->__toString());
        }
        $insertData = [
            'wa_id' => $params['wa_id'],
            'content' => $params['content'],
            'comment_id' => $params['comment_id'] ?? 0,
            'user_id' => Common::getUserId(),
            'reply_user_id' => !empty($params['reply_user_id']) ? $params['reply_user_id'] : 0,
            'is_read' => 1
        ];
        if(!empty($params['type'])){
            $insertData['type'] = $params['type'];
        }
        return WowWaCommentModel::query()->insertGetId($insertData);
    }

    /**
     * @desc       　删除评论
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param int $commentId
     *
     * @return null
     */
    public function delComment(int $commentId){
        if(empty($commentId)){
            CommonException::msgException('评论id不能为空');
        }
        WowWaCommentModel::query()->where('id', $commentId)->where('user_id', Common::getUserId())->delete();
        return null;
    }

    /**
     * @desc       获取用户收藏wa列表
     * @author     文明<736038880@qq.com>
     * @date       2022-07-11 14:36
     * @param $params
     *
     * @return array
     */
    public function getWaFavoritesList($params){
        $userId = Common::getUserId();
        $waIds = WowUserLikesModel::query()->where('type', 1)->where('user_id', $userId)->pluck('link_id')->toArray();
        if(empty($waIds)){
            return ['list' => [], 'page' => (int)$params['page']];
        }
        $params = array_merge($params, ['id' => $waIds]);
        return $this->getWaList($params);
    }

    /**
     * @desc       获取用户所有wa评论
     * @author     文明<736038880@qq.com>
     * @date       2022-07-11 16:10
     * @param array $params
     *
     * @return array
     */
    public function getCommentAll(array $params){
        $params = array_merge($params, ['is_all' => 1]);
        $list = $this->getWaComment($params);
        //获取被回复的评论
        $commentIds = array_unique(array_filter(array_column($list['list'], 'comment_id')));
        $commentLink = [];
        if(!empty($commentIds)){
            $commentLink = WowWaCommentModel::query()->whereIn('id', $commentIds)->pluck('content', 'id')->toArray();
        }
        foreach ($list['list'] as &$val) {
            $val['reply_content'] = !empty($commentLink[$val['comment_id']]) ? $commentLink[$val['comment_id']] : '';
        }

        return $list;
    }

    public function uploadFile(){
        $list = WowWaImageModel::query()->whereBetween('wa_id',[26,111])->get()->toArray();
        $file = new File();
        foreach ($list as $val) {
            $rs = $file->uploadImageToBlog($val['image_url']);
            dump($rs);
            if(empty($rs['status'])){
                continue;
            }
            WowWaImageModel::query()->where('id', $val['id'])->update(['image_url' => 'http://www.wenming.online/public/uploads/'.$rs['savepath']]);
            \Co::sleep(0.5);
        }
        return null;
    }

    /**
     * @desc       爬虫wa数据
     * @author     文明<736038880@qq.com>
     * @date       2022-07-26 16:21
     * @param array $params
     *
     * @return void|null
     */
    public function saveFiddlerWaData(array $params){
        $params = $params['response_data']['data'];
        return;
        $link = ['法师'=>'fs','战士'=>'zs','牧师'=>'ms','圣骑士'=>'qs','德鲁伊'=>'xd','术士'=>'ss','猎人'=>'lr','潜行者'=>'dz','萨满祭祀'=>'sm','武僧'=>'ws','死亡骑士'=>'dk','恶魔猎手'=>'dh'];
        $versionLink = ['2' => 1, '5' => 3, '1' => 2];
//        $info = WowWaContentModel::query()->where('origin_id', $params['last_version']['id'])->first();
//        if(!empty($info)){
//            return null;
//        }

        $tips = [];
        $oc = '';
        foreach ($params['tags'] as $val) {
            if(isset($link[$val['name']])){
                $oc = $link[$val['name']];
                continue;
            }
            $tips[] = $val['name'];
        }
        $tips = implode(',', $tips);

        $insertData = [
            'occupation' => $oc,
            'description' => $params['description'],
            'origin_description' => $params['description'],
            'wa_content' => $params['last_version']['string'],
            'origin_id' => $params['last_version']['id'],
            'tips' => $tips,
            'title' => $params['title'],
            'origin_title' => $params['title'],
            'version' => !empty($versionLink[$params['plugin_type']['id']]) ? $versionLink[$params['plugin_type']['id']] : 2,
            'origin_url' => $params['raw_address'],
            'type' => 3,
            'tt_id' => 9,
            'data_from' => 2,
        ];

        $waId = WowWaContentModel::query()->insertGetId($insertData);

        if(!empty($params['version_list'])){
            $historyData = [
                'version' => $insertData['version'],
                'version_number' => $params['version_list'][0]['version'],
                'wa_content' => $insertData['wa_content'],
                'wa_id' => $waId,
                'description' => $params['version_list'][0]['description'],
            ];
            WowWaContentHistoryModel::query()->insert($historyData);
        }
        $imageData = [];

        $file = new File();
        foreach ($params['images'] as $image) {
            $rs = $file->uploadImageToBlog($image);
            dump($rs);
            if(empty($rs['status'])){
                continue;
            }
            $imageData[] = [
                'wa_id' => $waId,
                'origin_image_url' => $image,
                'image_url' => 'http://www.wenming.online/public/uploads/'.$rs['savepath']
            ];
        }
        if(!empty($imageData)){
            WowWaImageModel::query()->insert($imageData);
        }
        dump('采集成功');
        return null;
    }

    /**
     * @desc   采集数据转移
     * @return null
     */
    public static function savePythonWa(){
//        WowWaImagePythonModel
        $pythonList = WowWaContentPythonModel::get()->toArray();
        $originIds = array_column($pythonList, 'origin_id');
        $originLink = WowWaContentModel::query()->whereIn('origin_id', $originIds)->pluck('origin_id', 'origin_id');
        $file = new File();
        foreach ($pythonList as $val) {
            if(isset($originLink[$val['origin_id']])){
                //已经转换跳过
                continue;
            }
            $imageList = WowWaImagePythonModel::where('wa_id', $val['id'])->get()->toArray();
            if(empty($imageList)){
                continue;
            }
            $isSuc = 0;
            $images = [];
            foreach ($imageList as $image) {
                $rs = $file->uploadImage(['url' => [$image['origin_image_url']]]);
                \Co::sleep(0.5);
                if(!isset($rs[$image['origin_image_url']])){
                    continue;
                }
                $isSuc = 1;
                WowWaImagePythonModel::where(['id' => $image['id']])->update(['image_url' => $rs[$image['origin_image_url']]]);
                $images[] = ['origin_image_url' => $image['origin_image_url'], 'image_url' => $rs[$image['origin_image_url']]];
            }

            //有图片才保存wa
            if(!$isSuc){
                continue;
            }
            $insertData = [
                'version' => $val['version'],
                'occupation' => $val['occupation'],
                'talent_name' => $val['talent_name'],
                'tips' => $val['tips'],
                'type' => $val['type'],
                'data_from' => $val['data_from'],
                'status' => 0,
                'tt_id' => $val['tt_id'],
                'title' => $val['origin_title'],
                'origin_title' => $val['origin_title'],
                'origin_id' => $val['origin_id'],
                'origin_url' => $val['origin_url'],
                'description' => $val['origin_description'],
                'origin_description' => $val['origin_description'],
                'wa_content' => $val['wa_content']
            ];
            $waId = WowWaContentModel::insertGetId($insertData);
            $imageData = [];
            foreach ($images as $image) {
                $imageData[] = [
                    'wa_id' => $waId,
                    'origin_image_url' => $image['origin_image_url'],
                    'image_url' => $image['image_url']
                ];
            }
            WowWaImageModel::insert($imageData);
            echo '新wa_id:'.$waId;
        }
        dump('数据转移成功');
        return null;
    }
}