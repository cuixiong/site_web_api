<?php
namespace App\Http\Controllers\Pay;

use App\Http\Controllers\Common\SendEmailController;
use App\Models\CouponUser;
use App\Models\Order;
use App\Models\WechatTool;
use App\Services\OrderService;
use GuzzleHttp\Exception\RequestException;
use WechatPay\GuzzleMiddleware\WechatPayMiddleware;
use WechatPay\GuzzleMiddleware\Util\PemUtil;
use WechatPay\GuzzleMiddleware\WechatPayMiddlewareBuilder;
use CodeItNow\BarcodeBundle\Utils\QrCode;
use Exception;

class Wechatpay extends Pay
{
    private $wechatpayMiddleware = null;
    private $guzzleHttpClient = null;
    private $wechatTool = null;

    public function __construct()
    {
        parent::__construct();
        $this->wechatTool = new WechatTool();
    }

    /**
     * @param Order $order
     */
    public function do($order, $options = [])
    {
        $returnUrl = rtrim(env('APP_URL',''),'/').'/paymentcomplete/'.$order->id;
        if ($this->getOption(self::KEY_IS_WECHAT) == self::OPTION_ENABLE) {

            $wechat_type = 'native';
            if (empty($order->wechat_type)) {
                //假设进来没有记录终端方式
                $order->wechat_type = $wechat_type;
                if (!$order->save()) {
                    throw new Exception('change wechatType error');
                }
            } elseif ($order->wechat_type != $wechat_type) {
                //终端方式改变
                $order->wechat_type = $wechat_type;
                $order->order_number = date('YmdHis', time()) . mt_rand(10, 99);
                if (!$order->save()) {
                    throw new Exception('change wechatType error');
                }
            }

            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            // $redirecturi = env('APP_URL','').'/api/order/wechat-order?referer='.urlencode($referer);
            $domain = env('APP_URL' , '');
            $wxTransferDomain = env('WX_TRANSFER_DOMAIN' , 'https://www.qyresearch.com.cn');
            if($domain){
                $domain = trim($domain,'/');
            }
            $business_url = $domain.'/api/order/wechat-order';
            $redirecturi = $wxTransferDomain.'/api/wx-empower/index1?business_url='.$business_url."&referer=".$referer;
            $url = $this->wechatTool->getOAuthUrl($redirecturi, $order->id);

            $html = $this->getJump($url);
            return $html;
        }

        try {
            if ($this->getOption(self::KEY_IS_MOBILE) == self::OPTION_ENABLE) { // h5 支付

                $wechat_type = 'h5';
                if (empty($order->wechat_type)) {
                    //假设进来没有记录终端方式
                    $order->wechat_type = $wechat_type;
                    if (!$order->save()) {
                        throw new Exception('change wechatType error');
                    }
                } elseif ($order->wechat_type != $wechat_type) {
                    //终端方式改变
                    $order->wechat_type = $wechat_type;
                    $order->order_number = date('YmdHis', time()) . mt_rand(10, 99);
                    if (!$order->save()) {
                        throw new Exception('change wechatType error');
                    }
                }
                $h5Url = $this->getH5Url($order);
                $h5Url = $h5Url.'&redirect_url='. urldecode($returnUrl);
                $html = $this->getJump($h5Url);

                return $html;
            } else { // 扫码支付

                $wechat_type = 'pc';
                if (empty($order->wechat_type)) {
                    //假设进来没有记录终端方式
                    $order->wechat_type = $wechat_type;
                    if (!$order->save()) {
                        throw new Exception('change wechatType error');
                    }
                } elseif ($order->wechat_type != $wechat_type) {
                    //终端方式改变
                    $order->wechat_type = $wechat_type;
                    $order->order_number = date('YmdHis', time()) . mt_rand(10, 99);
                    if (!$order->save()) {
                        throw new Exception('change wechatType error');
                    }
                }

                $codeUrl = $this->getNativeUrl($order);

                $qrCode = new QrCode();
                $qrCode->setText($codeUrl)
                    ->setSize(200)
                    ->setPadding(10)
                    ->setErrorCorrection(QrCode::LEVEL_HIGH)
                    ->setImageType(QrCode::IMAGE_TYPE_PNG);

                $orderNumber = $order->order_number;
                $orderAmount = $order->actually_paid;
                //$orderCreateAt = date('Y-m-d H:i:s', $order->created_at->timestamp);
                $orderData = $order->toArray();
                $orderCreateAt = $orderData['created_at'] ?? '';

                $merchantName = env('WECHATPAY_MERCHANT_NAME','');
                $qrcodeUrl = $qrCode->getDataUri();
                $orderPaySuccess = Order::PAY_SUCCESS;
                $actionUrl = rtrim(env('APP_URL'),'/').'/api/order/details';
                $html = <<<EOF
                <!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="UTF-8">
                        <title>WeChat Pay</title>
                        <link rel="shortcut icon" href="https://admin.globalinforesearch.com.cn/images/wechatpay/logo.ico" />
                        <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1,user-scalable=no">
                        <meta name="format-detection" content="telephone=no" />
                        <meta http-equiv="Cache-Control" content="no-transform,no-siteapp">
                        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                        <style>
                            .main {
                                padding-top: 10px;
                                display: grid;
                                grid-template-columns: 220px;
                                grid-template-columns: auto auto;
                                grid-template-rows: auto auto;
                                grid-row-gap: 5px;
                                grid-column-gap: 50px;
                                justify-items: start;
                                justify-content: center;
                            }
                            .wechatpay {
                                width: 220px;
                            }
                            .logo {
                                grid-column-start: 1;
                                grid-column-end: 3;
                            }
                            .detail {
                                display: grid;
                                grid-template-columns: 68px auto;
                                grid-template-rows: 70px 35px 35px 35px;
                                grid-row-gap: 10px;
                                grid-column-gap: 10px;
                                align-content: center;
                            }
                            .detail .amount {
                                justify-self: center;
                                align-self: center;
                                grid-column-start: 1;
                                grid-column-end: 3;
                            }
                            .detail .amount sup {
                                font-size: 30px;
                                font-weight: lighter;
                            }
                            .detail .amount strong {
                                font-size: 50px;
                            }
                            .qrcode {
                                justify-self: center;
                                align-self: center;
                            }
                            @media screen and (max-width: 750px) {
                                .main {
                                    grid-template-columns: 240px;
                                    grid-template-rows: auto auto auto;
                                }
                                .logo {
                                    grid-column-end: 2;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="main">
                            <div class="logo"><img class="wechatpay" src="https://admin.globalinforesearch.com.cn/images/wechatpay/logo.webp"></div><!-- 微信支付 logo -->
                            <div class="detail">
                                <span class="amount"><strong><sup>￥</sup>$orderAmount</strong></span>
                                <span>商户全称: </span><span>$merchantName</span>
                                <span>订单编号: </span><span>$orderNumber</span>
                                <span>下单时间: </span><span>$orderCreateAt</span>
                            </div>
                            <div class="qrcode">
                                <div><img class="wechatpay" src="$qrcodeUrl"></div><!-- 微信支付 二维码 -->
                                <div><img class="wechatpay" src="https://admin.globalinforesearch.com.cn/images/wechatpay/instructions.webp"></div><!-- 微信支付 二维码说明 -->
                            </div>
                        </div>
                    </body>
                    <script>
                        var maxAjax = 100; // 轮询次数的上限
                        var flg = false;
                        window.onload = function() {
                            console.log("页面加载完成！");
                            setTimeout(function(){
                                var timer = setInterval(function(){
                                    if (maxAjax <= 0 && flg == false) {
                                        flg = true;
                                        clearTimeout(timer);
                                        // alert('支付超时');
                                        // history.back();
                                        if (confirm("是否已完成支付") == true) {
                                            window.location='$returnUrl';
                                        } else {
                                            history.back();
                                        }
                                    }
                                    var xhr = new XMLHttpRequest();
                                    xhr.open('POST', '$actionUrl', true);
                                    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                                    xhr.onreadystatechange = function () {
                                        if (xhr.readyState == 4) {
                                            console.log(xhr);
                                            maxAjax--;
                                            if (xhr.status == 200) {
                                                console.log(xhr.responseText);
                                                obj = JSON.parse(xhr.responseText);
                                                console.log(obj);
                                                if (!'code' in obj || obj.code != 200 || !'data' in obj) {
                                                    return;
                                                }
                                                if (!'is_pay' in obj.data || obj.data.is_pay != $orderPaySuccess) {
                                                    return;
                                                }
                                                window.location='$returnUrl';
                                                flg = true;
                                            }
                                        }
                                    }
                                    xhr.send('order_id=$order->id');
                                    if (flg == true) {
                                        clearTimeout(timer);
                                    }
                                }, 3000); // 每 3 秒轮询一次
                            }, 5000); // 等待 5 秒后开始轮询支付结果
                        }
                    </script>
                </html>
                EOF;
                return $html;
            }
        } catch (RequestException $e) {
            // 进行错误处理
            $msg = $e->getMessage()."\n";
            if ($e->hasResponse()) {
                $msg .= $e->getResponse()->getStatusCode().' '.$e->getResponse()->getReasonPhrase()."\n";
                $msg .= $e->getResponse()->getBody();
            }
            throw new \Exception($msg);
        }
    }

    /**
     * @param Order $order
     * @return array
     */
    public function createFormdata($order)
    {
    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
    }

    public function notify()
    {
        $input = file_get_contents("php://input");
        $input = json_decode($input, true);
        if (!is_array($input)) {
            // 抛异常
            throw new Exception('JSON interpretation error');
        }
        $timestamp = time();
        $dir = storage_path('log').'/wechatpay/'.date('Y_m/', $timestamp);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
        }
        $logName = $input['id'] ?? 'create_time_'.$timestamp;
        $logName = $dir.$logName.'.log';
        $_DATA = count($_POST) == 0 ? file_get_contents("php://input") : $_POST;
        $log = sprintf("%s", var_export([
                        '_TIME' => date('Y-m-d H:i:s', $timestamp),
                        '_IP' => request()->ip(),
                        '_DATA' => $_DATA,
                        '_GET' => $_GET,
                    ], true)).PHP_EOL;
        file_put_contents($logName, $log, FILE_APPEND);

        if (!isset($input['resource'])) {
            // 抛异常
            throw new Exception('resource not found');
        }
        $resourceJson = $input['resource'];
        if (!isset($resourceJson['associated_data']) ||
            !isset($resourceJson['nonce']) ||
            !isset($resourceJson['ciphertext'])) {
            // 抛异常
            throw new Exception('resource Key value not found');
        }

        $associatedData = $resourceJson['associated_data'];
        $nonceStr = $resourceJson['nonce'];
        $ciphertext = $resourceJson['ciphertext'];
        $aesKey = env('WECHATPAY_APIV3_SECRET_KEY');
        $aesUtil = new \WechatPay\GuzzleMiddleware\Util\AesUtil($aesKey);
        $resource = $aesUtil->decryptToString($associatedData, $nonceStr, $ciphertext);
        if ($resource === false) {
            // 抛异常
            $msg = 'Decryption failed';
            file_put_contents($logName, $msg.PHP_EOL, FILE_APPEND);
            throw new Exception($msg);
        }
        $resource = json_decode($resource, true);
        if (!is_array($resource)) {
            // 抛异常
            $msg = 'resource interpretation error';
            file_put_contents($logName, var_export([
                    'msg' => $msg,
                    'resource' => $resource,
                    'json_error_msg' => json_last_error_msg(),
                    'json_error' => json_last_error(),
                ], true).PHP_EOL, FILE_APPEND);
            throw new Exception($msg);
        }
        if (!isset($resource['transaction_id']) ||
            !isset($resource['out_trade_no']) ||
            !isset($resource['trade_state'])) {
            // 抛异常
            throw new Exception('resource Key value not found');
        }
        file_put_contents($logName, var_export($resource, true).PHP_EOL, FILE_APPEND);
        $transaction_id = $resource['transaction_id'];
        $out_trade_no = $resource['out_trade_no'];
        $trade_state = $resource['trade_state'];

        if ($trade_state != 'SUCCESS') { // 支付不成功
            // 记下日志，提前返回
            file_put_contents($logName, 'Payment failed'.PHP_EOL, FILE_APPEND);
            return ['code' => 'SUCCESS', 'message' => ''];
        }

        if (!isset($resource['success_time'])) {
            // 记下错误，但不抛异常
            file_put_contents($logName, 'success_time not found'.PHP_EOL, FILE_APPEND);
            $success_time = time();
        } else {
            $success_time = $resource['success_time'];
            $success_time = strtotime($success_time);
            if ($success_time === false) {
                // 记下错误，但不抛异常
                file_put_contents($logName, 'success_time format error'.PHP_EOL, FILE_APPEND);
                $success_time = time();
            }
        }

        $order = Order::where(['order_number' => $out_trade_no])->first();
        if (!$order) { // 订单号不存在
            // 记下日志，提前返回
            $msg = 'order number not found';
            file_put_contents($logName, $msg.PHP_EOL, FILE_APPEND);
            throw new Exception($msg);
        }

        if($order->is_pay == Order::PAY_SUCCESS){
            return ['code' => 'SUCCESS', 'message' => ''];
        }

        // 改变订单状态
        $order->out_order_num = $transaction_id;
        $order->is_pay = Order::PAY_SUCCESS;
        $order->pay_time = $success_time;
        $order->updated_at = time();
        if ($order->save()) {
            //处理未注册用户的优惠券业务
            (new OrderService())->handlerPayCouponUser($order->id);
            (new SendEmailController())->payment($order->id);
            // Order::sendPaymentEmail($order); // 发送已付款的邮件
        } else { // 订单状态更新失败
            $msg = 'order status update failed '.$order->getModelError();
            file_put_contents($logName, $msg.PHP_EOL, FILE_APPEND);
            throw new Exception($msg);
        }
        file_put_contents($logName, 'success'.PHP_EOL, FILE_APPEND);

        return ['code' => 'SUCCESS', 'message' => ''];
    }

    public function getWechatPayMiddleware()
    {
        if ($this->wechatpayMiddleware === null) {
            // 商户相关配置
            $merchantId = env('WECHATPAY_MERCHANT_ID',''); // 商户号
            $merchantSerialNumber = env('WECHATPAY_MERCHANT_SERIAL_NUMBER',''); // 商户API证书序列号
            $merchantPrivateKey = PemUtil::loadPrivateKey(base_path().env('WECHATPAY_MERCHANT_PRIVATE_KEY','')); // 商户私钥
            // 微信支付平台配置
            $wechatpayCertificate = $this->getWechatpayCertificate();

            // 构造一个WechatPayMiddleware
            // $wechatpayMiddleware = WechatPayMiddleware::builder()
            $wechatpayMiddleware = WechatPayMiddleware::builder()
                ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey) // 传入商户相关配置
                ->withWechatPay($wechatpayCertificate); // 可传入多个微信支付平台证书，参数类型为array

            $this->wechatpayMiddleware = $wechatpayMiddleware;
        }

        return $this->wechatpayMiddleware;
    }

