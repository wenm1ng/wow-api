<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2022-09-10 17:15
 */
namespace User\Models;

use App\Common\EasyModel;
use App\Work\Config;
use App\Utility\Database\Db;

class LeaderBoardModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_leader_board';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public static function incrementScore($userId, int $type, string $dateTime, int $num = 1, int $descriptionNum = 0){
         //如果描述字数大于0，说明是回答，需要>=15才予积分
         if($descriptionNum > 0 && $descriptionNum < Config::SCORE_DESCRIPTION_LENGTH){
             $descriptionNum = 0;
         }
         $timeData = getWowWeekYear($dateTime);
         $year = $timeData['year'];
         $week = $timeData['week'];
         $model = self::query();
         $id = $model
             ->where('year', $year)
             ->where('week', $week)
             ->where('user_id', $userId)
             ->value('id');

         $column = Config::$typeColumnLink[$type];
         $value = Config::$scoreLink[$type];
         $score = $value * $num;
        $descriptionNum = $descriptionNum * $num;
         if(empty($id) && $num >= 1){
             //没有记录，添加
             $insertData = [
                 'year' => $year,
                 'week' => $week,
                 'user_id' => $userId,
                 'score' => $score,
                 'description_num' => $descriptionNum
             ];
             $insertData[$column] = $value;
             $model->insert($insertData);
         }else{
             //increment
             $updateData = [
                 $column => Db::raw("{$column} + {$num}"),
                 'score' => Db::raw("score + {$score}"),
                 'description_num' => Db::raw('description_num + '.$descriptionNum)
             ];
             $model->where('id', $id)->update($updateData);
         }
    }
}