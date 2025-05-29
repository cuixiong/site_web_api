<?php

namespace App\Models;

use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class DictionaryValue extends Base {
    // 获取某一个code字典的全部选项
    public static function GetDicOptions($code) {
        if (checkSiteAccessData(['mrrs', 'yhen', 'qyen' ,'mmgen'])) {
            $field_list = ['english_name as label', 'value'];
        } else {
            $field_list = ['name as label', 'value'];
        }
        $list = self::select($field_list)
                    ->where(['status' => 1, 'code' => $code])
                    ->orderBy('sort', 'ASC')
                    ->get()
                    ->toArray();

        return $list;
    }
}
