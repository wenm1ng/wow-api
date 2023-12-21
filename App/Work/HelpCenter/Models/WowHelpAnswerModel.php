<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-07-28 13:59
 */
namespace App\Work\HelpCenter\Models;

use App\Common\EasyModel;

class WowHelpAnswerModel extends EasyModel
{
    protected $connection = 'service';

    protected $table = 'wow_help_answer';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public function help_info(){
        return $this->belongsTo('App\Work\HelpCenter\Models\WowHelpCenterModel','help_id','id');
    }
}