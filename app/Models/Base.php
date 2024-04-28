<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Predis\Command\Redis\LTRIM;

class Base extends Model
{
    // 时间戳
    protected $dateFormat = 'U';
    protected $casts = [
        'published_date' => 'date:Y-m-d',
    ];

    /**
     * 创建时间获取器
     * @param \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function createdAt(): Attribute
    {

        return Attribute::make(
            get: fn ($value) => date('Y-m-d H:i:s',strtotime($value)),
        );
    }
    /**
     * 更新时间获取器
     * @param \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => date('Y-m-d H:i:s',strtotime($value)),
        );
    }


}
