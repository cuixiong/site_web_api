<?php

namespace App\Models;

use App\Models\Base;

class ProductsCategory extends Base
{
    protected $table = 'product_category';


    /**
     * 返回报告分类
     * @return array|null
     */
    public static function getProductCategory($hasPrompt = true)
    {
        $field = ['id', 'name', 'link'];
        $data = self::select($field)
            ->where('status', 1)
            ->get()
            ->toArray();
        if ($hasPrompt) {
            array_unshift($data, [
                'id'   => '0',
                'name' => '全部',
                'link' => '',
            ]);
        }

        return $data;
    }
}
