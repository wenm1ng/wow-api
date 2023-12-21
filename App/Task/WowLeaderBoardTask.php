<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-14 10:32
 */

namespace App\Task;

use Common\Common;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Task\Models\WowLeaderBoardCrontabModel;
use App\Task\Models\WowLeaderBoardAbnormalModel;
use App\Work\HelpCenter\Models\WowHelpAnswerModel;
use App\Work\HelpCenter\Models\WowHelpCenterModel;
use App\Work\Config;
use App\Utility\Database\Db;
use User\Models\WowUserModelNew;

class WowLeaderBoardTask implements TaskInterface
{
    protected $data;
    protected $logName = 'wow_leader_board_task';

    public function __construct()
    {
    }

    public function run(int $taskId, int $workerIndex)
    {
        Common::log('-----wow_leader_board_task task start-------', $this->logName);

        $weekEndDay = date('y-m-d H:i:s', mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y')));
        $weekStartDay = date('y-m-d H:i:s', mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y')));
//        $beginDay = $weekBeforeDay. '00:00:00';
//        $endDay = $yesterday.' 23:59:59';
        $textNum = Config::SCORE_DESCRIPTION_LENGTH ?? 15;

        $timeData = getWowWeekYear($weekStartDay);
        $year = $timeData['year'];
        $week = $timeData['week'];
        //删除旧数据，以后可能有错误数据的情况，需要多次执行此任务脚本
        WowLeaderBoardCrontabModel::query()->where('year', $year)->where('week', $week)->delete();
        WowLeaderBoardAbnormalModel::query()->where('year', $year)->where('week', $week)->delete();

        //主要逻辑，获取所有本周的回答和采纳情况，并写入db和redis
        //规格判定：回答同一用户的问题，>= 3 次，则判定为刷榜，不予积分，并记录
        $list = WowHelpAnswerModel::query()
            ->whereBetween('create_at', [$weekStartDay, $weekEndDay])
            ->where('status', 1)
            ->with([
                'help_info'=>function($query){
                    $query->select('user_id','id');
                }
            ])
            ->select(Db::raw('is_adopt_answer,description_num,user_id,help_id'))
            ->get()->toArray();
//
//        $ = array_unique(array_column($list, 'help_id'));
//        WowHelpCenterModel::query()->whereIn('id',)


        $list = Common::arrayGroup($list, 'user_id');
        $link = [];
        $insertData = $abnormalData = [];
//        dump($list);
        foreach ($list as $userId => $answerList) {
            $score = $adoptNum = $answerNum = $descriptionNum = 0;
            foreach ($answerList as $val) {
                $answerNum++;
                if(!empty($link[$userId.'-'.$val['help_info']['user_id']]['answer_num'])){
                    $link[$userId.'-'.$val['help_info']['user_id']]['answer_num']++;
                }else{
                    $link[$userId.'-'.$val['help_info']['user_id']]['answer_num'] = 1;
                }
                if($val['is_adopt_answer']){
                    //被采纳
                    $score += 3;
                    $adoptNum++;
                    if(!empty($link[$userId.'-'.$val['help_info']['user_id']]['adopt_num'])){
                        $link[$userId.'-'.$val['help_info']['user_id']]['adopt_num']++;
                    }else{
                        $link[$userId.'-'.$val['help_info']['user_id']]['adopt_num'] = 1;
                    }
                }
                if($val['description_num'] < $textNum){
                    continue;
                }
                $descriptionNum++;
                $score++;
            }
            $insertData[] = [
                'week' => $week,
                'year' => $year,
                'score' => $score,
                'adopt_num' => $adoptNum,
                'answer_num' => $answerNum,
                'description_num' => $descriptionNum,
                'user_id' => $userId
            ];
        }

        $insertData = array_chunk($insertData, 100);
        foreach ($insertData as $data) {
            WowLeaderBoardCrontabModel::query()->insert($data);
        }

//        arrayKeySort($insertData, 'score');

        foreach ($link as $userKey => &$val) {
            $userIds = explode('-', $userKey);
            $val['user_id'] = $userIds[0];
            $val['to_help_user_id'] = $userIds[1];
            $val['week'] = $week;
            $val['year'] = $year;
        }

        $abnormalData = array_values($link);
        $abnormalData = array_chunk($abnormalData, 100);
        foreach ($abnormalData as $data) {
            WowLeaderBoardAbnormalModel::query()->insert($data);
        }

        //写入缓存
        $where = [
            'where' => [
                ['year', '=', $year],
                ['week', '=', $week],
            ],
            'order' => ['score' => 'desc', 'answer_num' => 'desc', 'description_num' => 'desc']
        ];
        $list = WowLeaderBoardCrontabModel::baseQuery($where)->get()->toArray();
        $userList = WowUserModelNew::query()->select(Db::raw('user_id,nickName,avatarUrl'))->get()->toArray();
        $userList = array_column($userList, null, 'user_id');
        $redis = redis();
        $redisKey = Config::REDIS_KEY_BOARD . '_' . $year . '_' . $week;
        $redisInfoKey = Config::REDIS_KEY_BOARD_INFO . '_' . $year . '_' . $week;

        $scoreData = array_column($list, 'user_id', 'score');
        $hashData = [];
        foreach ($list as $key => $value) {
            if(in_array($key, [0,1,2])){
                //前三名，将奖励写入描述
                $value['description'] = Config::$award[$key];
                WowLeaderBoardCrontabModel::query()->where('id', $value['id'])->update(['description' => $value['description']]);
            }
            $value['avatarUrl'] = $userList[$val['user_id']]['avatarUrl'] ?? '';
            $value['nickName'] = $userList[$val['user_id']]['nickName'] ?? '';
            $hashData[$value['user_id']] = json_encode($value);
        }

        //先删除redis
        $redis->del($redisKey);
        $redis->del($redisInfoKey);
        if(!empty($scoreData)){
            $scoreKey = $scoreVal = '';
            foreach ($scoreData as $key => $value) {
                $scoreKey = $key;
                $scoreVal = $value;
                unset($scoreData[$key]);
                break;
            }
            if(!empty($scoreData)){
                $redis->zAdd($redisKey, $scoreKey, $scoreVal, ...$scoreData);
            }else{
                $redis->zAdd($redisKey, $scoreKey, $scoreVal);
            }
        }
        if(!empty($hashData)){
            $redis->hMSet($redisInfoKey, $hashData);
        }

        Common::log('-----wow_leader_board_task task finished-------', $this->logName);

        return true;
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        Common::log('----- task error 异常------- ' . $throwable->getMessage(), $this->logName);
    }
}
