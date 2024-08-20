<?php
/**
 * Wisepay.php UTF-8
 * 支付逻辑处理
 *
 * @date    : 2024/8/6 10:28 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers\Pay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use TransferWise\Client;

class Wisepay extends Pay {
    public function createFormdata($order) {
        // TODO: Implement createFormdata() method.
    }

    public function test() {
        // 初始化客户端
        $profile_id = "19405681";
        $config = [
            "token"      => "2f2a7092-7478-424e-b74f-3afff281debd",
            "profile_id" => $profile_id,
            "env"        => "sandbox" // optional //线上
            //"token"      => "1743a608-887d-4ff7-a7ac-78c1f75a3e8a",
            //"profile_id" => "57275931",
            //"env"        => "optional" // optional //线上
        ];
        $client = new Client($config);
//        $a = $client->profiles->all();
//        dd($a);
        $sourceCurrency = 'GBP';
        $targetCurrency = 'GBP';
        ////////////////
        $recip_data = [
            'profile'           => $profile_id,
            'accountHolderName' => 'hong tao',
            'currency'          => $sourceCurrency,
            'type'              => 'iban',
            'details'           => [
                'iban' => 'GB29NWBK60161331926819'  // 收款方的 IBAN
            ]
        ];
//        $rs = $client->recipient_accounts->create($recip_data);
//        dd($rs);
        /////////////////
//        $a = $client->recipient_accounts->all();
//        dd($a);
        $params = [
            "sourceCurrency"  => $sourceCurrency,
            "targetCurrency"  => $targetCurrency,
            "sourceAmount"    => "10",
            "targetAmount"    => null,
            "payOut"          => null,
            "preferredPayIn"  => null,
            "targetAccount"   => 700224726,
            "paymentMetadata" => [
                "transferNature" => "MOVING_MONEY_BETWEEN_OWN_ACCOUNTS"
            ]
        ];
//        $res = $client->quotes->create(
//            $params
//        );
//        $quoteUuid = $res['id'];
//        dd($res);
        $res = $client->quotes->createNew(
            $params, $profile_id
        );
        $quoteUuid = $res['id'];
//
//        dd($response);
//        dd($recipient);
//        dd($res);
        $orderNumber = date('YmdHis', time()).mt_rand(10, 99);
        //获取转账列表
//        $list = $client->transfers->list([]);
//        dd($list);
        $trandData = [
            "targetAccount"         => '700224726',
            "sourceAccount"         => '700224725',
            "quoteUuid"             => $quoteUuid,
            "customerTransactionId" => $quoteUuid,
            "details"               => [
                "reference"                         => "Testing transfer",
                "transferPurpose"                   => "verification.transfers.purpose.pay.bills",
                "transferPurposeSubTransferPurpose" => "verification.sub.transfers.purpose.pay.interpretation.service",
                "sourceOfFunds"                     => "verification.source.of.funds.other"
            ],
        ];
//        $res = $client->transfers->requirements($trandData);
//        dd($res);
        $transfer_data = $client->transfers->create(
            $trandData
        );
        \Log::error('返回结果数据$transfer_data:'.json_encode([$transfer_data]));
        $domain = rtrim(env('APP_URL', ''), '/');
        $url = $domain.'/paymentComplete/1969';
        $transferData = [
            'type'        => 'BALANCE',
            'details' => [
                'sourceAmount' => 100, // 你希望支付的金额
                'sourceCurrency' => 'GBP', // 你账户中的货币
            ]
            //'redirectUri' => $url
        ];
        $res = $client->transfers->fund($transfer_data['id'], $transferData);
        dd($res);
    }

    public function test2() {
        // 初始化客户端
        $profile_id = "19405681";
        $config = [
            "token"      => "2f2a7092-7478-424e-b74f-3afff281debd",
            "profile_id" => $profile_id,
            "env"        => "sandbox" // optional //线上
            //"token"      => "1743a608-887d-4ff7-a7ac-78c1f75a3e8a",
            //"profile_id" => "57275931",
            //"env"        => "optional" // optional //线上
        ];
        $client = new Client($config);
        $domain = rtrim(env('APP_URL', ''), '/');
        $url = $domain.'/paymentComplete/1969';
        $transferData = [
            'type'        => 'CARD',
            'redirectUri' => $url
        ];
        //$res = $client->transfers->fund("T54069337", $transferData);
        $res = $client->transfers->fund("54069077");
        dd($res);
    }

    public function test3() {
        // 初始化客户端
        $profile_id = "19405681";
        $config = [
            "token"      => "2f2a7092-7478-424e-b74f-3afff281debd",
            "profile_id" => $profile_id,
            "env"        => "sandbox" // optional //线上
            //"token"      => "1743a608-887d-4ff7-a7ac-78c1f75a3e8a",
            //"profile_id" => "57275931",
            //"env"        => "optional" // optional //线上
        ];
        $client = new Client($config);
//        $a = $client->simulation->processing(54069077);
//        dd($a);
        $sourceCurrency = 'GBP';
        //创建余额账户
//        $data = [
//            'currency' => $sourceCurrency,
//            //'type' => "STANDARD",
//            'type' => "SAVINGS",
//            'name' => 'account_B',
//            'X-idempotence-uuid' => Str::uuid()->toString()
//        ];
//        $c = $client->simulation->createAccount($profile_id, $data);
//        dd($c);

        //余额充值
        $upd_data = [
            'profileId' => $profile_id,
            'balanceId' => 213460,
            'currency'  => $sourceCurrency,
            'amount'    => 1001,
        ];
        $b = $client->simulation->balanceAdd($upd_data);
        dd($b);

    }

    public function createWebhook() {
    }

    public function notify() {
        // 处理 wise 的 webhook
        $payload = @file_get_contents('php://input');
        try {
//            $res = $this->handlerOrderPaySucService(
//                $out_trade_no, $logName, $total_amount, $trade_no, $gmt_payment
//            );
        } catch (\Exception $e) {
            \Log::error('返回结果数据:'.json_encode([$e]));
            $res = false;
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
        return true;
    }
}
