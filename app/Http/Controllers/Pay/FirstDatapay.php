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
class FirstDatapay extends Pay {
    public function createFormdata($order) {
        // TODO: Implement createFormdata() method.
    }

    public function notify() {
        try {
            $dir = storage_path('log').'/firstData/'.date('Y_m/', time());
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
            }
            $params = request()->input();
            // TODO: cuizhixiong 2024/8/2  因为改支付没有文档, 支付通知签名做不了验证, 因此这里有盗刷的风险
            // 如出现盗刷, 开发人员概不担责!
            $logName = date("d-H");
            $logName = $dir.$logName.'.log';
            if (empty($params['x_cust_id']) || empty($params['x_amount']) || empty($params['x_trans_id'])
                || empty($params['x_fp_timestamp'])) {
                return false;
            }
            $out_trade_no = $params['x_cust_id'];
            $total_amount = $params['x_amount'];
            $trade_no = $params['x_trans_id'];
            $gmt_payment = $params['x_fp_timestamp'];
            $this->handlerOrderPaySucService($out_trade_no, $logName, $total_amount, $trade_no, $gmt_payment);

            return true;
        } catch (\Exception $e) {
            throw $e;

            return false;
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
        $notifyUrl = $domain.'/api/notify/firstdata';
        $xlogin = env('FIRSTDATA_XLOGIN');
        $secret = env('FIRSTDATA_SECRET');
        $formData = [
            'x_login'            => $xlogin,
            'x_amount'           => $order->actually_paid,
            'x_cust_id'          => $order->order_number,
            'x_currency_code'    => 'USD',
            'x_fp_sequence'      => rand(1000, 100000) + 123456, //随机串
            'x_fp_timestamp'     => time(),
            //'x_relay_url'        => $notifyUrl,
            //'x_relay_response'   => 'TRUE',
            'x_receipt_link_url' => $domain.'/paymentComplete/'.$order->id,
            'x_show_form'        => 'PAYMENT_FORM',
        ];
        $formData['hmac_data'] = $formData['x_login']."^".
                                 $formData['x_fp_sequence']."^".
                                 $formData['x_fp_timestamp']."^".
                                 $formData['x_amount']."^".
                                 $formData['x_currency_code'];
        $formData['x_fp_hash'] = hash_hmac('MD5', $formData['hmac_data'], $secret);
        $input_list = '';
        foreach ($formData as $key => $value) {
            $input_list .= "<input type='hidden' name='$key' value='$value'>";
        }
        $html = <<<EOF
            <form id="myForm" action="https://checkout.globalgatewaye4.firstdata.com/payment" method="POST">
                 $input_list
            </form>
            <script>
                document.getElementById("myForm").submit();
            </script>
EOF;

        return $html;
    }
}
