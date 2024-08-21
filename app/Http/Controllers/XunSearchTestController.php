<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Http\Controllers\Pay\PaypalPay;
use App\Http\Controllers\Pay\Wisepay;
use App\Http\Helper\XunSearch;
use App\Jobs\HandlerEmailJob;
use App\Models\Order;
use App\Models\Products;
use App\Models\User;
use App\Services\OrderService;
use App\Services\SenWordsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use XS;

class XunSearchTestController extends Controller {
    public function stripePayTest(Request $request) {
//        $str = "0-goods_id=332534&0-number=1&0-price_edition=1&ts=1723448986463&1-goods_id=332534&1-number=1&1-price_edition=2&62d9048a8a2ee148cf142a0e6696ab26";
//         parse_str($str, $params);
        $order = Order::query()->orderby("id" , "desc")->first();
        $token = (new PaypalPay())->do($order);
        dd($token);

        $wise = new Wisepay();
        $a = $wise->test();
//        $a = $wise->test2();
//        $a = $wise->test3();
        dd($a);

        $storeId = 'WSP-MARKE-hx1mCADaBQ'; // Your Store ID
        $transId = '1090914307'; // The transaction ID from the notification
        $amount = '0.01'; // The transaction amount from the notification
        $receivedMd5Hash = 'c90bc881ce37841cca06bf6f60adbcc6'; // The MD5 hash from the notification
        $sharedSecret = 'vZz88eaJpzSPbePG6Txe'; // Your shared secret key
        // Construct the hash string
        $hashString = $sharedSecret . $storeId . $transId . $amount;
        $computedMd5Hash = strtoupper(md5($hashString));
        if ($computedMd5Hash === $receivedMd5Hash) {
            dd([$computedMd5Hash , "ok"]);
        } else {
            dd([$computedMd5Hash , "error"]);
        }
        dd($computedMd5Hash);

        $a = 'WSP-MARKE-hx1mCADaBQ^170724^1722563004^0.01^USD';
        $b = hash_hmac('MD5', $a, 'vZz88eaJpzSPbePG6Txe');
        dd($b);

        $orderNumber = date('YmdHis', time()).mt_rand(10, 99);
        $notifyUrl = 'https://mmgcn.marketmonitorglobal.com.cn/api/notify/firstdata';
        $formData = [
            'x_login'            => 'WSP-MARKE-hx1mCADaBQ',
            'x_amount'           => 0.01,
            'x_cust_id'          => $orderNumber,
            'x_currency_code'    => 'USD',
            'x_fp_sequence'      => rand(1000, 100000) + 123456,
            'x_fp_timestamp'     => time(),
            'x_relay_url'        => $notifyUrl,
            'x_relay_response'   => 'TRUE',
            'x_receipt_link_url' => 'https://mmgcn.marketmonitorglobal.com.cn/paymentComplete/'.$orderNumber,
            'x_show_form'        => 'PAYMENT_FORM',
        ];
        $formData['hmac_data'] = $formData['x_login']."^".
                                 $formData['x_fp_sequence']."^".
                                 $formData['x_fp_timestamp']."^".
                                 $formData['x_amount']."^".
                                 $formData['x_currency_code'];
        $formData['x_fp_hash'] = hash_hmac('MD5', $formData['hmac_data'], 'vZz88eaJpzSPbePG6Txe');

        $input_list = '';
        foreach ($formData as $key => $value){
            $input_list .= "<input type='hidden' name='$key' value='$value'>";
        }

        $html = <<<EOF
            <form id="myForm" action="https://checkout.globalgatewaye4.firstdata.com/payment" method="POST">
                 $input_list
                <input type="submit" value="Pay Now">
            </form>
            <script>
                document.getElementById("myForm").submit();
            </script>
EOF;
        echo $html;
        die;
        $a = (new SendEmailController())->payment(1794);
        dd($a);
        // 后端创建支付意图
        $apiKey = env('STRIPE_APIKEY');
        \Stripe\Stripe::setApiKey(
            $apiKey
        );
        $request = $request->all();
        $amount = $request['amount'] ?? 2000;
        $paymentIntent = \Stripe\PaymentIntent::create([
                                                           'amount'               => $amount,
                                                           // 以最小货币单位表示，例如：1000 表示 $10.00
                                                           'currency'             => 'usd',
                                                           'description'          => '测试报告',
                                                           'receipt_email'        => '798396652@qq.com',
                                                           'metadata'             => [
                                                               'order_id' => uniqid(),
                                                           ],
                                                           'payment_method_types' => ['card', 'alipay'],
                                                       ]);
        $clientSecret = $paymentIntent->client_secret;

        return view('paytest', ['clientSecret' => $clientSecret]);
    }

