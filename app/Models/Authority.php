<?php

namespace App\Models;
use App\Models\Base;
class Authority extends Base
{
    protected $appends = ['category_name'];

    public function getCategoryNameAttribute()
    {
        if(!empty($this->attributes['category_id'] )){
            return QuoteCategory::query()->where("id" , $this->attributes['category_id'])->value("name");
        }else{
            return "";
        }

    }

}
