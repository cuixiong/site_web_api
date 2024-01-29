<?php

namespace App\Models;
use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class DictionaryValue extends Base
{
    // 获取某一个code字典的全部选项
    public static function GetDicOptions($code)
    {
        $list = Redis::hGetAll('dictionary_'.$code);
        $result = [];
        if($list){
            foreach ($list as $map) {
                $map = json_decode($map,true);
                if($map['status'] == 1){
                    $result[] = ['value' => $map['value'],'label' => $map['name']];
                }
            }
        }
        return $result;
    }
}
