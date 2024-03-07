<?php

namespace App\Http\Controllers\Pay;

use backend\models\Order;
use backend\models\CouponUser;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use Alipay\EasySDK\Kernel\Config;
use App\Models\Payment;
use Exception;
use App\Http\Controllers\Pay\Pay;

class Alipay extends Pay
{
    public string $returnUrl;
    public string $notifyUrl;

    public function __construct()
    {
        parent::__construct();
        $payment = Payment::find(1);// 查了个寂寞

        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = env('ALIPAY_GETWAY_HOST','https://openapi.alipaydev.com/gateway.do');
        $options->signType = 'RSA2';

        $options->appId = env('ALIPAY_APPID','');
        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = file_get_contents(env('ALIPAY_PRIVATEKEY',''));
        // 支付宝公钥证书文件路径
        $options->alipayCertPath = env('ALIPAY_CERT','');
        // 支付宝根证书文件路径
        $options->alipayRootCertPath = env('ALIPAY_ROOT_CERT','');
        // 应用公钥证书文件路径
        $options->merchantCertPath = env('ALIPAY_MERCHANT_CERT','');
        // 异步通知接收服务地址
        $options->notifyUrl = env('APP_URL','') . '/notify/alipay';
        // exit;
        Factory::setOptions($options);
    }

    /**
     * @param Order $order
     */
    public function do($order, $options = [])
    {
        try {
            $subject = env('ALIPAY_SUBJECT', '商品名称'); // 商品名称
            $outTradeNo = $order->order_number; // 外部订单号
            $totalAmount = $order->actually_paid; // 交易金额
            $returnUrl = env('APP_URL','') . '/paymentcomplete/' . $order->id; // 同步回调地址
            if ($this->getOption(self::KEY_IS_MOBILE) == self::OPTION_ENABLE) {
                $quitUrl = env('APP_URL','');
                $result = Factory::payment()->Wap()->pay($subject, $outTradeNo, $totalAmount, $quitUrl, $returnUrl);
            } else {
                $result = Factory::payment()->page()->pay($subject, $outTradeNo, $totalAmount, $returnUrl);
            }
            $responseChecker = new ResponseChecker();
            // 处理响应或异常
            if ($responseChecker->success($result)) {
                // echo "调用成功". PHP_EOL;
                return $result->body;
            } else {
                // echo "调用失败，原因：". $result->msg."，".$result->subMsg.PHP_EOL;
                throw new Exception($result->msg . "，" . $result->subMsg, 1);
            }
        } catch (\Exception $e) {
            // echo "调用失败，". $e->getMessage(). PHP_EOL;;
            throw $e;
        }
    }

    
    /**
     * @param Order $order
     */
    public function queryOrder($order, $options = [])
    {
        try {
            $outTradeNo = $order->order_number;
            $result = Factory::payment()->common()->query($outTradeNo);
            $responseChecker = new ResponseChecker();
            // 处理响应或异常
            if ($responseChecker->success($result)) {
                // echo "调用成功". PHP_EOL;
                return json_decode($result->httpBody, true)['alipay_trade_query_response'];
            } else {
                // echo "调用失败，原因：". $result->msg."，".$result->subMsg.PHP_EOL;
                throw new Exception($result->msg . "，" . $result->subMsg, 1);
            }
        } catch (\Exception $e) {
            // echo "调用失败，". $e->getMessage(). PHP_EOL;;
            throw $e;
        }
    }

    /**
     * @param Order $order
     */
    public function refund($order, $options = [])
    {
        try {
            $refundAmount = $order->actually_paid;
            $outTradeNo = $order->order_number;
            $trade_no = $order->out_order_num;
            $result = Factory::payment()->common()->refund($outTradeNo, $refundAmount);
            
            $responseChecker = new ResponseChecker();
            // 处理响应或异常
            if ($responseChecker->success($result)) {
                // echo "调用成功". PHP_EOL;
                return $result->httpBody;
            } else {
                // echo "调用失败，原因：". $result->msg."，".$result->subMsg.PHP_EOL;
                throw new Exception($result->msg . "，" . $result->subMsg, 1);
            }
        } catch (\Exception $e) {
            // echo "调用失败，". $e->getMessage(). PHP_EOL;;
            throw $e;
        }
    }

    /**
     * @param Order $order
     */
    public function refund_query($order, $options = [])
    {
        try {
            $outTradeNo = $order->order_number;
            $trade_no = $order->out_order_num;  //外部订单号
            $result = Factory::payment()->common()->queryRefund($outTradeNo, $outTradeNo);
            
            $responseChecker = new ResponseChecker();
            // 处理响应或异常
            if ($responseChecker->success($result)) {
                // echo "调用成功". PHP_EOL;
                return $result->httpBody;
            } else {
                // echo "调用失败，原因：". $result->msg."，".$result->subMsg.PHP_EOL;
                throw new Exception($result->msg . "，" . $result->subMsg, 1);
            }
        } catch (\Exception $e) {
            // echo "调用失败，". $e->getMessage(). PHP_EOL;;
            throw $e;
        }
    }