    public function getGuzzleHttpClient()
    {
        if ($this->guzzleHttpClient === null) {
            $wechatpayMiddleware = $this->getWechatPayMiddleware();

            // 将WechatPayMiddleware添加到Guzzle的HandlerStack中
            $stack = \GuzzleHttp\HandlerStack::create();
            $stack->push($wechatpayMiddleware->build(), 'wechatpay');

            // 创建Guzzle HTTP Client时，将HandlerStack传入
            $client = new \GuzzleHttp\Client(['handler' => $stack]);

            $this->guzzleHttpClient = $client;
        }

        return $this->guzzleHttpClient;
    }

    /**
     * @param WechatPayMiddlewareBuilder $builder
     * @return self
     */
    public function setWechatPayMiddleware($builder)
    {
        $this->wechatpayMiddleware = $builder;
        return $this;
    }

    /**
     * @param \GuzzleHttp\Client $client
     * @return $this
     */
    public function setGuzzleHttpClient($client)
    {
        $this->guzzleHttpClient = $client;
        return $this;
    }

    /**
     * @return array
     */
    public function getWechatpayCertificate()
    {
        $folder = base_path().env('WECHATPAY_CERTIFICATE_FOLDER','');
        $certArr = [];
        foreach (glob($folder.'/*.pem') as $item) {
            if (is_file($item)) {
                $certArr[] = PemUtil::loadCertificate($item); // 微信支付平台证书
            }
        }

        return $certArr;
    }

