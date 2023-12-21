<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-14 16:16
 */
namespace App\Task\Models;

use App\Common\EasyModel;

class WowLeaderBoardAbnormalModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_leader_board_abnormal';

    protected $primaryKey = 'id';

    protected $keyType = 'int';
}