<?php
/**
 * PayConst.php UTF-8
 * 支付常量
 *
 * @date    : 2024/7/31 11:17 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Const;
class PayConst {
    const PAY_TYPE_WXPAY        = 'WECHATPAY';
    const PAY_TYPE_ALIPAY       = 'ALIPAL';
    const PAY_TYPE_STRIPEPAY    = 'STRIPE';
    const PAY_TYPE_FIRSTDATAPAY = 'FIRSTDATA';
    const PAY_TYPE_PAYPAL       = 'PAYPAL';
    const PAY_TYPE_AIRWALLEXPAY = 'AIRWALLEX';
    //货币类型
    const COIN_TYPE_USD = 'USD';
    const COIN_TYPE_CNY = 'CNY';
    const COIN_TYPE_EUR = 'EUR';
    const COIN_TYPE_GBP = 'GBP';
    const COIN_TYPE_HKD = 'HKD';
    const COIN_TYPE_JPY = 'JPY';
    const COIN_TYPE_AUD = 'AUD';
    const COIN_TYPE_CAD = 'CAD';
    const COIN_TYPE_CHF = 'CHF';
    const COIN_TYPE_DKK = 'DKK';
    public static $coinTypeALL
        = [
            self::COIN_TYPE_USD,
            self::COIN_TYPE_CNY,
            self::COIN_TYPE_EUR,
            self::COIN_TYPE_GBP,
            self::COIN_TYPE_HKD,
            self::COIN_TYPE_JPY,
            self::COIN_TYPE_AUD,
            self::COIN_TYPE_CAD,
            self::COIN_TYPE_CHF,
            self::COIN_TYPE_DKK,
        ];
    public static $coinTypeMap
        = [
            self::COIN_TYPE_USD => '美元',
            self::COIN_TYPE_CNY => '人民币',
            self::COIN_TYPE_EUR => '欧元',
            self::COIN_TYPE_GBP => '英镑',
            self::COIN_TYPE_HKD => '港币',
            self::COIN_TYPE_JPY => '日元',
            self::COIN_TYPE_AUD => '澳元',
            self::COIN_TYPE_CAD => '加元',
        ];
    public static $coinTypeSymbol
        = [
            self::COIN_TYPE_USD => '$',
            self::COIN_TYPE_CNY => '￥',
            self::COIN_TYPE_EUR => '€',
            self::COIN_TYPE_GBP => '£',
            self::COIN_TYPE_HKD => 'HK$',
            self::COIN_TYPE_JPY => '¥',
            self::COIN_TYPE_AUD => 'AU$',
            self::COIN_TYPE_CAD => 'CA$',
        ];
}
