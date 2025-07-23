<?php

namespace App\Http\Controllers\Pay;

use App\Models\Pay as Payment;

class RobotPayment extends Pay
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
        try {
            $reqData = $_GET;
            \Log::error('返回结果数据$reqData:' . json_encode([$reqData]) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);


            // 通过IP验证是否第三方支付的通知
            $robotPaymentIps = [
                "115.30.22.70",
                "54.95.223.30",
                "54.168.57.171"
            ];
            if (!in_array($_SERVER["REMOTE_ADDR"], $robotPaymentIps)) {
                \Log::error('非Robot Payment 的IP' . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);
                return false;
            }

            $payCode  = $_GET['rst'];  // 支付状态
            $trade_no  = $_GET['gid'];  // 決済番号
            // $trade_no  = $_GET['god'];  // オーダーコード
            $out_trade_no  = $_GET['cod'];  // 店舗オーダー番号  订单号
            $am   = $_GET['am'];   // 決済金額
            $tx   = $_GET['tx'];   // 税金額
            $total_amount   = $_GET['ta'];   // 合計金額

            if (empty($out_trade_no)) {
                \Log::error('订单号 为空' . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);
                return false;
            }

            $gmt_payment = time();

            $dir = storage_path('log') . '/robot-payment/' . date('Y_m/', time());
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
            }
            $logName = date("d-H");
            $logName = $dir . $logName . '.log';


            if ($payCode == '1') {
                $res = $this->handlerOrderPaySucService(
                    $out_trade_no,
                    $logName,
                    $total_amount,
                    $trade_no,
                    $gmt_payment
                );
            } else {
                $res = '支付失败' . $payCode;
            }

            \Log::error('res结果:' . json_encode([$res]) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);

            return false;
        } catch (\Exception $e) {
            \Log::error('支付通知异常:' . json_encode($e->getMessage()) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);
            return false;
        }

        return true;
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
