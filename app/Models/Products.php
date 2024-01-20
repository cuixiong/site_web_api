<?php

namespace App\Models;
use App\Models\Base;
class Products extends Base
{
    protected $table = 'product_routine';

    public function getPublishedDataAttributes($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}
