<?php

namespace App\Models;


class SearchProductsListLog extends Base
{
    protected $table = 'search_products_list_log';

    // 设置允许入库字段,数组形式
    protected $fillable = [
        'id',
        'ip',
        'ip_addr',
        'keywords',
        'status',
        'sort',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];
}
