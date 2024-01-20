<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Base extends Model
{
    // 时间戳
    protected $dateFormat = 'U';
    protected $casts = [
        'published_date' => 'date:Y-m-d H:i:s',
    ];
}
