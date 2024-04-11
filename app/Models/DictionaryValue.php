<?php

namespace App\Models;

use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class DictionaryValue extends Base
{
    // 获取某一个code字典的全部选项
    public static function GetDicOptions($code)
    {
        $list = self::select(['name as label', 'value'])
            ->where(['status' => 1,'code'=>$code])
            ->orderBy('sort', 'ASC')
            ->get()
            ->toArray();

        return $list;
    }
}
