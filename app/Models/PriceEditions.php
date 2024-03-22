<?php

namespace App\Models;
use App\Models\Base;
use Illuminate\Support\Facades\Redis;
class PriceEditions extends Base
{
    const RedisKey = 'PriceEdition';// 要与后台程序的KEY一致
    /**
     * 从redis中获取数据
     * 自己加风险意识代码，不想给那个SB领导写
     */
    public static function GetList($id,$priceEditionsPid = null){
        $res = [];
        $lists = $priceEditionsPid ? $priceEditionsPid : Redis::hgetall(self::RedisKey);
        if(!empty($lists)){
           foreach ($lists as $key => $value) {
                $value = json_decode($value,true);
                if($value['status'] == '1' ){
                    $value['publisher_id'] = explode(',',$value['publisher_id']);
                    if(in_array($id,$value['publisher_id'])){
                        $res[] = $value['id'];
                    }
                }
           }
        }
        return $res;
    }
}
