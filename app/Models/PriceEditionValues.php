<?php

namespace App\Models;
use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class PriceEditionValues extends Base
{
    const RedisKey = '';// 要与后台程序的KEY一致
    public static function GetList($languageId)
    {
        try {
            $priceEditions = Redis::hget(self::RedisKey);
            if(empty($priceEditions)){
                // 如果Redis为空则查询Mysql，（温馨提示：当前的MYSQL是站点的哦，接受的小伙伴要新建一个总控的MYSQL链接并且查询出来）
                $priceEditions = PriceEditionValues::select(['id', 'name as edition', 'rules as rule', 'notice'])->where(['language_id' => $languageId])->get()->toArray();
                $result = $priceEditions && is_array($priceEditions) ? $priceEditions : [];
                return $result;
            } else {
                $result = [];
                $priceEditions = json_decode($priceEditions,true);
                if(!empty($priceEditions) && is_array($priceEditions)){
                    foreach ($priceEditions as $key => $value) {
                        // 如果语言相同则返回，语言不同，不需要返回
                        if($value['language_id'] == $languageId){
                            $value['edition'] = $value['name'];
                            $value['edition'] = $value['name'];
                            $res = [
                                'id' => $value['id'],
                                'edition' => $value['name'],
                                'rule' => $value['rules'],
                                'notice' => $value['notice'],
                            ];
                            $result[] = $res;
                        }
                    }
                }
                return $result;
            }
        } catch (\Exception $e) {
            // 出现错误则进行储存错误，并且通过MYSQL进行查询
            file_put_contents(storage_path('log').'/prices_editon/'.date('Y_m_d',time()).'.log',FILE_APPEND);
            // 如果Redis为空则查询Mysql，（温馨提示：当前的MYSQL是站点的哦，接受的小伙伴要新建一个总控的MYSQL链接并且查询出来）
            $priceEditions = PriceEditionValues::select(['id', 'name as edition', 'rules as rule', 'notice'])->where(['language_id' => $languageId])->get()->toArray();
            $result = $priceEditions && is_array($priceEditions) ? $priceEditions : [];
            return $result;
        }
    }
}