    public function clean(Request $request) {
        // 后端创建支付意图
        \Stripe\Stripe::setApiKey(
            'sk_test_51ObCzVHL71UkJjJvvmJe0JVr5mROf4eaJvFN7WA28FDWYdDsvoNVzQ3RelLMtXgvle0ugcYslYJzo7NP1keYd6US00KJrYlvj6'
        );
        $paymentIntent = \Stripe\PaymentIntent::create([
                                                           'amount'               => 2000,// 以最小货币单位表示，例如：1000 表示 $10.00
                                                           'currency'             => 'usd',
                                                           'payment_method_types' => ['card'],
                                                       ]);
        // 返回支付意图客户端密钥给前端
        dd(['clientSecret' => $paymentIntent->client_secret]);
        //ipv4地址
        $ip = '47.91.74.212';
//        $ip = '8.220.193.184';
        $ipdb = new \PhpCpm\IpAddress\IpRegion();
        $driver = \PhpCpm\IpAddress\drivers\Ip2region::class;
        $a = $ipdb->drvier($driver)->init($ip)->getMap();
        dd($a);
        $pc = new ProductController();
        $pinfo = Products::query()->find(327573)->toArray();
        $a = $pc->viewLog($pinfo);
        dd($a);
        $smController = new SendEmailController();
//        $a = $smController->contactUs(145);
//        dd($a);
//        $a = $smController->productSample(7);
//        dd($a);
        $a = $smController->payment(1672);
        dd($a);
        $a = (new SendEmailController)->placeOrder(1672);
        dd($a);
        $a = (new OrderService())->handlerPayCouponUser(1468);
        dd($a);
        $a = (new SendEmailController)->placeOrder(1468);
        dd($a);
        $a = $smController->contactUs(145);
        dd($a);
        $a = $smController->ResetPassword('798396652@qq.com');
        dd($a);
        $user = User::find(17);
        $user->password = Hash::make('qq798396652');
        $rs = $user->save();
        dd($rs);
        $a = SenWordsService::checkFitter("普1金");
        dd($a);
        $a = encrypt("17&798396652@qq.com");
        $b = decrypt($a);
        dd([$a, $b]);
        //测试 付款完成, 邮件
        $smController = new SendEmailController();
        $a = $smController->productSample(104);
        $a = $smController->payment(1384);
        dd($a);
        $a = date("Y-m-d H:i:s", 1714295009);
        dd($a);
//        $xs = new XunSearch();
//        $xs->clean();
//        echo "完成".date('Y-m-d H:i:s',time());
    }

    function printStackTrace() {
        $trace = debug_backtrace();
        array_shift($trace); // 移除第一个元素，因为那是当前函数
        foreach ($trace as $index => $call) {
            if (isset($call['type']) && $call['type'] == 'include' || $call['type'] == 'require') {
                // 对于 include 或 require 的调用，我们可能只关心文件名
                //echo "#{$index} {$call['type']}d file: {$call['file']} called at [{$call['line']}]" . PHP_EOL;
            } else {
                // 对于其他类型的调用，打印详细信息
                echo "#{$index} Called by {$call['class']}{$call['type']}{$call['function']}()"
                     ." called at [{$call['file']}:{$call['line']}]".PHP_EOL;
            }
        }
    }

    public function test(Request $request) {
        $input = $request->all();
        $keyword = $input['keyword'];
        $RootPath = base_path();
        $xs = new XS($RootPath.'/config/xunsearch/MMG_CN.ini');
        $search = $xs->search;
        $queryWords = "name:{$keyword}";
        $search->setQuery($queryWords);
        $docs = $search->search();
        $count = $search->count();
        dd([$docs, $count]);
        //测试 付款完成, 邮件
//        $smController = new SendEmailController();
//        $a = $smController->payment(1384);
//        dd($a);
    }
}
