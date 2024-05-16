<?php

namespace App\Models;

use App\Models\Base;

class CouponUser extends Base {
    const isUsedNO  = 0; //未使用
    const isUsedYes = 1; //已使用
    protected $fillable
        = [
            'user_id', 'coupon_id', 'is_used', 'use_time', 'order_id'
        ];
}
