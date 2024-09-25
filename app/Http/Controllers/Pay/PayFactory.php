<?php

namespace App\Http\Controllers\Pay;

use App\Const\PayConst;
use App\Http\Controllers\Controller;
use App\Models\Pay;
use App\Models\Payment;

class PayFactory extends Controller
{
    /**
     * @param int $type
     * @return PayInterface
     */
    // public static function create($type)
    // {
    //     $pay = null;
    //     switch ($type) {
    //         case Pay::where('code','ALIPAL')->value('id');
    //             $pay = new Alipay();
    //             break;
    //         case Pay::where('code','WECHATPAY')->value('id');
    //             $pay = new Wechatpay();
    //             break;
    //         case Pay::where('code','STRIPE')->value('id');
    //             $pay = new Stripepay();
    //             break;
    //         case Pay::where('code','FIRSTDATA')->value('id');
    //             $pay = new FirstDatapay();
    //             break;
    //         case Pay::where('code','PAYPAL')->value('id');
    //             $pay = new PaypalPay();
    //             break;
    //     }

    //     return $pay;
    // }

    public static function create($code)
    {
        $pay = null;
        switch ($code) {
            case PayConst::PAY_TYPE_ALIPAY;
                $pay = new Alipay();
                break;
            case PayConst::PAY_TYPE_WXPAY;
                $pay = new Wechatpay();
                break;
            case PayConst::PAY_TYPE_STRIPEPAY;
                $pay = new Stripepay();
                break;
            case PayConst::PAY_TYPE_FIRSTDATAPAY;
                $pay = new FirstDatapay();
                break;
            case PayConst::PAY_TYPE_PAYPAL;
                $pay = new PaypalPay();
                break;
        }

        return $pay;
    }
}
