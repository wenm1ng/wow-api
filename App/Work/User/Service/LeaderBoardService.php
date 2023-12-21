<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-09-10 16:48
 */
namespace User\Service;

/**
 * UserService不要去掉会报错
 */

use Common\Common;
use User\Validator\UserValidate;
use User\Models\LeaderBoardModel;
use User\Models\WowUserModelNew;
use App\Exceptions\CommonException;
use App\Work\Config;
use App\Utility\Database\Db;

class LeaderBoardService
{

    public function __construct($token = "")
    {
        $this->validate = new UserValidate();
    }

    public function getList(array $params){
        $this->validate->checkBoardGetList();
        if (!$this->validate->validate($params)) {
            CommonException::msgException($this->validate->getError()->__toString());
        }

        if($params['year'] != date('Y') || $params['week'] != $params['nowWeek']){
            //非本周，从redis获取
            $redisKey = Config::REDIS_KEY_BOARD . '_' . $params['year'] . '_' . $params['week'];
            $redisInfoKey = Config::REDIS_KEY_BOARD_INFO . '_' . $params['year'] . '_' . $params['week'];

            $list = redis()->ZREVRANGE($redisKey, 0, -1, ['WITHSCORES']);
            $newList = [];
            if(!empty($list)){
                $userIds = array_keys($list);
                $scoreInfoList = redis()->hMGet($redisInfoKey, $userIds);
                foreach ($list as $userId => $score) {
                    if(empty($scoreInfoList[$userId])){
                        continue;
                    }
                    $newList[] = json_decode($scoreInfoList[$userId], true);
                }
            }
            return $newList;
        }

        //是本周，从库里面获取
        $fields = 'user_id,week,year,score,adopt_num,answer_num,description';
        $list = LeaderBoardModel::query()->where('year', $params['year'])->where('week', $params['week'])->orderBy('score', 'desc')->orderBy('adopt_num', 'desc')->select(Db::raw($fields))->get()->toArray();
        $userIds = array_column($list, 'user_id');
        $userList = [];
        if(!empty($userIds)){
            $userList = WowUserModelNew::query()->whereIn('user_id', $userIds)->select(Db::raw('user_id,avatarUrl,nickName'))->get()->toArray();
            $userList = array_column($userList, null, 'user_id');
        }

        foreach ($list as $key => &$val) {
            $val['description'] = Config::$award[$key] ?? null;
            $val['avatarUrl'] = $userList[$val['user_id']]['avatarUrl'] ?? '';
            $val['nickName'] = $userList[$val['user_id']]['nickName'] ?? '';
        }

        return $list;
    }

    /**
     * @desc       同步用户、排行榜缓存
     * @author     文明<736038880@qq.com>
     * @date       2022-09-12 10:44
     * @return array
     */
    public function aKeySyncRedis(){
        //用户信息缓存
        $userList = WowUserModelNew::query()->select(Db::raw('user_id,nickName,avatarUrl'))->get()->toArray();
        $newUserList = $newUserList2 = [];
        foreach ($userList as $val) {
            $newUserList[$val['user_id']] = json_encode($val);
            $newUserList2[$val['user_id']] = $val;
        }
        redis()->hMSet(Config::REDIS_KEY_USER, $newUserList);

        //排行榜缓存
        $fields = 'user_id,week,year,score,adopt_num,answer_num';
        $list = LeaderBoardModel::query()->orderBy('score', 'desc')->orderBy('adopt_num', 'desc')->select(Db::raw($fields))->get()->toArray();
        foreach ($list as $val) {
            redis()->zadd(Config::REDIS_KEY_BOARD . '_'.$val['year'].'_'.$val['week'], $val['score'], $val['user_id']);
            $val['avatarUrl'] = $newUserList2[$val['user_id']]['avatarUrl'];
            $val['nickName'] = $newUserList2[$val['user_id']]['nickName'];
            redis()->hSet(Config::REDIS_KEY_BOARD_INFO . '_'.$val['year'].'_'.$val['week'], $val['user_id'], json_encode($val));
        }


        return [];
    }
}