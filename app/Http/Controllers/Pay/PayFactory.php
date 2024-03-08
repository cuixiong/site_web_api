<?php
namespace App\Http\Controllers\Pay;
use App\Http\Controllers\Controller;
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
            case Payment::where('code','ALIPAL')->value('id');
                $pay = new Alipay();
                break;
            case Payment::where('code','WECHATPAY')->value('id');
                $pay = new Wechatpay();
                break;
        }

        return $pay;
    }
}