<?php

namespace App\Models;
use App\Models\Base;
use Illuminate\Support\Facades\Redis;

class Products extends Base
{
    protected $table = 'product_routine';

    public function getPublishedDataAttributes($value)
    {
        return date('Y-m-d', $value);
    }

    /**
     * @param int $priceEdition
     * @param Products|array $goods 必须要有这个字段 price
     * @return int
     */
    public static function getPrice($priceEdition, $goods)
    {
        $priceRule = Redis::hget(PriceEditionValues::RedisKey,$priceEdition);
        $priceRule = json_decode($priceRule,true);
        $priceRule = $priceRule['rules'];
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
        if ($timestamp >= $goods['discount_time_begin'] && $timestamp <= $goods['discount_time_end']) { // 如果队列不能把discount_time_begin和discount_time_end的值恢复成null，就不能要这句代码了
            if ($goods['discount_type'] == 1) {
                $actuallyPaid = $price * $goods['discount'] / 100;
            } else if ($goods['discount_type'] == 2) {
                $actuallyPaid = bcsub($price, $goods['discount_amount'], 2);
            }
        }
        return $actuallyPaid;
    }

    /**
     * 通过价格和价格版本进行计算价格
     */
    public static function CountPrice($price,$publisherId,$languages = null ,$priceEditionsValue = null, $priceEditionsPid = null)
    {
        // 这里的代码可以复用 开始
        $prices = [];
        // 计算报告价格（当前语言是放在站点端的，但是后台的语言是放在总控端的，接手的小伙伴自己改）
        $languages = $languages ? $languages : Languages::GetList();
        if ($languages) {
            foreach ($languages as $index => $language) {
                $priceEditions = PriceEditionValues::GetList($language['id'],$publisherId,$priceEditionsValue,$priceEditionsPid);
                if ($priceEditions) {
                    $prices[$index]['language'] = $language['name'];
                    foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                        $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                        $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['name'];
                        $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                        $prices[$index]['data'][$keyPriceEdition]['sort'] = $priceEdition['sort'];
                        $prices[$index]['data'][$keyPriceEdition]['price'] = eval("return " . sprintf($priceEdition['rules'], $price) . ";");
                    }
                }
            }
        }
        $prices = array_values($prices);
        return $prices;
        // 这里的代码可以复用 结束
    }
}
