<?php

namespace App\Models;

use App\Models\Base;

class ContactUs extends Base {
    protected $table = 'contact_us';
    protected $fillable
                     = [
            'category_id', 'product_id', 'name', 'phone', 'email', 'company', 'content', 'country_id', 'province_id',
            'city_id', 'buy_time', 'channel', 'language_version', 'address'
        ];
}
