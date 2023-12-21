<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-10-06 15:05
 */
namespace App\Work\Common;

class MacroConfig{
    /**
     * @var array 组合技能枚举
     */
    public static $groupEnum = [
        'mouse_enemy' => '/cast [@mouseover,harm]',
        'mouse_team' => '/cast [@mouseover,help]',
        'focus' => '/cast [@focus,nodead]',
        'tar_enemy' => '/cast [@target,harm]',
        'tar_team' => '/cast [@target,help]',
        'shift' => '/cast [mod:shift]',
        'alt' => '/cast [mod:alt]',
        'ctrl' => '/cast [mod:ctrl]',
        'player' => '/cast [@player]',
        'def' => '/cast ',
    ];

    /**
     * @var \string[][] 释放技能 || 使用物品枚举
     */
    public static $useEnum = [
        [
            'name' => '当前目标',
            'code' => '@target'
        ],
        [
            'name' => '以焦点为目标',
            'code' => '@focus'
        ],
        [
            'name' => '以鼠标指向为目标',
            'code' => '@mouseover'
        ],
        [
            'name' => '在鼠标位置施放',
            'code' => '@cursor'
        ],
        [
            'name' => '以自己为目标',
            'code' => '@player'
        ],
        [
            'name' => '以目标的目标为目标',
            'code' => '@targettarget'
        ],
        [
            'name' => '以宠物为目标',
            'code' => '@pet'
        ],
//        [
//            'name' => '目标：友方',
//            'code' => 'help'
//        ],
//        [
//            'name' => '目标：敌方',
//            'code' => 'harm'
//        ],
//        [
//            'name' => '目标：存活',
//            'code' => 'nodead'
//        ],
//        [
//            'name' => '目标：死亡',
//            'code' => 'dead'
//        ],
//        [
//            'name' => '目标：存在',
//            'code' => 'exists'
//        ],
//        [
//            'name' => 'shift按键',
//            'code' => 'mod:shift'
//        ],
//        [
//            'name' => 'ctrl按键',
//            'code' => 'mod:ctrl'
//        ],
//        [
//            'name' => 'alt按键',
//            'code' => 'mod:alt'
//        ]
    ];

    public static $checkboxCamp = [
        [
            'name' => '不加此条件',
            'code' => ''
        ],
        [
            'name' => '目标：友方',
            'code' => 'help'
        ],
        [
            'name' => '目标：敌方',
            'code' => 'harm'
        ],
    ];

    public static $checkboxStatus = [
        [
            'name' => '不加此条件',
            'code' => ''
        ],
        [
            'name' => '目标：存活',
            'code' => 'nodead'
        ],
        [
            'name' => '目标：死亡',
            'code' => 'dead'
        ],
        [
            'name' => '目标：存在',
            'code' => 'exists'
        ]
    ];

    public static $checkboxPlayerStatus = [
        [
            'name' => '不加此条件',
            'code' => ''
        ],
        [
            'name' => '玩家：战斗中',
            'code' => 'combat'
        ],
        [
            'name' => '玩家：非战斗中',
            'code' => 'nocombat'
        ],
        [
            'name' => '玩家：潜行中',
            'code' => 'stealth'
        ],
        [
            'name' => '玩家：非潜行中',
            'code' => 'nostealth'
        ],
        [
            'name' => '玩家：房间内',
            'code' => 'indoors'
        ],
        [
            'name' => '玩家：房间外',
            'code' => 'outdoors'
        ],
    ];

    public static $checkboxButton = [
        [
            'name' => '不加此条件',
            'code' => ''
        ],
        [
            'name' => 'shift按键',
            'code' => 'mod:shift'
        ],
        [
            'name' => 'ctrl按键',
            'code' => 'mod:ctrl'
        ],
        [
            'name' => 'alt按键',
            'code' => 'mod:alt'
        ]
    ];

    /**
     * @var array 一些公共的配置
     */
    public static $checkboxCommon = [
        [
            'name' => '不加此条件',
            'code' => ''
        ],
        [
            'name' => '强制打断当前施法',
            'code' => '/stopcasting'
        ],
    ];

    /**
     * @var array 手动创建宏枚举
     */
    public static $handEnum = [
        [
            'name' => '释放技能',
            'code' => '/cast',
            'child' => 'useEnum',
        ],
        [
            'name' => '使用物品',
            'code' => '/use',
            'child' => 'useEnum',
        ],
        [
            'name' => '喊话',
            'code' => '',
            'child' => [
                [
                    'name' => '在当前频道用白字说',
                    'code' => '/S',
                    'child' => []
                ],
                [
                    'name' => '在当前频道用红字喊话',
                    'code' => '/Y',
                    'child' => []
                ],
                [
                    'name' => '在小队频道说',
                    'code' => '/P',
                    'child' => []
                ],
                [
                    'name' => '在团队频道说',
                    'code' => '/RA',
                    'child' => []
                ],
                [
                    'name' => '表情命令',
                    'code' => '/E',
                    'child' => []
                ]
            ]
        ],
        [
            'name' => '宠物相关',
            'code' => '',
            'child' => [
                [
                    'name' => '宠物释放技能',
                    'code' => '/cast',
                    'child' => 'useEnum'
                ],
                [
                    'name' => '宠物开始攻击',
                    'code' => '/petattack',
                    'child' => []
                ],
                [
                    'name' => '宠物跟随模式',
                    'code' => '/petfollow',
                    'child' => []
                ],
                [
                    'name' => '宠物被动模式',
                    'code' => '/petpassive',
                    'child' => []
                ],
                [
                    'name' => '宠物防御模式',
                    'code' => '/petdefensive',
                    'child' => []
                ],
                [
                    'name' => '宠物待在某地',
                    'code' => '/petstay',
                    'child' => []
                ]
            ]
        ],
        [
            'name' => '焦点相关',
            'code' => '',
            'child' => [
                [
                    'name' => '设置焦点',
                    'code' => '/focus',
                    'child' => 'useEnum',
                ],
                [
                    'name' => '清除焦点',
                    'code' => '/clearfocus',
                    'child' => []
                ]
            ]
        ]
    ];

    /**
     * @desc       获取手动创建宏菜单列表
     * @author     文明<736038880@qq.com>
     * @date       2022-10-07 14:42
     * @return array
     */
    public static function getHandList(){
        $handEnum = self::$handEnum;
        $selectListSecond = $selectListThird = [];
        $selectListFirst = array_column($handEnum, 'name');
        foreach ($handEnum as $key => &$val) {
            if(is_array($val['child'])){
                //多级菜单
                foreach ($val['child'] as $k => &$v) {
                    $selectListSecond[$key][] = $v['name'];
                    if(!empty($v['child'])){
                        $temp = $v['child'];
                        $v['child'] = self::$$temp;
                        $selectListThird[$key][$k] = array_column($v['child'], 'name');
                    }
                }
            }else{
                //二级菜单
                $temp = $val['child'];
                $val['child'] = self::$$temp;
                $selectListSecond[$key] = array_column($val['child'], 'name');
            }
        }
        $selectList = [$selectListFirst, $selectListSecond, $selectListThird];

        return ['list' => $handEnum, 'select_list' => $selectList, 'checkbox_status_list' => array_column(self::$checkboxStatus, 'name'), 'checkbox_button_list' => array_column(self::$checkboxButton,'name'), 'checkbox_camp_list' => array_column(self::$checkboxCamp, 'name'), 'checkbox_common_list' => array_column(self::$checkboxCommon, 'name'), 'checkbox_player_list' => array_column(self::$checkboxPlayerStatus, 'name')];
    }
}