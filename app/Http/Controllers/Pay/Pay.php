<?php

namespace App\Http\Controllers\Pay;

use App\Http\Controllers\Common\SendEmailController;
use App\Models\Order;
use App\Models\Pay as ModelsPay;
use App\Services\OrderService;

abstract class Pay implements PayInterface {
    public $logdir;
    public $actionUrl;
    public $type    = PayForm::AS_ARRAY;
    public $options = [];
    const KEY_IS_WECHAT    = 'is_wechat';
    const KEY_IS_MOBILE    = 'is_mobile';
    const OPTION_ENABLE    = 1;
    const OPTION_DISENABLE = 2;

    public function __construct() {
        $this->logdir = base_path().'/_pay_log_';
    }

    /**
     * {@inheritdoc}
     */
    public function do($order, $options = []) {
        $payForm = new PayForm($this->actionUrl, $this->createFormdata($order));
        switch ($this->type) {
            case PayForm::AS_ARRAY:
                $ret = $payForm->asArray();
                break;
            case PayForm::AS_LINK:
                $ret = $payForm->asLink();
                break;
            case PayForm::AS_AUTO:
                $ret = $payForm->asAutoPost();
                break;
            default:
                $ret = $payForm->asArray();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function queryOrder($order, $options = []) {
    }

    /**
     * {@inheritdoc}
     */
    public function refund($order, $options = []) {
    }

    /**
     * {@inheritdoc}
     */
    public function refund_query($order, $options = []) {
    }

    /**
     * {@inheritdoc}
     */
    abstract public function createFormdata($order);

    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    public function getActionUrl() {
        return $this->actionUrl;
    }

    public function logger($oid, $data, $dir) {
        $log = sprintf(
            "%s", var_export(
                    array_merge([
                                    '_POST'   => $_POST,
                                    '_GET'    => $_GET,
                                    '_SERVER' => $_SERVER,
                                    '_input'  => file_get_contents(
                                        "php://input"
                                    ),
                                ],
                                $data),
                    true
                )
        );
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
        }
        file_put_contents($dir.$oid.'.log', $log, FILE_APPEND);
    }

    /**
     * @param array $options
     *
     * @return self
     */
    public function setOptions($options) {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function setOption($key, $value) {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getOption($key) {
        return $this->options[$key] ?? null;
    }

    /**
     *
     * @param string $trade_no 交易订单号
     * @param Order  $order    订单对象
     * @param string $logName
     *
     */
    public function handlerPaySucNotify(string $trade_no, $order, string $logName
    ) {
        $paymentMsg = 'payment success'.PHP_EOL;
        // 改变订单状态
        $order->out_order_num = $trade_no;
        $order->is_pay = Order::PAY_SUCCESS;
        $order->pay_time = time(); // x_fp_timestamp 其实是订单创建的时间，回调的参数里没有具体的支付时间
        $order->updated_at = time();
        if ($order->save()) {
            //处理未注册用户的优惠券业务
            (new OrderService())->handlerPayCouponUser($order->id);
            (new SendEmailController())->payment($order->id);
            // Order::sendPaymentEmail($order); // 发送已付款的邮件
            $paymentMsg .= 'success to update status'.PHP_EOL;
        } else {
            $paymentMsg .= 'fail to update status'.PHP_EOL;
            $paymentMsg .= print_r($order->getErrors(), true).PHP_EOL;
        }
        $paymentMsg .= print_r($order->getAttributes(), true);
        file_put_contents($logName, $paymentMsg, FILE_APPEND);
    }

    /**
     *
     * @param mixed  $out_trade_no 订单号
     * @param string $logName      日志文件名
     * @param mixed  $total_amount 支付金额
     * @param string $trade_no     支付订单号
     * @param mixed  $gmt_payment  支付时间
     *
     * @return string|void
     */
    public function handlerOrderPaySucService(
        $out_trade_no, $logName, $total_amount, $trade_no, $gmt_payment
    ) {
        $paymentMsg = 'payment success'.PHP_EOL;
        $order = Order::where('order_number', $out_trade_no)->first();
        if (!$order) {
            $paymentMsg .= 'order_number is invalid'.PHP_EOL;
            file_put_contents($logName, $paymentMsg, FILE_APPEND);

            return false;
        }
        file_put_contents($logName, print_r($order->getAttributes(), true), FILE_APPEND);
        if ($order['is_pay'] == Order::PAY_SUCCESS) {
            $paymentMsg .= 'order has payment received'.PHP_EOL;
            // header("Location: $this->returnUrl");
            file_put_contents($logName, $paymentMsg, FILE_APPEND);

            return false;
        }
        
        // 日元支付获取汇率
        $rate = 1;
        $paymentModel = ModelsPay::query()->where("code", $order['pay_code'])->first();
        if($paymentModel && !empty($paymentModel->pay_exchange_rate)){
            $rate = $paymentModel->pay_exchange_rate;
        }

        // 检查支付的金额是否相符
        if($order['pay_code'] != 'GMO_PAYMENT'){

            if ($order['actually_paid'] * $rate != $total_amount) { // 网站付款时的币种的订单总金额
                $paymentMsg .= 'fail amount is wrong'.PHP_EOL;
                file_put_contents($logName, $paymentMsg, FILE_APPEND);
    
                return false;
            }
        }
        // 改变订单状态
        $order->out_order_num = $trade_no;
        $order->is_pay = Order::PAY_SUCCESS;
        $order->pay_time = $gmt_payment;
        $order->updated_at = time();
        if ($order->save()) {
            //处理未注册用户的优惠券业务
            (new OrderService())->handlerPayCouponUser($order->id);
            (new SendEmailController())->payment($order->id);
            // Order::sendPaymentEmail($order); // 发送已付款的邮件
            $paymentMsg .= 'success to update status'.PHP_EOL;
        } else {
            $paymentMsg .= 'fail to update status'.PHP_EOL;
            $paymentMsg .= print_r($order->getErrors(), true).PHP_EOL;
            return false;
        }
        $paymentMsg .= print_r($order->getAttributes(), true);
        file_put_contents($logName, $paymentMsg, FILE_APPEND);
        return true;
    }
}
