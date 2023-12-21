<?php
namespace App\Work;
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-10-11 13:51
 */
Class Config{
    const ADMIN_NAME = '我就是小明';
    const IMAGE_DIR = '/data/www/image';
    const IMAGE_HOST = 'https://mingtongct.com/images';
    const ACCESS_TOKEN_KEY = 'access_token';
    //排行榜积分缓存名
    const REDIS_KEY_BOARD = 'leaderBoard';
    //排行榜详细信息缓存名
    const REDIS_KEY_BOARD_INFO = 'leaderBoardInfo';
    //坐骑列表缓存名
    const REDIS_KEY_MOUNT_LIST = 'mountMember';
    //用户缓存名
    const REDIS_KEY_USER = 'user';
    //周数偏移量
    const WEEK_OFFSET = 1;
    //回答有积分字数限制长度值
    const SCORE_DESCRIPTION_LENGTH = 15;
    //帮币兑换幸运币汇率
    const MONEY_CHANGE_LUCKY_RATE = 100;
    //针对wow前端组件做的周数容错
    public static $yearLinkWeek = [
        2021 => 1,
        2022 => 1,
        2023 => 1,
        2024 => 1,
        2025 => 1,
        2026 => 0,
    ];
    //奖励
    public static $award = [
        0 => '敬请期待',
        1 => '敬请期待',
        2 => '敬请期待',
    ];

    //类型对应的字段
    public static $typeColumnLink = [
        1 => 'adopt_num', //采纳
        2 => 'answer_num' //回答
    ];
    //采纳和回答对应分数
    public static $scoreLink = [
        1 => 3,
        2 => 1
    ];

    //获取天赋树技能列表的redis key
    public static function getTalentSkillTreeRedisKey($version, $oc){
        return "talent_tree_list:{$version}:{$oc}";
    }

    //获取职业技能列表的redis key
    public static function getOcSkillRedisKey($version, $oc){
        return "oc_skill_list:{$version}:{$oc}";
    }

    /**
     * @var string[] 帮助类型
     */
    public static $helpTypeLink = [
        1 => '插件研究',
        2 => '副本专区',
        3 => '任务/成就',
        4 => '人员招募',
        5 => '幻化讨论',
        6 => '宠物讨论',
        7 => '竞技场/战场',
        8 => '地精商会',
        9 => '新版本讨论'
    ];

    public static $pushModels = [
        //回答
        1 => 'VQUnsikNUM9pKaue4ufp4Ql8mvmKRsJCezbVtRLsEPA',
        //评论
        2 => 'UTVidyyWxD0xlnQv8xkrnu_Ar7s5OusnnyK9So_Vr1M',
        //回复
        3 => '1LaDLFdDr2zaBIXAYmJlQ4Imb3b89Owy6I8nHr-mRW4',
    ];

    public static $pushModelDataFormat = [
        //回答
        1 => [
            'thing4' => ['value' => ''],
            'name1' => ['value' => ''],
            'thing2' => ['value' => ''],
            'time3' => ['value' => ''],
        ],
    ];

    /**
     * @desc       获取推送模板格式
     * @author     文明<736038880@qq.com>
     * @date       2022-09-02 15:35
     * @param       $type
     * @param array $data
     *
     * @return \string[][]
     */
    public static function getModelFormat($type, array $data){
        $modelData = self::$pushModelDataFormat[$type];
        $num = 0;
        foreach ($modelData as &$val) {
            if(!isset($data[$num])){
                break;
            }
            $val['value'] = $data[$num];
            $num++;
        }
        return $modelData;
    }

    /**
     * @var array 敏感词干扰因子
     */
    public static $sensitiveDisturb = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9", "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
        "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
        "}", "<", ">", "~", "+", "=", ",", "."];
}