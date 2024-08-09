<?php

namespace App\Const;

use huolib\constant\CommonConst;

class ApiCode {
    const INVALID_PARAM       = 10001; //参数错误
    const SHOP_CART_NOT_EXIST = 10002; //购物车商品失效
    const INSERT_FAIL         = 10003; //插入失败
    const ORDER_AMOUNT_ERROR  = 10004; //订单金额异常

    public static function getStatusMsg($status, $all = false) {
        $data = [
            //self::INVALID_PARAM       => trans('lang.INVALID_PARAM'),
            self::INVALID_PARAM       => '参数错误',
            self::SHOP_CART_NOT_EXIST => '购物车失效',
            self::INSERT_FAIL         => '插入异常',
            self::ORDER_AMOUNT_ERROR  => '订单金额异常',
        ];
        if (true == $all) {
            return $data;
        }

        return isset($data[$status]) ? $data[$status] : false;
    }
}



