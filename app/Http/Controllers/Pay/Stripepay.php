<?php
/**
 * Stripepay.php UTF-8
 * 支付逻辑类
 *
 * @date    : 2024/7/26 15:54 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers\Pay;
class Stripepay extends Pay {
    public function createFormdata($order) {
        // TODO: Implement createFormdata() method.
    }

    public function notify() {
        // 处理 Stripe 的 webhook
        $payload = @file_get_contents('php://input');
        $sig_header = request()->header('stripe-signature');
        $event = null;
        $stripeWebhookSecret = env('STRIPE_WEBHOOK_SECRET');
        try {
            //校验签名与参数
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header,
                $stripeWebhookSecret
            );
            // 处理事件
            if ($event->type == 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object; // contains a StripePaymentIntent
                \Log::error('返回结果数据:'.json_encode([$paymentIntent]));
                $out_trade_no = $paymentIntent->metadata->order_number;
                $total_amount = $paymentIntent->amount / 100;
                $trade_no = $paymentIntent->id;
                $gmt_payment = $paymentIntent->created;
                //$currency = $paymentIntent->currency;  //usd , 需要做转换
                $dir = storage_path('log').'/stripe/'.date('Y_m/', time());
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
                }
                $logName = date("d-H");
                $logName = $dir.$logName.'.log';
                try {
                    $res = $this->handlerOrderPaySucService(
                        $out_trade_no, $logName, $total_amount, $trade_no, $gmt_payment
                    );
                } catch (\Exception $e) {
                    \Log::error('返回结果数据:'.json_encode([$e]));
                    $res = false;
                }

                return $res;
            }

            return false;
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            throw $e;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            throw $e;
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
        $apiKey = env('STRIPE_APIKEY');
        \Stripe\Stripe::setApiKey($apiKey);
        $stripe = new \Stripe\StripeClient($apiKey);
        $domain = rtrim(env('APP_URL', ''), '/');
        $returnUrl = $domain.'/paymentcomplete/'.$order->id; // 同步回调地址
        //报告名称
        $orderGoodsName = '测试报告';
        $payData = [
            'line_items'          => [
                [
                    'price_data' => [
                        'currency'     => 'usd',
                        'product_data' => [
                            'name' => $orderGoodsName,
                        ],
                        'unit_amount'  => $order->actually_paid * 100,
                    ],
                    'quantity'   => 1,
                    //'tax_rates' => ['txr_xxxxxxxx'], //  应用税率
                ]
            ],
            'mode'                => 'payment',
            'success_url'         => $returnUrl,
            'cancel_url'          => $domain,
            'payment_intent_data' => [
                'metadata' => [
                    'order_number' => $order->order_number
                ],
            ],
        ];
        $checkout_session = $stripe->checkout->sessions->create($payData);
        header("HTTP/1.1 303 See Other");
        header("Location: ".$checkout_session->url);

        return true;
    }

    public function getPayHtml($clientSecret, $stripePublishableKey, $returnUrl) {
        $html = <<<EOF
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Stripe Payment Example</title>
        <script src="https://js.stripe.com/v3/"></script>
        <style>
            /* 一些基本的样式 */
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
                max-width: 600px;
                margin: auto;
            }
            #card-element {
                border: 1px solid #ccc;
                padding: 10px;
                margin: 10px 0;
            }
            #submit {
                background-color: #007bff;
                color: #fff;
                padding: 10px 20px;
                border: none;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <h1>Stripe Payment Example</h1>
        <form id="payment-form">
            <div id="card-element">
                <!-- Stripe Elements will create input elements here -->
            </div>
            <button id="submit">Pay</button>
            <div id="card-errors" role="alert"></div>
        </form>

        <script>
            var stripe = Stripe("{$stripePublishableKey}");
            var elements = stripe.elements();
            var card = elements.create('card');
            card.mount('#card-element');

            card.on('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            var form = document.getElementById('payment-form');
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                // 禁用提交按钮
                document.getElementById('submit').disabled = true;

                // 使用 card 支付方式创建支付意图
                stripe.confirmCardPayment("{$clientSecret}", {
                    payment_method: {
                        card: card
                    }
                }).then(function(result) {
                    // 重新启用提交按钮
                    document.getElementById('submit').disabled = false;

                    if (result.error) {
                        var errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                    } else {
                        if (result.paymentIntent.status === 'succeeded') {
                            console.log('Payment successful!');
                            // 在此处理支付成功后的操作
                            window.location.href = "{$returnUrl}";
                        }
                    }
                });
            });
        </script>
    </body>
    </html>
EOF;

        return $html;
    }
}
