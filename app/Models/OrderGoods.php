<?php

namespace App\Models;

use App\Models\Base;

class OrderGoods extends Base {
    protected $table = 'order_goods';
    //报告信息
    protected $product_info = [];
    //报告价格版本信息
    protected $price_edition_info = [];

    public function getProductInfoAttribute() {
        $rdata = [
            'id'             => '',
            'name'           => '',
            'thumb'          => '',
            'published_date' => '',
        ];
        $data = Products::query()->where('id', $this->attributes['goods_id'])
                        ->select('id', 'name', 'thumb', 'published_date')
                        ->first();
        if (!empty($data)) {
            $data = $data->toArray();
            $rdata = [
                'id'             => $data['id'],
                'name'           => $data['name'],
                'thumb'          => $data['thumb'],
                'published_date' => $data['published_date'],
            ];

            return $rdata;
        } else {
            return $rdata;
        }
    }

    public function getPriceEditionInfoAttribute() {
        $rdata = [
            'id'         => '',
            'price_name' => '',
            'price_lang' => '',
        ];
        if (empty($this->attributes['price_edition'])) {
            return $rdata;
        }
        $data = PriceEditionValues::query()
                                  ->where('price_edition_values.id', $this->attributes['price_edition'])
                                  ->select(
                                      'price_edition_values.id',
                                      'price_edition_values.name',
                                      'price_edition_values.language_id',
                                      'languages.name as language_name',
                                  )
                                  ->leftJoin('languages', 'languages.id', '=', 'price_edition_values.language_id')
                                  ->first();
        if (!empty($data)) {
            $data = $data->toArray();
            $rdata = [
                'id'         => $data['id'],
                'price_name' => $data['name'],
                'price_lang' => $data['language_name'],
            ];
        }

        return $rdata;
    }
}
