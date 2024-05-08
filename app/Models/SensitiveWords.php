<?php

namespace App\Models;

use App\Models\Base;

class SensitiveWords extends Base {
    // 设置允许入库字段,数组形式
    protected $fillable = ['word', 'status', 'sort'];
}
