<?php

namespace App\Http\Controllers\Pay;

use App\Models\Pay as Payment;

class GmoPayment extends Pay
{
    public $shopId   = '';  // shop店铺id，不是site站点id
    public $shopPwd  = '';  // shop密码
    public $configId  = ''; // 配置模板id
    public $gatewayUrl = '';    // 网关地址

    public function __construct()
    {
        $this->shopId = env('gmo_shop_id');
        $this->shopPwd = env('gmo_shop_password');
        $this->configId = env('gmo_pay_template_name');
        $this->gatewayUrl = env('gmo_gateway_url');
    }

    public function createFormdata($order)
    {
        // TODO: Implement createFormdata() method.
    }
    public function do($order, $options = [])
    {

        // 日元支付获取汇率 Pay类名与父类冲突
        $paymentModel = Payment::query()->where("code", $order->pay_code)->first();
        $rate = $paymentModel->pay_exchange_rate;
        // $tax = $paymentModel->pay_tax_rate;

        //报告名称
        $orderGoodsName = $order->getProductNameAttribute();

        if (!empty($product_names)) {
            $orderGoodsName = mb_strlen($orderGoodsName, 'utf8') > 80 ? mb_substr($orderGoodsName, 0, 80, 'utf8') . '...' : $orderGoodsName;
        }
        // return $product_names;
        srand(time());
        $allPrice = round($order->actually_paid * $rate, 0); // 含税实付
        $tx = $allPrice / 11;   // 税额
        $tx = round($tx, 0);
        $am = $allPrice - $tx; // 不含税实付
        // return $product_names;
        $domain = rtrim(env('APP_URL', ''), '/');
        $returnUrl = $domain . '/paymentComplete/' . $order->id;
        $param = [
            'configid'          => $this->configId,
            'transaction'       => [
                'OrderID'       => $order->order_number,
                'Amount'        => $am,
                'Tax'           => $tx,
                'PayMethods'    => ["credit"],
                'Detail'        => $orderGoodsName,
                'RetUrl'        => $returnUrl,
                'CompleteUrl'  => $returnUrl,
                'CancelUrl'     => $returnUrl,
                'ResultSkipFlag'     => 0,
                'ConfirmSkipFlag'     => 1,
                'TranDetailShowFlag'    => 1,   // 默认展开详情
            ],
            'credit' => [
                'JobCd' => 'CAPTURE',   // 即时捕获(扣款) 
                'Method' => 1,  // 批量
                'TdFlag' => 0, // 2:使用3DS2.0
            ],

        ];

        // JSON文字列をBASE64エンコード（URLSafe／文字コード：UTF-8）
        $param_json = json_encode($param, JSON_UNESCAPED_UNICODE);
        $base64Encode = str_replace(array('+', '/'), array('-', '_'), base64_encode($param_json));

        // BASE64エンコードしたJSONとショップパスワードを文字列結合し、SHA256でハッシュ化
        $hash = hash('sha256', $base64Encode . ($this->shopPwd));

        // BASE64エンコードしたJSONに「.(ドット)」及び、SHA256ハッシュを文字列結合
        $parameter = $base64Encode . '.' . $hash;

        // 決済URL、ショップID、機能種別(※)、ハッシュ付き実行パラメータセットを文字列結合しURLを生成
        //   (※)決済：checkout、カード編集：member
        $paymentUrl = "{$this->gatewayUrl}/v1/plus/{$this->shopId}/checkout/{$parameter}";

        header("Location: " . $paymentUrl);
    }


    public function notify()
    {
        try {
            $reqData = $_POST;
            \Log::error('返回结果数据$reqData:' . json_encode([$reqData]) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);

            // 校验
            // 没得校验
            // 校验结束

            //获取单号
            $out_trade_no = $reqData['OrderID'];
            $status = $reqData['Status'];

            if ($status != 'CAPTURE') {
                return false;
            }

            $trade_no = $reqData['Amount'];
            $total_amount = $reqData['Amount'] + $reqData['Tax'];;
            $gmt_payment = strtotime($reqData['TranDate']);
            $dir = storage_path('log') . '/mgo-payment/' . date('Y_m/', time());
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
            }
            $logName = date("d-H");
            $logName = $dir . $logName . '.log';
            $res = $this->handlerOrderPaySucService(
                $out_trade_no,
                $logName,
                $total_amount,
                $trade_no,
                $gmt_payment
            );
            \Log::error('res结果:' . json_encode([$res]) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);

            return false;
        } catch (\Exception $e) {
            \Log::error('支付通知异常:' . json_encode($e->getMessage()) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);
            throw $e;
        }

        return false;
    }
}
