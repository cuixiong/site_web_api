<?php

namespace App\Models;

use App\Models\Base;

class Coupon extends Base {
    protected $fillable
        = [
            'code', 'type', 'value', 'user_ids', 'time_begin', 'time_end', 'sort', 'status'
        ];
}
