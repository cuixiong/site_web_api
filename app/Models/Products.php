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

    /**
     * 通过价格和价格版本进行计算价格
     */
    public static function CountPrice($price)
    {
        // 这里的代码可以复用 开始
        $prices = [];
        // 计算报告价格（当前语言是放在站点端的，但是后台的语言是放在总控端的，接手的小伙伴自己改）
        $languages = Languages::select(['id', 'name'])->get()->toArray();
        if ($languages) {
            foreach ($languages as $index => $language) {
                $priceEditions = PriceEditionValues::GetList($language['id']);
                $prices[$index]['language'] = $language['name'];
                if ($priceEditions) {
                    foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                        $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                        $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['edition'];
                        $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                        $prices[$index]['data'][$keyPriceEdition]['price'] = eval("return " . sprintf($priceEdition['rule'], $price) . ";");
                    }
                }
            }
        }
        return $prices;
        // 这里的代码可以复用 结束
    }
}
