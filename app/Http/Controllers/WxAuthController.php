<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Pay\Wechatpay;
use App\Models\Order;
use App\Models\WechatTool;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class WxAuthController extends Controller {
    //获取微信授权码
    public function getWxAuthCode(Request $request) {
        $input = $request->all();
        if (isset($input['business_url']) && !empty($input['business_url'])) {
            $code = $input['code'] ?? '';
            $url = $input['business_url'];
            $state = $input['state'] ?? '';
            $referer = $input['referer'] ?? '';
            $jumpUrl = $url."?1=1&code=".$code.'&state='.$state.'&referer='.$referer;
            header("location:".$jumpUrl);
        } else {
            echo "no";
        }
    }

    public function WechatOrder() {
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $referer = $_GET['referer'] ?? env('APP_URL');
        if (empty($code) || empty($state)) {
            throw new \Exception('invalid param');
        }
        $orderId = $state;
        $order = Order::find($orderId);
        if (empty($order)) {
            throw new \Exception('order_id not found');
        }
        $returnUrl = rtrim(env('APP_URL'), '/').'/paymentComplete/'.$order->id;
        try {
            $wechatTool = new WechatTool();
            $wechatpay = new Wechatpay();
            $openid = $wechatTool->getOpenid($code);
            $prepayid = $wechatpay->getPrepayid($order, $openid);
            $timestamp = time();
            $nonce = substr(str_shuffle('0123456789abcdefghijklnmopqrstuvwxyz'), mt_rand(0, 36 - 33), 32);
            $sign = $wechatpay->getJssdkSign($timestamp, $nonce, $prepayid);
            $prepayid = 'prepay_id='.$prepayid;
            $appid = $wechatTool::$APPID;
            $html = <<<EOF
            <script>
            function onBridgeReady() {
                WeixinJSBridge.invoke('getBrandWCPayRequest', {
                    "appId": "$appid",
                    //公众号名称，由商户传入
                    "timeStamp": "$timestamp",
                    //时间戳，自1970年以来的秒数
                    "nonceStr": "$nonce",
                    //随机串
                    "package": "$prepayid",
                    "signType": "RSA",
                    //微信签名方式：
                    "paySign": "$sign" //微信签名
                },
                function(res) {
                    if (res.err_msg == "get_brand_wcpay_request:ok") {
                        // 使用以上方式判断前端返回,微信团队郑重提示：
                        // res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
                        window.location='$returnUrl';
                    } else if (res.err_msg == "get_brand_wcpay_request:cancel" || res.err_msg == "get_brand_wcpay_request:fail") {
                        alert('支付取消');
                        window.location='$referer';
                    } else if (res.err_msg == "system:function_not_implement") {
                        window.document.write("<h1>请使用手机微信打开</h1>");
                        alert('请使用手机微信打开');
                    } else {
                        alert('支付取消');
                        window.location='$referer';
                    }
                });
            }
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
                } else if (document.attachEvent) {
                    document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
                    document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
                }
            } else {
                onBridgeReady();
            }
            </script>
            EOF;

            return $html;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // 进行错误处理
            $msg = $e->getMessage()."\n";
            if ($e->hasResponse()) {
                $msg .= $e->getResponse()->getStatusCode().' '.$e->getResponse()->getReasonPhrase()."\n";
                $msg .= $e->getResponse()->getBody();
            }

            return $msg;
        }
    }
}
