<?php
/*
 * @desc       排行榜积分计算定时任务
 * @author     文明<736038880@qq.com>
 * @date       2022-09-14 10:16
 */
namespace App\Crontab;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;
use App\Task\WowLeaderBoardTask;
use Common\Common;

class WowLeaderBoardCrontab extends AbstractCronTask
{
    protected $logName = 'wow_leader_board_crontab';

    public static function getRule(): string
    {
        // 定义执行规则 根据Crontab来定义
        //分 时 日 月 周
        //0  0  *  *  1
        //每周1的00 : 01执行
        return '1 0 * * 1';
//        return '27 18 * * *';
    }

    public static function getTaskName(): string
    {
        // 定时任务的名称
        return 'WowLeaderBoardCrontab';
    }

    public function run(int $taskId, int $workerIndex)
    {
        Common::log('-----wow_leader_board_crontab crontab start-------', $this->logName);

        // 定时任务的执行逻辑
        $task = TaskManager::getInstance();
        $task->async(new WowLeaderBoardTask());

    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // 捕获run方法内所抛出的异常
        Common::log('-----wow_leader_board_crontab crontab error-------' . $throwable->getMessage(), $this->logName);
    }
}