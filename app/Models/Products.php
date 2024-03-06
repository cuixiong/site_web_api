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

    /**
     * @param int $priceEdition
     * @param Products|array $goods 必须要有这个字段 price
     * @return int
     */
    public static function getPrice($priceEdition, $goods)
    {
        $priceRule = PriceEditionValues::where('id',$priceEdition)->value('rules');
        $price = eval("return " . sprintf($priceRule, $goods['price']) . ";");
        return $price;
    }

    /**
     * 获取订单里的每个商品的原价格（不带折扣或带折扣）
     */
    public static function getPriceBy($price, $goods, $timestamp = null)
    {
        if ($timestamp !== null) {
            $timestamp = time();
        }
        $actuallyPaid = $price;
        if ($timestamp >= $goods['discount_begin'] && $timestamp <= $goods['discount_end']) { // 如果队列不能把discount_begin和discount_end的值恢复成null，就不能要这句代码了
            if ($goods['discount_type'] == 1) {
                $actuallyPaid = $price * $goods['discount_value'] / 100;
            } else if ($goods['discount_type'] == 2) {
                $actuallyPaid = bcsub($price, $goods['discount_value'], 2);
            }
        }
        return $actuallyPaid;
    }
}
