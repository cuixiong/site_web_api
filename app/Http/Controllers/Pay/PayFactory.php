<?php
namespace App\Http\Controllers\Pay;
use App\Http\Controllers\Controller;
use App\Models\Pay;
use App\Models\Payment;

class PayFactory extends Controller
{
    /**
     * @param int $type
     * @return PayInterface
     */
    public static function create($type)
    {
        $pay = null;
        switch ($type) {
            case Pay::where('code','ALIPAL')->value('id');
                $pay = new Alipay();
                break;
            case Pay::where('code','WECHATPAY')->value('id');
                $pay = new Wechatpay();
                break;
            case Pay::where('code','STRIPE')->value('id');
                $pay = new Stripepay();
                break;
        }

        return $pay;
    }
}
