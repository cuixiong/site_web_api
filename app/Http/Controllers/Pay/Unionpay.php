<?php

namespace App\Http\Controllers\Pay;
use backend\models\Order;
use backend\models\Payment;
use backend\models\CouponUser;
use backend\models\Product;
use backend\models\OrderGoods;

class Unionpay extends Pay
{
    public string $returnUrl;
    public string $notifyUrl;

    public function __construct()
    {
        parent::__construct();
        $payment = Payment::find()->where(['code'=>'CREDITCARD'])->one();
    }

    /**
     * @param Order $order
     */
    // public function do($order, $options = [])
    public function do($order, $options = [])
    {
        $product_names = Product::find()->alias('p')->select('name')
            ->leftJoin(OrderGoods::tableName() . ' as og', 'og.goods_id = p.id')
            ->where(['og.order_id' => $order->id])
            ->asArray()
            ->column();
        if (!empty($product_names) && count($product_names) > 0) {
            $product_names = implode("\n", $product_names);
            $product_names = mb_strlen($product_names, 'utf8') > 80 ? mb_substr($product_names, 0, 80, 'utf8') . '...' : $product_names;
            // $product_names = substr($product_names,0,strlen($product_names)>100?100:strlen($product_names));
            $product_names = '{commodityName='.$product_names.'}';
        }

        try {
            include(Yii::getAlias('@common') . '/lib/unionPaySDK/acp_service.php');
            $time = date('YmdHis', time());
            // return $time;
            $params = array(
                //以下信息非特殊情况不需要改动
                'version' => \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->version,                 //版本号
                'encoding' => 'utf-8',                  //编码方式
                'txnType' => '01',                      //交易类型
                'txnSubType' => '01',                  //交易子类
                'bizType' => '000201',                  //业务类型
                'frontUrl' =>  Yii::$app->params['frontend_domain'] . '/paymentcomplete/' . $order->id,  //前台通知地址
                'backUrl' => Yii::$app->params['api_domain'] . '/notify/unionpay',      //后台通知地址
                'signMethod' => \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->signMethod,                  //签名方法
                'channelType' => '08',                  //渠道类型，07-PC，08-手机
                'accessType' => '0',                  //接入类型
                'currencyCode' => '156',              //交易币种，境内商户固定156

                // //TODO 以下信息需要填写
                // 'merId' => $_POST["merId"],		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
                // 'orderId' => $_POST["orderId"],	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
                // 'txnTime' => $_POST["txnTime"],	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
                // 'txnAmt' => $_POST["txnAmt"],	//交易金额，单位分，此处默认取demo演示页面传递的参数

                'merId' => Yii::$app->params['union_merId'],        //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
                'orderId' => $order->order_number,    //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
                'txnTime' => $time,    //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
                'txnAmt' => ($order->actually_paid) * 100,    //交易金额，单位分，此处默认取demo演示页面传递的参数

                // 订单超时时间。
                // 超过此时间后，除网银交易外，其他交易银联系统会拒绝受理，提示超时。 跳转银行网银交易如果超时后交易成功，会自动退款，大约5个工作日金额返还到持卡人账户。
                // 此时间建议取支付时的北京时间加15分钟。
                // 超过超时时间调查询接口应答origRespCode不是A6或者00的就可以判断为失败。
                'payTimeout' => date('YmdHis', strtotime('+15 minutes')),

                'riskRateInfo' => $product_names,

                // 请求方保留域，
                // 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
                // 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
                // 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
                //    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
                // 2. 内容可能出现&={}[]"'符号时：
                // 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
                // 2) 如果对账文件没有显示要求，可做一下base64（如下）。
                //    注意控制数据长度，实际传输的数据长度不能超过1024位。
                //    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
                //    'reqReserved' => base64_encode('任意格式的信息都可以'),

                //TODO 其他特殊用法请查看 special_use_purchase.php
            );

            \com\unionpay\acp\sdk\AcpService::sign($params);
            $uri = \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->frontTransUrl;
            $html_form = \com\unionpay\acp\sdk\AcpService::createAutoFormHtml($params, $uri);
            return $html_form;
        } catch (\Exception $e) {
            // echo "调用失败，". $e->getMessage(). PHP_EOL;
            throw $e;
        }
    }


    /**
     * @param Order $order
     */
    // public function queryOrder($order, $options = [])
    // {
    //     try {
    //         $outTradeNo = $order->order_number;
    //         $result = Factory::payment()->common()->query($outTradeNo);
    //         $responseChecker = new ResponseChecker();
    //         // 处理响应或异常
    //         if ($responseChecker->success($result)) {
    //             // echo "调用成功". PHP_EOL;
    //             return json_decode($result->httpBody, true)['alipay_trade_query_response'];
    //         } else {
    //             // echo "调用失败，原因：". $result->msg."，".$result->subMsg.PHP_EOL;
    //             throw new Exception($result->msg . "，" . $result->subMsg, 1);
    //         }
    //     } catch (\Exception $e) {
    //         // echo "调用失败，". $e->getMessage(). PHP_EOL;;
    //         throw $e;
    //     }
    // }

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
        include(Yii::getAlias('@common') . '/lib/unionPaySDK/acp_service.php');

        $trade_no = $_POST['orderId'];
        $out_trade_no = $_POST['queryId'];
        $trade_status = $_POST['respCode'];
        $total_amount = $_POST['settleAmt'];
        $gmt_payment = $_POST['txnTime'];
        $signature = $_POST['signature'];
        if (
            empty($trade_no) ||
            empty($out_trade_no) ||
            empty($trade_status) ||
            empty($total_amount) ||
            empty($gmt_payment) ||
            empty($signature)
        ) {
            $e = sprintf("%s", var_export($_POST, true));
            throw new \Exception('缺失关键参数 ' . $e);
        }
        // //验签

        // if (\com\unionpay\acp\sdk\AcpService::validate($_POST)) {
        // } else {
        //     throw new \Exception('签名验证失败');
        // }
        $timestamp = time();
        $gmt_payment = strtotime($gmt_payment);
        if ($gmt_payment === false) {
            $gmt_payment = $timestamp;
        }
        $paymentMsg = '';
        $trade_no = trim($trade_no);
        $dir = $this->logdir . '/unionpay/' . date('Y_m/', time());
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
        $order = Order::findOne(['order_number' => $trade_no]);
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
        if ($order['actually_paid'] * 100 != $total_amount) { // 网站付款时的币种的订单总金额
            $paymentMsg .= 'fail amount is wrong' . PHP_EOL;
            file_put_contents($logName, $paymentMsg, FILE_APPEND);
            return;
        }

        if ($trade_status == '00') {
            //这里应该主动查询银联那边的订单结果
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
