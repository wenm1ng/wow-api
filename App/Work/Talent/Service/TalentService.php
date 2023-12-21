<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-09-04 11:18
 */
namespace Talent\Service;

use Common\Common;
use Talent\Models\WowTalentModel;
use Talent\Models\WowTalentTreeModel;
use Talent\Validator\TalentValidator;
use Talent\Models\WowUserTalentModel;
use Talent\Models\WowUserTalentCommentModel;
use User\Models\WowUserModel;
use App\Work\Config;

class TalentService
{

    protected $talentModel;
    protected $talentTreeModel;
    protected $userTalentModel;
    protected $userModel;
    protected $commentModel;
    protected $validator;

    public function __construct($token = "")
    {
        $this->talentModel = new WowTalentModel();
        $this->talentTreeModel = new WowTalentTreeModel();
        $this->userTalentModel = new WowUserTalentModel();
        $this->userModel = new WowUserModel();
        $this->commentModel = new WowUserTalentCommentModel();
        $this->validator = new TalentValidator();
    }

    /**
     * @desc       　天赋列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $version **版本号
     *
     * @return array|mixed
     */
    public function getTalentList(int $version, string $oc = null){
        if(empty($version)){
            throw new \Exception('版本信息不能为空');
        }

        $talentList = redis()->get('talent_list:'.$version);
        if(!empty($talentList)){
            $talentList = json_decode($talentList, true);
            if(!empty($oc)){
                $newTalentList = [];
                foreach ($talentList as $val) {
                    if($oc != $val['occupation']){
                        continue;
                    }
                    $newTalentList[] = $val;
                }
                $talentList = $newTalentList;
            }
            return $talentList;
        }
        $where = ['version' => $version];
        $talentList = $this->talentModel->field('occupation,talent_id,talent_name,icon,sort')->order(['sort' => 'ASC'])->all($where)->toRawArray();
        if(!empty($oc)){
            $newTalentList = [];
            foreach ($talentList as $val) {
                if($oc != $val['occupation']){
                    continue;
                }
                $newTalentList[] = $val;
            }
            $talentList = $newTalentList;
        }
        redis()->set('talent_list:'.$version, json_encode($talentList), 3600);
        return $talentList;
    }

    /**
     * @desc       　获取天赋技能树
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $version **版本号
     * @param $talentId **天赋id
     *
     * @return array
     */
    public function getTalentSkillTree($version, array $talentId, $oc):array{
        if(empty($version)){
            throw new \Exception('版本号不能为空');
        }
        if(empty($talentId)){
            throw new \Exception('天赋号不能为空');
        }
        if(empty($oc)){
            throw new \Exception('职业不能为空');
        }

        $redisKey = Config::getTalentSkillTreeRedisKey($version, $oc);
        $treeList = redis()->get($redisKey);
        if(!empty($treeList)){
            $treeList = json_decode($treeList, true);
            return $treeList;
        }

        $where = [
            'version' => $version,
            'talent_id' => [$talentId, 'in'],
            'occupation' => $oc
        ];
        $treeList = $this->talentTreeModel->all($where)->toRawArray();
        $treeList = Common::arrayGroup($treeList, 'talent_id');
        redis()->set($redisKey, json_encode($treeList), 3600);
        return $treeList;
    }

    /**
     * @desc       　保存用户天赋信息
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $params
     *
     * @return array
     */
    public function saveUserTalent($params){
        $this->validator->checkSaveUserTalent();
        if (!$this->validator->validate($params)) {
            throw new \Exception($this->validator->getError()->__toString());
        }
        //获取用户id
        $userInfo = $this->userModel->get(['openId' => $params['openId']])->field('user_id')->toRawArray();
        $dbData = [
            'user_id' => $userInfo['user_id'] ?? 0,
            'version' => $params['version'],
            'occupation' => $params['oc'],
            'title' => $params['title'],
            'statis' => $params['statis'],
            'points' => $params['points'],
            'actPoints' => $params['actPoints'],
            'type' => $params['type'],
            'description' => $params['description'] ?? '',
            'talent_skill_id_json' => json_encode($params['talent_ids']),
        ];

        if(!empty($params['id'])){
            //修改
            $this->userTalentModel->update($dbData, ['wut_id' => $params['id']]);
        }else{
            //新增
            $this->userTalentModel->create($dbData)->save();
        }
        return [];
    }

