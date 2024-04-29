<?php

namespace App\Models;

use App\Models\Base;

class UserAddress extends Base {
    protected $fillable
                       = ['user_id', 'address', 'city_id', 'province_id', 'consignee', 'contact_number', 'is_default',
                          'email'];
    protected $table   = 'user_address';
    protected $appends = ['province_id', 'city_id'];

    /**
     * 省份获取器
     */
    public function getProvinceIdAttribute() {
        $text = '';
        if (isset($this->attributes['province_id']) && !empty($this->attributes['province_id'])) {
            $text = City::query()->where('id', $this->attributes['province_id'])->value('name') ?? '';
        }

        return $text ?? '';
    }

    /**
     * 城市获取器
     */
    public function getCityIdAttribute() {
        $text = '';
        if (isset($this->attributes['city_id']) && !empty($this->attributes['city_id'])) {
            $text = City::query()->where('id', $this->attributes['city_id'])->value('name') ?? '';
        }

        return $text ?? '';
    }
}
