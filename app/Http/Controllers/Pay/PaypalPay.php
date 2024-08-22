<?php
/**
 * PaypalPay.php UTF-8
 * Paypal支付逻辑
 *
 * @date    : 2024/8/20 13:34 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers\Pay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class PaypalPay extends Pay {
    //public $apiUrl = 'https://api.sandbox.paypal.com'; //沙箱
    //public $apiUrl = 'https://api-m.paypal.com'; //正式
    public $apiUrl = '';
    public $client_id  = '';
    public $secret_id  = '';
    public $webhook_id = '';

    public function __construct() {
        $this->client_id = env('paypal_client_id', '');
        $this->secret_id = env('paypal_secret_id', '');
        $this->webhook_id = env('paypal_webhook_id', '');
        $this->apiUrl = env('paypal_api_url', 'https://api.sandbox.paypal.com');
    }

    public function createFormdata($order) {
        // TODO: Implement createFormdata() method.
    }

    public function notify() {
        try {
            $checkRes = $this->verifySignAPI();
//            $checkRes = $this->verifySignSelf();
            if ($checkRes) {
                $input = request()->input();
                $out_trade_no = $input['resource']['custom_id']; //以palpal为角度, 我们系统的订单号,就是他们的订单号
                $trade_no = $input['resource']['id'];
                $gmt_payment = strtotime($input['create_time']);
                $total_amount = $input['resource']['amount']['value'];

                $dir = storage_path('log').'/paypal/'.date('Y_m/', time());
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
                }
                $logName = date("d-H");
                $logName = $dir.$logName.'.log';
                return $this->handlerOrderPaySucService($out_trade_no, $logName, $total_amount, $trade_no, $gmt_payment);
            }

            return false;
        } catch (\Exception $e) {
            // Invalid signature
            \Log::error('paypal异常数据:'.json_encode([$e->getMessage()]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            throw $e;
        }

        return false;
    }

    public function getAccessToken() {
        //return 'Bearer A21AAKtYuIFs5urcUcBw3Jfbv9vLthDEHrDyOuhfWmzuRX5Ws5su4TwwG3uiaOQQwYPQMSNQ7mupFQtfpm3njgsdMDDO0U99A';
        $accessTokenCacheKey = 'palpay_access_token_'.$this->client_id;
        $accessToken = Redis::get($accessTokenCacheKey);
        if (empty($accessToken)) {
            \Log::error('结果获取token  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            $curl = curl_init();
            $url = $this->apiUrl.'/v1/oauth2/token';
            $token = 'Basic '.base64_encode($this->client_id.':'.$this->secret_id);
            curl_setopt_array($curl, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
                CURLOPT_HTTPHEADER     => array(
                    'Authorization: '.$token,
                ),
            ));
            $response = curl_exec($curl);
            $result = json_decode($response, true);
            curl_close($curl);
            if (!empty($result['access_token'])) {
                $accessToken = $result['token_type']." ".$result['access_token'];
                $expires_in = $result['expires_in'] - 200;
                Redis::setex($accessTokenCacheKey, $expires_in, $accessToken);
            } else {
                return '';
            }
        }

        return $accessToken;
    }

    /**
     *  API验签
     */
    public function verifySignAPI() {
        $accessToken = $this->getAccessToken();
        // 获取PayPal Webhook事件中的数据
        $authAlgo = request()->header('paypal-auth-algo', '');
        $transmissionId = request()->header('paypal-transmission-id', '');
        $transmissionTime = request()->header('paypal-transmission-time', '');
        $certUrl = request()->header('paypal-cert-url', '');
        $transmissionSig = request()->header('paypal-transmission-sig', '');
        if (!$authAlgo || !$transmissionId || !$transmissionTime || !$certUrl || !$transmissionSig) {
            \Log::error('PayPal 数据异常:'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return false;
        }
        $signatureVerification = [
            'auth_algo'         => $authAlgo,
            'cert_url'          => $certUrl,
            'transmission_id'   => $transmissionId,
            'transmission_sig'  => $transmissionSig,
            'transmission_time' => $transmissionTime,
            'webhook_id'        => $this->webhook_id,  // Replace with your actual webhook ID
            'webhook_event'     => json_decode(request()->getContent(), true)
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl."/v1/notifications/verify-webhook-signature");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: '.$accessToken
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($signatureVerification));
        $response = curl_exec($ch);
        curl_close($ch);
        \Log::error('返回结果数据:'.$response.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        $result = json_decode($response);
        if ($result->verification_status === "SUCCESS") {
            // 验签成功，处理 Webhook 事件
            \Log::error('Signature ok!'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return true;
        } else {
            // 验签失败，可能是伪造请求
            \Log::error('check sign error!'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return false;
        }
    }

    /**
     * 暂时不使用, 验签失败
     * 公钥验签
     *
     * @return bool
     */
    public function verifySignSelf() {
        // 获取PayPal Webhook事件中的数据
        $authAlgo = request()->header('paypal-auth-algo', '');
        $transmissionId = request()->header('paypal-transmission-id', '');
        $transmissionTime = request()->header('paypal-transmission-time', '');
        $certUrl = request()->header('paypal-cert-url', '');
        $transmissionSig = request()->header('paypal-transmission-sig', '');
        if (!$authAlgo || !$transmissionId || !$transmissionTime || !$certUrl || !$transmissionSig) {
            \Log::error('PayPal 数据异常:'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return false;
        }
        $webhookId = $this->webhook_id;
        // 你的Webhook ID
        $webhookEventBody = request()->getContent();
        // 下载并验证PayPal的证书
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $certUrl);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        $cert = curl_exec($ch);
//        curl_close($ch);
//        $certKey = openssl_pkey_get_public($cert);
        $certKey = file_get_contents($certUrl);
        \Log::error('返回结果数据:'.$certKey.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        // 验证证书内容是否为空
        if (!$certKey) {
            \Log::error('下载证书失败:'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return false;
        }
        // 构建签名验证字符串
        $expectedSignature = "{$transmissionId}|{$transmissionTime}|{$webhookId}|{$webhookEventBody}";
        \Log::error('返回结果数据:'.$expectedSignature.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        // 验证签名
        $verified = openssl_verify($expectedSignature, base64_decode($transmissionSig), $certKey, OPENSSL_ALGO_SHA256);
        if ($verified === 1) {
            // 验签成功
            \Log::error('返回结果数据:Signature ok!'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);

            return true;
        } elseif ($verified === 0) {
            // 验签失败
            \Log::error('Signature is invalid!'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        } else {
            // 错误
            \Log::error('Error during signature verification!'.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        }

        return false;
    }

    /**
     * 拉起支付
     *
     * @param $order
     * @param $options
     *
     */
    public function do($order, $options = []) {
        $domain = rtrim(env('APP_URL', ''), '/');
        $returnUrl = $domain.'/paymentcomplete/'.$order->id; // 同步回调地址
        $paramsData = [
            'intent'         => 'CAPTURE',
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name'                => 'MMG3',
                        'locale'                    => 'en-US',
                        'landing_page'              => 'LOGIN',
                        'user_action'               => 'PAY_NOW',
                        'return_url'                => $returnUrl,
                        'cancel_url'                => $returnUrl,
                    ]
                ],
            ],
            'purchase_units' => [
                [
                    'reference_id' => $order->order_number,
                    'custom_id'    => $order->order_number,
                    'amount'       => [
                        'currency_code' => 'USD',$order->currency,
                        'value'         => $order->actually_paid,
                    ],
                ],
            ],
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $this->apiUrl.'/v2/checkout/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($paramsData),
            CURLOPT_HTTPHEADER     => array(
                'PayPal-Request-Id: '.$order->order_number,
                'Authorization: '.$this->getAccessToken(),
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        \Log::error('返回结果数据:'.$response.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        $reesult = @json_decode($response, true);
        curl_close($curl);
        if ($reesult && !empty($reesult['id'] ) && !empty($reesult['links'] )) {
            $order->out_order_num = $reesult['id'];
            $rs = $order->save();
            if($rs) {
                foreach ($reesult['links'] as $link) {
                    if ($link['rel'] == 'payer-action') {
                        header("Location: ".$link['href']);
                        die;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 捕获订单
     * @param $order
     *
     */
    public function capturePaypalOrder($order) {
        $paypalOrderId = $order->out_order_num;
        $order_number = $order->order_number;
        if(empty($paypalOrderId) || empty($order_number ) || empty($order->is_capture) ){
            return false;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl."/v2/checkout/orders/{$paypalOrderId}/capture",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'PayPal-Request-Id: '.$order_number,
                'Authorization: '.$this->getAccessToken(),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        \Log::error('捕获结果:'.$response.'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        $resArr = @json_decode($response, true);
        if(!empty($resArr['id']) && !empty($resArr['status'] )){
            if($resArr['id'] == $paypalOrderId && $resArr['status'] == 'COMPLETED'){
                //捕获成功
                $order->is_capture = 0;
                $order->save();
            }
        }
        curl_close($curl);
    }

}
