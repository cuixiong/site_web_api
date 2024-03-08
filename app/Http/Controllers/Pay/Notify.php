<?php
namespace App\Http\Controllers\Pay;

use AlibabaCloud\Tea\Request;
use App\Http\Controllers\Controller;

/**
 * 处理各种回调请求
 */
class Notify extends Controller
{
    public function __construct($action)
    {
        $dir = storage_path('log').'/_notify_log_/'.date('Y_m/', time());
        $timestamp = time();
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
        }
        $_DATA = count($_POST) == 0 ? file_get_contents("php://input") : $_POST;
        $log = sprintf("%s", var_export([
                        '_DATA' => $_DATA,
                        '_GET' => $_GET,
                        '_SERVER' => $_SERVER,
                    ], true));
        $logName = $dir.$timestamp.'.log';
        file_put_contents($logName, $log, FILE_APPEND);

        return parent::beforeAction($action);
    }

    public function Alipay(Request $request)
    {
        try {
            $pay = new Alipay();
            return $pay->notify();
        } catch (\Throwable $e) {
            $logName = storage_path('log').'/_notify_log_/err_'.time().'.log';
            file_put_contents($logName, print_r($e, true), FILE_APPEND);
        }
    }

    public function Wechatpay(Request $request)
    {
        try {
            $pay = new Wechatpay();
            return $pay->notify();
        } catch (\Throwable $e) {
            $logName = storage_path('log').'/_notify_log_/err_'.time().'.log';
            file_put_contents($logName, print_r($e, true), FILE_APPEND);
            $statusCode = $e->getCode();
            return ['code' => 'ERROR', 'message' => $e->getMessage(),'code' => $statusCode];
        }
    }
}
