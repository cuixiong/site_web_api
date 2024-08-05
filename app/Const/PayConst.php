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
    const PAY_TYPE_WXPAY     = 'ALIPAL';
    const PAY_TYPE_ALIPAY    = 'WECHATPAY';
    const PAY_TYPE_STRIPEPAY = 'STRIPE';
    const PAY_TYPE_FIRSTDATAPAY = 'FIRSTDATA';
}
