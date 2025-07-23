<?php

namespace App\Http\Controllers\Pay;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * 处理各种回调请求
 */
class Notify extends Controller {
    public function __construct() {
        $dir = storage_path('log').'/_notify_log_/'.date('Y_m/', time());
        $timestamp = time();
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
        }
        $_DATA = count($_POST) == 0 ? file_get_contents("php://input") : $_POST;
        $log = sprintf(
            "%s", var_export([
                                 '_DATA'   => $_DATA,
                                 '_GET'    => $_GET,
                                 '_SERVER' => $_SERVER,
                             ],
                             true
                )
        );
        $logName = $dir.$timestamp.'.log';
        file_put_contents($logName, $log, FILE_APPEND);
        // parent::__construct(); //一定不要加这个, 否则会进入签名验证中, 导致支付回调全部失败
    }

    public function Alipay(Request $request) {
        try {
            $pay = new Alipay();

            return $pay->notify();
        } catch (\Throwable $e) {
            $logName = storage_path('log').'/_notify_log_/err_'.time().'.log';
            file_put_contents($logName, print_r($e, true), FILE_APPEND);
        }
    }

    public function Wechatpay(Request $request) {
        try {
            $pay = new Wechatpay();

            return $pay->notify();
        } catch (\Throwable $e) {
            $logName = storage_path('log').'/_notify_log_/err_'.time().'.log';
            file_put_contents($logName, print_r($e, true), FILE_APPEND);
            $statusCode = $e->getCode();

            return ['code' => 'ERROR', 'message' => $e->getMessage(), 'err_code' => $statusCode];
        }
    }

    public function Stripe(Request $request) {
        try {
            $fileName = date("d-H").".log";
            $dir = storage_path('logs').'/notify_log/'.date("Y-m");
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
            }
            $logName = $dir."/".$fileName;
            if (!file_exists($logName)) {
                file_put_contents($logName, "");
            }
            $params = [
                'headers' => $request->header(),
                'data'    => $_REQUEST,
                'input'   => @file_get_contents('php://input'),
            ];
            file_put_contents($logName, json_encode([$params]), FILE_APPEND);
            $pay = new Stripepay();
            $res = $pay->notify();
            if ($res) {
                ReturnJson(true, 'success');
            } else {
                ReturnJson(false, 'fail');
            }
        } catch (\Throwable $e) {
            file_put_contents($logName, print_r($e, true), FILE_APPEND);
            $statusCode = $e->getCode();
            http_response_code(400);
            ReturnJson(false, '验签失败~'.$e->getMessage());
        }
    }

    public function FirstData() {
        $_input = file_get_contents('php://input');
        $request = request();
        $params = $request->input();
        $heards = $request->header();
        \Log::error('返回结果数据FirstData---input:'.json_encode([$_input]));
        \Log::error('返回结果数据FirstData---params:'.json_encode([$params]));
        \Log::error('返回结果数据FirstData---heards:'.json_encode([$heards]));


        $firstDataPay = new FirstDatapay();
        $res = $firstDataPay->notify();
        if($res){
            return 'Success';
            if (strpos($res, 'http') !== false) {
                return '<script>window.location.href="'.$res.'";</script>';
            }else {
                return 'Success';
            }
        }else{
            return 'fail';
        }
    }

    public function wiseNotify() {
        return '1';
    }


    public function paypalNotify() {
        $_input = file_get_contents('php://input');
        $request = request();
        $heards = $request->header();
        \Log::error('返回结果数据paypalNotify---input:'.$_input);
        \Log::error('返回结果数据paypalNotify---heards:'.json_encode($heards));

        $res = (new PaypalPay())->notify();
        if($res){
            return 'ok';
        }else{
            header('HTTP/1.1 403');die('check error');
        }
    }

    public function airwallexNotify(Request $request) {
        \Log::error('返回请求头数据:'.json_encode([$request->header()]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        \Log::error('返回结果数据:'.json_encode($request->input()).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        $res = (new AirwallexPay())->notify();
        if($res){
            return 'ok';
        }else{
            header('HTTP/1.1 403');die('check error');
        }
    }
    

    public function GmoPaymentNotify(Request $request) {
        \Log::error('返回请求头数据:'.json_encode([$request->header()]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        \Log::error('返回结果数据:'.json_encode($request->input()).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        $res = (new GmoPayment())->notify();
        if($res){
            return 'ok';
        }else{
            header('HTTP/1.1 403');die('check error');
        }
    }
    
    public function RobotPaymentNotify(Request $request) {
        \Log::error('返回请求头数据:'.json_encode([$request->header()]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        \Log::error('返回结果数据:'.json_encode($request->input()).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        $res = (new RotbotPayment())->notify();
        if($res){
            return 'ok';
        }else{
            header('HTTP/1.1 403');die('check error');
        }
    }

}
