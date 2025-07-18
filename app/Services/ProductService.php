<?php
/**
 * ProductService.php UTF-8
 * 订单业务类
 *
 * @date    : 2024/7/18 13:40 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Services;

use App\Models\PriceEditionValues;

class ProductService {
    public static $price_value_ids = '';

    public function __construct() {
        $id_list = PriceEditionValues::query()
                                     ->where("status", 1)
                                     ->where("is_deleted", 1)
                                     ->pluck("id")->toArray();
        self::$price_value_ids = implode(",", $id_list);
    }

    public static function getAllPriceValuesIds(): string {
        return self::$price_value_ids;
    }
}
