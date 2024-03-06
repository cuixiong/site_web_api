<?php

namespace App\Models;
use App\Models\Base;
class Order extends Base
{
    public static function payType(): array
    {
        static $payType = null;
        if ($payType === null) {
            $payment = Payment::select('id', 'code')->get()->toArray();
            $payType = [];
            for ($i = 0, $len = count($payment); $i < $len; $i++) {
                $payType[$payment[$i]['id']] = $payment[$i]['code'];
            }
        }
        return $payType;
    }
}
