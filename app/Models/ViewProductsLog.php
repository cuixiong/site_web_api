<?php

namespace App\Models;
class ViewProductsLog extends Base
{
    protected $table = 'view_products_log';
    protected $fillable = [
        'user_id', 'product_id', 'ip', 'ip_addr', 'product_name', 'keyword', 'view_cnt', 'view_date_str'
    ];
    protected $appends = ['user_name'];



}
