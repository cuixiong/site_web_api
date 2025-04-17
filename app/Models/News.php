<?php

namespace App\Models;
use App\Models\Base;
class News extends Base
{
    protected $table = 'news';


    protected $appends = ['thumb_img'];

    /**
     * 获取缩略图 , 获取默认图片
     *
     * @return mixed
     */
    public function getThumbImgAttribute()
    {
        if (!empty($this->attributes['thumb'])) {
            return Common::cutoffSiteUploadPathPrefix($this->attributes['thumb']);
        } else{
            return SystemValue::query()->where("key" , 'default_news_thumb')->value("value");
        }
    }

}
