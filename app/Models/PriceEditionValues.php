<?php

namespace App\Models;

use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class PriceEditionValues extends Base {
    const RedisKey = 'PriceEditionValue';// 要与后台程序的KEY一致

    public static function GetList($languageId, $publisherId, $priceEditionsValue = null, $priceEditionsPid = null) {
        //重写一个新方法
        return self::getNewList($languageId, $publisherId, $priceEditionsValue, $priceEditionsPid);
        try {
            $priceEditionIds = PriceEditions::GetList($publisherId, $priceEditionsPid);
            $priceEditions = $priceEditionsValue ? $priceEditionsValue : Redis::hgetall(self::RedisKey);
            if (empty($priceEditions)) {
                return [];
            } else {
                $result = [];
                if (!empty($priceEditions) && is_array($priceEditions)) {
                    foreach ($priceEditions as $key => $value) {
                        $value = json_decode($value, true);
                        if ($value['status'] == '1' && in_array($value['edition_id'], $priceEditionIds)
                            && $value['language_id'] == $languageId) {
                            $res = $value;
                            $result[] = $res;
                        }
                    }
                }

                return $result;
            }
        } catch (\Exception $e) {
            // 出现错误则进行储存错误
            file_put_contents(storage_path('log').'/prices_editon/'.date('Y_m_d', time()).'.log', FILE_APPEND);
        }
    }

    public static function getNewList($languageId, $publisherId, $priceEditionsValue = null, $priceEditionsPid = null) {
        $edition_id_list = [];
        if (!empty($priceEditionsPid)) {
            $lists = $priceEditionsPid;
        } else {
            $lists = PriceEditions::query()->where("status", 1)->get()->toArray();
        }
        foreach ($lists as $key => $value) {
            if (!empty($priceEditionsPid)) {
                //缓存那套业务兼容
                $value = json_decode($value, true);
                if ($value['status'] == '1') {
                    $value['publisher_id'] = explode(',', $value['publisher_id']);
                    if (in_array($publisherId, $value['publisher_id'])) {
                        $edition_id_list[] = $value['id'];
                    }
                }
            } else {
                //Db数据库查询
                $value['publisher_id'] = explode(',', $value['publisher_id']);
                if (in_array($publisherId, $value['publisher_id'])) {
                    $edition_id_list[] = $value['id'];
                }
            }
        }
        if (!empty($priceEditionsValue)) {
            $priceEditionsList = $priceEditionsValue;
        } else {
            $priceEditionsList = PriceEditionValues::query()->where("status", 1)
                                                   ->where("language_id", $languageId)
                                                   ->whereIn("edition_id", $edition_id_list)
                                                   ->select("id", "name", "notice", "sort", "rules")
                                                   ->orderBy("sort", "asc")
                                                   ->get()->toArray();
        }
        $rData = [];
        foreach ($priceEditionsList as $key => $priceEditionsItem) {
            if (!empty($priceEditionsValue)) {
                $value = json_decode($priceEditionsItem, true);
                if ($value['status'] == '1' && in_array($value['edition_id'], $edition_id_list)
                    && $value['language_id'] == $languageId) {
                    $rData[] = $value;
                }
            } else {
                $rData[] = $priceEditionsItem;
            }
        }

        return $rData;
    }

    public static function GetPriceEditonsIds() {
        $data = Redis::hgetall(self::RedisKey);
        $ids = array_keys($data);

        return $ids;
    }
}
