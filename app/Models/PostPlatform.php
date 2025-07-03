<?php

namespace App\Models;

use App\Models\Base;

class PostPlatform extends Base {
    protected $table = 'post_platform';
    protected $fillable
                     = [
            'name',
            'keywords',
            'status',
            'sort',
        ];
}
