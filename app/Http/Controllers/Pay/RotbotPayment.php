<?php

namespace App\Http\Controllers\Pay;

use App\Models\Pay as Payment;

class RotbotPayment extends Pay
{
    public $shopId   = '';
    public $actionUrl  = '';

    public function __construct()
    {
        $this->shopId = env('robotpayment_shop_id');
        $this->actionUrl = env('robotpayment_action_url');
    }

    public function createFormdata($order)
    {
        // TODO: Implement createFormdata() method.
    }

    public function do($order, $options = [])
    {

        $paymentModel = Payment::query()->where("code", $order->pay_code)->first();
        $rate = $paymentModel->pay_exchange_rate;

        $orderGoodsName = $order->getProductNameAttribute();

        if (!empty($product_names)) {
            $orderGoodsName = mb_strlen($orderGoodsName, 'utf8') > 80 ? mb_substr($orderGoodsName, 0, 80, 'utf8') . '...' : $orderGoodsName;
        }

        srand(time());
        $allPrice = round($order->actually_paid * $rate, 0);
        $tx = $allPrice / 11;
        $tx = round($tx, 0);
        $am = $allPrice - $tx;
        $formData = [
            'aid'            => $this->shopId,
            'pt'            => 1,
            'jb'            => 'CAPTURE',
            'am'           => $am,
            'tx'          => $tx,
            // 'am'           => ceil($order->order_amount),
            // 'tx'          => ceil($order->actually_paid-$order->order_amount),
            'sf'          => 0,
            'cod'          => $order->order_number,
            'iid2'          => $order->order_number,
            'inm'          => $orderGoodsName ?? '',
        ];

        $form = '<form id="pay_form" action="' . $this->actionUrl . '" method="post" target="_self" style="display:none;">';
        foreach ($formData as $key => $value) {
            $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '"/>';
        }
        $form .= '<input type="submit" value="Submit"/>';
        $form .= '</form>';
        $form .= '<script>document.getElementById("pay_form").submit();</script>';

        return $form;
    }


    public function notify()
    {
        // try {
        //     $reqData = $_POST;
        //     \Log::error('返回结果数据$reqData:' . json_encode([$reqData]) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);

        //     $orderId = trim($_GET['cod']??'');
        //     if (empty($orderId)) {
        //         \Log::error('order_id 为空' . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);
        //         return false;
        //     }

            

        //     $trade_no = $reqData['TranID'];
        //     $total_amount = $reqData['Amount'] + $reqData['Tax'];;
        //     $gmt_payment = strtotime($reqData['TranDate']);

        //     $dir = storage_path('log') . '/mgo-payment/' . date('Y_m/', time());
        //     if (!is_dir($dir)) {
        //         mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
        //     }
        //     $logName = date("d-H");
        //     $logName = $dir . $logName . '.log';
        //     $res = $this->handlerOrderPaySucService(
        //         $out_trade_no,
        //         $logName,
        //         $total_amount,
        //         $trade_no,
        //         $gmt_payment
        //     );
        //     \Log::error('res结果:' . json_encode([$res]) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);

        //     return false;
        // } catch (\Exception $e) {
        //     \Log::error('支付通知异常:' . json_encode($e->getMessage()) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);
        //     throw $e;
        // }

        // return false;
    }

    protected function curl($method, $url, $headers = [], $params = [])
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        $curlHeaders = [];
        foreach ($headers as $key => $header) {
            if ('Authorization' === $key) {
                $curlHeaders[] = "$key: Bearer " . $header;
            } else {
                $curlHeaders[] = "$key: " . $header;
            }
        }
        $curl = curl_init();
        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                $params = json_encode($params);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case "GET":
                if (count($params) > 0) {
                    $url = $url . '?' . http_build_query($params);
                }
                break;
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curlHeaders);
        $result = curl_exec($curl);

        return $result;
    }
}