    public function getJump($url)
    {
        $html = <<<EOF
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="refresh" content="1;url=$url" />
        </head>
        <body>
        </body>
        </html>
        EOF;

        return $html;
    }

    public function getNativeUrl($order)
    {
        $client = $this->getGuzzleHttpClient();

        $json = [
            'appid' => $this->wechatTool::$APPID,
            'mchid' => $this->wechatTool::$MERCHANT_ID, // 商户号
            'description' => $this->wechatTool::$DESCRIPTION,
            'out_trade_no' => $order->order_number,
            'notify_url' => rtrim(env('APP_URL',''),'/').'/api/notify/wechatpay',
            'amount' => [
                'total' => intval(bcmul($order->actually_paid, 100)),// 单位为分
                'currency' => 'CNY',
            ],
        ];

        $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/pay/transactions/native', [
            'json' => $json, // JSON请求体
            'headers' => ['Accept' => 'application/json']
        ]);

        $body = $resp->getBody();
        $body = json_decode($body, true);
        if (!isset($body['code_url'])) {
            throw new \Exception('code_url not found');
        }

        return $body['code_url'];
    }

    public function getH5Url($order)
    {
        $client = $this->getGuzzleHttpClient();

        $json = [
            'appid' => $this->wechatTool::$APPID,
            'mchid' => $this->wechatTool::$MERCHANT_ID, // 商户号
            'description' => $this->wechatTool::$DESCRIPTION,
            'out_trade_no' => $order->order_number,
            'notify_url' => rtrim(env('APP_URL',''),'/').'/api/notify/wechatpay',
            'amount' => [
                'total' => intval(bcmul($order->actually_paid, 100)),// 单位为分
                'currency' => 'CNY',
            ],
            'scene_info' => [
                'payer_client_ip' => request()->ip(),
                'h5_info' => [
                    'type' => 'Wap',
                ]
            ]
        ];

        $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5', [
            'json' => $json, // JSON请求体
            'headers' => ['Accept' => 'application/json']
        ]);

        $body = $resp->getBody();
        $body = json_decode($body, true);
        if (!isset($body['h5_url'])) {
            throw new \Exception('h5_url not found');
        }

        return $body['h5_url'];
    }

    /**
     * @param Order $order
     * @param string $openid
     * @return string
     */
    public function getPrepayid($order, $openid)
    {
        $client = $this->getGuzzleHttpClient();

        $json = [
            'appid' => $this->wechatTool::$APPID,
            'mchid' => $this->wechatTool::$MERCHANT_ID, // 商户号
            'description' => $this->wechatTool::$DESCRIPTION,
            'out_trade_no' => $order->order_number,
            'notify_url' => rtrim(env('APP_URL',''),'/').'/api/notify/wechatpay',
            'amount' => [
                'total' => intval(bcmul($order->actually_paid, 100)),// 单位为分
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $openid,
            ],
        ];

        $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi', [
            'json' => $json, // JSON请求体
            'headers' => ['Accept' => 'application/json']
        ]);

        $body = $resp->getBody();
        $body = json_decode($body, true);
        if (!isset($body['prepay_id'])) {
            throw new \Exception('prepay_id not found');
        }

        return $body['prepay_id'];
    }

    /**
     * @param int $timestamp
     * @param string $nonce
     * @param string $prepayid
     * @return string
     */
    public function getJssdkSign($timestamp, $nonce, $prepayid)
    {
        $appid = $this->wechatTool::$APPID;

        $prepay_id = 'prepay_id='.$prepayid;
        $data = $appid."\n".$timestamp."\n".$nonce."\n".$prepay_id."\n";
        $binary_signature = "";
        $algo = "SHA256";
        $prikey = file_get_contents($this->wechatTool::$MERCHANT_PRIVATE_KEY);
        openssl_sign($data, $binary_signature, $prikey, $algo);
        $base64_signature = base64_encode($binary_signature);

        return $base64_signature;
    }


    /**
     * @param Order $order
     */
    public function queryOrder($order, $options = [])
    {
        $client = $this->getGuzzleHttpClient();

        $query = [
            'mchid' => $this->wechatTool::$MERCHANT_ID, // 商户号
        ];
        $resp = $client->request('GET', 'https://api.mch.weixin.qq.com/v3/pay/transactions/out-trade-no/'.$order->order_number, [
            'query' => $query, // JSON请求体
            'headers' => ['Accept' => 'application/json']
        ]);
        // return $resp;
        $body = $resp->getBody();
        $body = json_decode($body, true);
        return $body;
    }

    /**
    * 获取签名
    */
    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }
    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
