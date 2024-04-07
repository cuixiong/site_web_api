<?php

namespace App\Models;

use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class DictionaryValue extends Base
{
    // 获取某一个code字典的全部选项
    public static function GetDicOptions($code)
    {
        $list = Redis::hGetAll('dictionary_' . $code);
        $result = [];
        if ($list) {
            foreach ($list as $map) {
                $map = json_decode($map, true);
                if ($map['status'] == 1) {
                    $result[] = ['value' => $map['value'], 'label' => $map['name'], 'sort' => $map['sort'] ?? 0];
                }
            }
            self::sortBySort($result, 'sort');
        }
        return $result;
    }

    /**
     * 对二维数组按照指定键的值进行排序（使用usort）
     *
     * @param array $arr 待排序的二维数组
     * @param string $key 指定的键名
     * @param int $order 可选，排序顺序，1表示升序，-1表示降序
     * @return void 直接修改原数组
     */
    public static function sortBySort(&$arr, $key, $order = SORT_ASC)
    {
        usort($arr, function ($a, $b) use ($key, $order) {
            if (!isset($a[$key]) || !isset($b[$key])) {
                return false;
            }
            $result = strcmp($a[$key], $b[$key]);
            return ($order == SORT_ASC) ? $result : -$result;
        });
    }
}
