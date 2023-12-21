<?php
/*
 * @desc       
 * @author     文明<736038880@qq.com>
 * @date       2022-09-20 16:02
 */
namespace App\Work\Common;

use EasySwoole\HttpClient\HttpClient;
use App\Work\Config;

class Lottery{
    /*
 * 不同概率的抽奖原理就是把0到*（比重总数）的区间分块
 * 分块的依据是物品占整个的比重，再根据随机数种子来产生0-* 中的某个数
 * 判断这个数是落在哪个区间上，区间对应的就是抽到的那个物品。
 * 随机数理论上是概率均等的，那么相应的区间所含数的多少就体现了抽奖物品概率的不同。
 */
    const DEFAULT_NUM = 5;
    protected static $defaultAward = [
        ['name' => '杂物', 'image_url' => ''],
        ['name' => '装备', 'image_url' => ''],
        ['name' => '材料', 'image_url' => ''],
        ['name' => '图纸', 'image_url' => ''],
        ['name' => '徽记', 'image_url' => ''],
    ];

    /**
     * 抽奖方法
     * @return [array] [抽奖情况]
     */
    public static function doDraw(string $awardName, $rate, string $imageUrl, int $mountId)
    {
        $rate = $rate * 100;
        $randNum = mt_rand(1, self::DEFAULT_NUM);
        // 奖品数组
        $proArr = [
            ['id' => 1, 'name' => $awardName, 'v' => $rate],
            ['id' => 2, 'name' => self::$defaultAward[$randNum-1]['name'], 'v' => 10000 - $rate],
        ];
        // 奖品等级奖品权重数组
        $arr = [];
        foreach ($proArr as $key => $val) {
            $arr[$val['id']] = $val['v'];
        }
        // 中奖 id
        $rid = self::get_rand($arr);
        if($rid == 2){
            $awardName = self::$defaultAward[$randNum-1]['name'];
            $imageUrl = self::$defaultAward[$randNum-1]['image_url'];
        }
        $return = [
            'id' => $mountId,
            'name' => $awardName,
            'image_url' => $imageUrl,
            'is_bingo' => $rid == 2 ? 0 : 1,
            'is_open' => 0,
            'title' => '点击开刷',
            'is_show_image' => 0
        ];
        return $return;

        /**模拟抽奖测试**/
        /*        $i = 0;
                while ( $i < 10000) {
                  $rid = $this->get_rand($arr);
                  $res[] = $rid;
                  $i++;
                }
                // 统计奖品出现次数
                $result = array_count_values($res);
                asort($result);
                foreach ($result as $id => $times) {
                    foreach ($proArr as $gifts) {
                        if($id == $gifts['id']){
                            $response[$gifts['name']] = $times;
                        }
                    }
                }
                dump($response);
                die;*/

    }

    /**
     * 抽奖算法
     * @param  array  $proArr 奖品等级奖品权重数组
     * @return [int]         中奖奖品等级
     */
    public static function get_rand($proArr = []) {
        $rid = 0;

        // 概率数组的总权重
        $proSum = array_sum($proArr);

        // 概率数组循环
        foreach ($proArr as $k => $proCur) {
            // 从 1 到概率总数中任意取值
            $randNum = mt_rand(1, $proSum);
            // 判断随机数是否在概率权重中
            if ($randNum <= $proCur) {
                // 取出奖品 id
                $rid = $k;
                break;
            } else {
                // 如果随机数不在概率权限中，则不断缩小总权重，直到从奖品数组中取出一个奖品
                $proSum -= $proCur;
            }
        }

        unset($proArr);
        return $rid;
    }
}