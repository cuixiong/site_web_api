<?php
/**
 * AirwallexPay.php UTF-8
 * 云上支付逻辑
 *
 * @date    : 2024/10/24 15:20 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers\Pay;


use App\Models\Order;

class AirwallexPay extends Pay {
    public $apiUrl     = '';
    public $client_id  = '';
    public $secret_id  = '';
    public $webhook_id = '';

    public function __construct() {
        $this->client_id = env('airwallex_client_id', '7EAouLiHRdqmTgIy0C-yrA');
        $this->secret_id = env(
            'airwallex_secret_id',
            '0f5bb5da9b3153a36ba245436db410e05fd305aa750218cc962665e2abebbcb9143cf77ef28eff270288fe5a4d3b4b29'
        );
        $this->webhook_id = env('airwallex_webhook_id', 'whsec_OF_wESDsqyVQXk2ozFj2GpBbPNlobouu');
        //$this->apiUrl = env('', 'https://api.airwallex.com');
        $this->apiUrl = env('airwallex_api_url', 'https://api-demo.airwallex.com');
    }

    public function createFormdata($order) {
        // TODO: Implement createFormdata() method.
    }

    public function notify() {
        try {
            $payload = @file_get_contents('php://input');
            $reqData = json_decode($payload, true);
            \Log::error('返回结果数据$reqData:'.json_encode([$reqData]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            //校验签名
            $timestamp = request()->header('x-timestamp');
            $signature = request()->header('x-signature');
            $body = $payload;
            //验签
            $secret = $this->webhook_id;
            $makeSignature = hash_hmac('sha256', $timestamp.$body, $secret);

            if ($makeSignature != $signature) {
                \Log::error('验签失败!:'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

                return false;
            }
            $out_trade_no = $reqData['data']['object']['merchant_order_id'];
            $status = $reqData['data']['object']['status'];
            if($status != 'SUCCEEDED'){
                //不是成功状态, 修改订单状态为失败, 且跳过
                $order = Order::where('order_number', $out_trade_no)->first();
                if (!$order) {
                    \Log::error('返回结果数据:$out_trade_no'.$out_trade_no.' . 订单不存在 文件路径:'.__CLASS__.'  行号:'.__LINE__);
                    return false;
                }
                if ($order['is_pay'] == Order::PAY_UNPAID) {
                    //未支付状态改为支付失败状态
                    $order->is_pay = Order::PAY_FAILED;
                    $order->updated_at = time();
                    $order->save();
                    return false;
                }
                return false;
            }

            $trade_no = $reqData['data']['object']['latest_payment_attempt']['provider_transaction_id'];
            $total_amount = $reqData['data']['object']['latest_payment_attempt']['amount'];
            $gmt_payment = strtotime($reqData['created_at']);
            $dir = storage_path('log').'/airwallex/'.date('Y_m/', time());
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
            }
            $logName = date("d-H");
            $logName = $dir.$logName.'.log';
            $res = $this->handlerOrderPaySucService(
                $out_trade_no, $logName, $total_amount, $trade_no, $gmt_payment
            );
            \Log::error('res结果:'.json_encode([$res]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return false;
        } catch (\Exception $e) {
            \Log::error('云上支付通知异常:'.json_encode($e->getMessage()).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            throw $e;
        }

        return false;
    }

    public function do($order, $options = []) {
        $domain = rtrim(env('APP_URL', ''), '/');
        $returnUrl = $domain.'/paymentcomplete/'.$order->id; // 同步回调地址
        $config = [
            'clientId'   => $this->client_id,
            'apiKey'     => $this->secret_id,
            'production' => false,
        ];
        $airwallex = new \Jitoot\Airwallex\Client($config);
        $orderNumber = $order['order_number'];
        $payData = [
            'amount'            => $order->actually_paid,
            'currency'          => 'USD',
            'merchant_order_id' => $orderNumber,
        ];
        $payData['metadata'] = [];
        $payData['metadata']['order_number'] = $orderNumber;
        $payData['request_id'] = $orderNumber.'-'.time();
        // 后面托管页面展示描述，在此我填写报告名称 长度32
        //$orderGoodsName = $order->getProductNameAttribute();
        //$payData['descriptor'] = $orderGoodsName;
        list($code, $resData) = $airwallex->paymentIntent->create($payData);
        if (!empty($code) && $code == 201) {
            $intent_id = $resData->id;
            $client_secret = $resData->client_secret;
            $hostPageHtml = $this->hostPage(
                $order->id, $intent_id,
                $client_secret,
                'USD'
            );

            return $hostPageHtml;
        } else {
            \Log::error('airwallex支付异常:'.json_encode([$code, $resData]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return false;
        }
    }

    public function hostPage($order_id, $intent_id, $client_secret, $currency = 'USD', $env = 'demo') {
        $domain = rtrim(env('APP_URL', ''), '/');
        // 支付成功跳转链接
        $successUrl = $domain.'/paymentComplete/'.$order_id;
        // 支付失败跳转链接
        $failUrl = $domain.'/paymentComplete/'.$order_id;
        $html = <<<EOF
                <!DOCTYPE html>
                <head>
                    <script src="https://checkout.airwallex.com/assets/elements.bundle.min.js"></script>
                </head>
                <html>
                    <script>
                    Airwallex.init({
                        env: '$env', // Setup which Airwallex env('demo' | 'prod') to integrate with
                        origin: window.location.origin, // Set up your event target to receive the browser events message
                      });
                    Airwallex.redirectToCheckout({
                        env: '$env', // Which env('staging' | 'demo' | 'prod') you would like to integrate with
                        intent_id: '$intent_id',
                        client_secret: '$client_secret',
                        currency: '$currency',
                        successUrl: '$successUrl',
                        failUrl: '$failUrl',
                    });
                    </script>
                </html>
                EOF;

        return $html;
    }

    /**
     * 发起POST请求
     */
    public function post($url, $headers = array(), $request = '', $methed = 'POST') {
        $header_res = [];
        $curl = curl_init($url);
        if ($methed == 'POST') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POST, 1);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $methed);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if ($request) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$header_res) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                {
                    return $len;
                }
                $header_res[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        $response_data = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response_data, true);

        return $result;
    }
}