    /**
     * @param Order $order
     * @return array
     */
    public function createFormdata($order)
    {
        return [];
    }

    public function notify()
    {
        if (!Factory::payment()->common()->verifyNotify($_POST)) {
            throw new \Exception('验签失败');
        }

        // trade_no	支付宝交易号
        // out_trade_no	商户订单号
        // trade_status	交易状态	String(32)	否	交易目前所处的状态，见下文 交易状态说明	TRADE_CLOSED
        // total_amount	订单金额    本次交易支付的订单金额，单位为人民币（元），精确到小数点后2位	20.00
        // gmt_payment	交易付款时间 该笔交易的买家付款时间。格式为yyyy-MM-dd HH:mm:ss	2015-04-27 15:45:57

        $trade_no = $_POST['trade_no'];
        $out_trade_no = $_POST['out_trade_no'];
        $trade_status = $_POST['trade_status'];
        $total_amount = $_POST['total_amount'];
        $gmt_payment = $_POST['gmt_payment'];
        if (
            empty($trade_no) ||
            empty($out_trade_no) ||
            empty($trade_status) ||
            empty($total_amount) ||
            empty($gmt_payment)
        ) {
            $e = sprintf("%s", var_export($_POST, true));
            throw new \Exception('缺失关键参数 ' . $e);
        }

        $timestamp = time();
        $gmt_payment = strtotime($gmt_payment);
        if ($gmt_payment === false) {
            $gmt_payment = $timestamp;
        }
        $paymentMsg = '';
        $trade_no = trim($trade_no);
        $dir = $this->logdir . '/alipay/' . date('Y_m/', time());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
        }
        $logName = empty($trade_no) ? $timestamp : $trade_no;
        $logName = $dir . $logName . '.log';
        $_DATA = count($_POST) == 0 ? file_get_contents("php://input") : $_POST;
        $log = sprintf("%s", var_export([
            '_TIME' => date('Y-m-d H:i:s', $timestamp),
            '_IP' => Yii::$app->request->userIP,
            '_DATA' => $_DATA,
            '_GET' => $_GET,
        ], true)) . PHP_EOL;
        file_put_contents($logName, $log, FILE_APPEND);

        $order = Order::findOne(['order_number' => $out_trade_no]);
        if (!$order) {
            $paymentMsg .= 'order_number is invalid' . PHP_EOL;
            file_put_contents($logName, $paymentMsg, FILE_APPEND);
            return;
        }
        file_put_contents($logName, print_r($order->getAttributes(), true), FILE_APPEND);

        if ($order['is_pay'] == Order::PAY_SUCCESS) {
            $paymentMsg .= 'order has payment received' . PHP_EOL;
            // header("Location: $this->returnUrl");
            file_put_contents($logName, $paymentMsg, FILE_APPEND);
            return;
        }

        // 检查支付的金额是否相符
        if ($order['actually_paid'] != $total_amount) { // 网站付款时的币种的订单总金额
            $paymentMsg .= 'fail amount is wrong' . PHP_EOL;
            file_put_contents($logName, $paymentMsg, FILE_APPEND);
            return;
        }

        if ($trade_status == 'TRADE_SUCCESS') {
            $paymentMsg .= 'payment success' . PHP_EOL;
            // 改变订单状态
            $order->out_order_num = $trade_no;
            $order->is_pay = Order::PAY_SUCCESS;
            $order->pay_time = $gmt_payment; // x_fp_timestamp 其实是订单创建的时间，回调的参数里没有具体的支付时间
            $order->updated_at = time();
            if ($order->save()) {
                if (!empty($order->coupon_id)) { // 如果这个订单有使用优惠券
                    $CouponUser = CouponUser::find()->where(['user_id' => $order->user_id, 'coupon_id' => $order->coupon_id])->one();
                    $CouponUser->is_used = 2;  // 改变该优惠券的使用状态为“已使用”
                    $CouponUser->usage_time = time();
                    $CouponUser->save(false);
                }
                Order::sendPaymentEmail($order); // 发送已付款的邮件
                $paymentMsg .= 'success to update status' . PHP_EOL;
            } else {
                $paymentMsg .= 'fail to update status' . PHP_EOL;
                $paymentMsg .= print_r($order->getErrors(), true) . PHP_EOL;
            }
            $paymentMsg .= print_r($order->getAttributes(), true);
            file_put_contents($logName, $paymentMsg, FILE_APPEND);
            return 'success';
        }
    }
}