    /**
     * @desc       　获取天赋列表（大厅 or 自己）
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $params
     *
     * @return array
     */
    public function getTalentHallList($params, $isMyself = 0){
        $this->validator->checkgetTalentHallList();
        if (!$this->validator->validate($params)) {
            throw new \Exception($this->validator->getError()->__toString());
        }

        if($isMyself){
            $userId = Common::getUserId();
            if(empty($userId)){
                throw new \Exception('请登录');
            }
            $where['user_id'] = $userId;
        }

        $where = ['version' => $params['version'], 'occupation' => $params['oc']];

        if($params['order_type'] == 1){
            $where['order'] = 'favorCount desc';
        }elseif($params['order_type'] == 2){
            $where['order'] = 'update_at desc';
        }

        if(!empty($params['title'])){
            $where['title like'] = '%'. $params['title'] .'%';
        }

        $list = $this->userTalentModel->getList($where, $params);

        return $list;
    }

    /**
     * @desc       　获取用户天赋列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $params
     *
     * @return array
     */
    public function getUserTalentList($params){
        return $this->getTalentHallList($params, 1);
    }

    /**
     * @desc       　进行评论
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $params
     *
     * @return array
     */
    public function createComment($params){
        $this->validator->checkCreateComment();
        if (!$this->validator->validate($params)) {
            throw new \Exception($this->validator->getError()->__toString());
        }

        $createData = [
            'wut_id' => $params['wut_id'],
            'user_id' => $params['user_id'],
            'version' => $params['version'],
            'content' => $params['content'],
            'to_user_id' => $params['to_user_id'] ?? 0,
            'to_comment_id' => $params['to_comment_id'] ?? 0
        ];

        $this->commentModel->create($createData)->save();

        return [];
    }

    /**
     * @desc       　获取用户评论列表
     * @example    　
     * @author     　文明<wenming@ecgtool.com>
     * @param $params
     *
     * @return array
     */
    public function getTalentCommentList($params){
        $this->validator->checkGetTalentCommentList();
        if (!$this->validator->validate($params)) {
            throw new \Exception($this->validator->getError()->__toString());
        }

        if(!empty($params['order_type']) && $params['order_type'] == 2){
            $this->commentModel->order('like_count','desc')->order('comment_id','desc');
        }else{
            $this->commentModel->order('update_at','desc')->order('comment_id','desc');
        }
        $list = $this->commentModel->where(['wut_id' => $params['wut_id']])->all()->toRawArray();
        //取出用户id去取用户信息
        $userIds = array_unique(array_column($list, 'user_id'));

        $userList = $this->userModel->field('user_id,nickName,avatarUrl')->where(['user_id' => [$userIds, 'in']])->all()->toRawArray();
        $userList = array_column($userList, null ,'user_id');

        $link = $newList = [];
        foreach ($list as &$comment) {
            $comment['avatarUrl'] = $userList[$comment['user_id']]['avatarUrl'] ?? '';
            $comment['nickName'] = $userList[$comment['user_id']]['nickName'] ?? '';
            if(empty($comment['to_comment_id'])){
                $newList[] = $comment;
                continue;
            }
            $link[$comment['to_comment_id']][] = $comment;
        }

        foreach ($newList as &$comment) {
            $comment['child'] = $link[$comment['comment_id']] ?? [];
            krsort($comment['child']);
            $comment['child'] = array_values($comment['child']);
        }

        return $newList;
    }

    public function delComment(int $commentId){
        if(empty($commentId)){
            throw new \Exception('评论id不能为空');
        }
        $commentInfo = $this->commentModel->field('user_id')->get(['comment_id' => $commentId])->toRawArray();
        if(empty($commentInfo)){
            throw new \Exception('评论不存在');
        }
        $userId = Common::getUserId();
        if($userId != $commentInfo['user_id']){
            throw new \Exception('你只能删除自己的评论');
        }
        $this->commentModel->destroy(['comment_id' => $commentId]);
        return [];
    }
}