<?php

namespace App\Models;
use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class Languages extends Base
{
    const RedisKey = 'Languages';
    /**
     * 从redis中获取数据
     * 自己加风险意识代码，不想给那个SB领导写
     */
    public static function GetList()
    {
        $res = [];
        $lists = Redis::hgetall(self::RedisKey);
        if(!empty($lists)){
           foreach ($lists as $key => $value) {
                $value = json_decode($value,true);
                if($value['status'] == '1' ){
                    $res[] = [
                        'id' => $value['id'],
                        'name' => $value['name'],
                    ];
                }
           }
        }
        return $res;
    }
}
