<?php

namespace App\Models;
use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class PriceEditionValues extends Base
{
    const RedisKey = 'PriceEditionValue';// 要与后台程序的KEY一致
    public static function GetList($languageId,$publisherId,$priceEditionsValue = null,$priceEditionsPid = null)
    {
        try {
            $priceEditionIds = PriceEditions::GetList($publisherId,$priceEditionsPid);
            $priceEditions = $priceEditionsValue ? $priceEditionsValue : Redis::hgetall(self::RedisKey);
            if(empty($priceEditions)){
                return [];
            } else {
                $result = [];
                if(!empty($priceEditions) && is_array($priceEditions)){
                    foreach ($priceEditions as $key => $value) {
                        $value = json_decode($value,true);
                        if($value['status'] == '1' && in_array($value['edition_id'],$priceEditionIds) && $value['language_id'] == $languageId){
                                $res = $value;
                                $result[] = $res;
                        }
                    }
                }
                return $result;
            }
        } catch (\Exception $e) {
            // 出现错误则进行储存错误
            file_put_contents(storage_path('log').'/prices_editon/'.date('Y_m_d',time()).'.log',FILE_APPEND);
        }
    }
}
