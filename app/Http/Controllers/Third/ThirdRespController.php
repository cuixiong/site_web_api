<?php
/**
 * ThirdRespController.php UTF-8
 * 第三方接收方接口
 *
 * @date    : 2024/6/11 14:51 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers\Third;

use App\Http\Controllers\Common\SendEmailController;
use App\Models\ContactUs;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ThirdRespController extends BaseThirdController {
    public function __construct() {
        parent::__construct();
    }

    public function sendEmail() {
        $inputParams = request()->input();
        \Log::error('返回结果数据:'.json_encode([$inputParams]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        $code = $inputParams['code'];
        $res = false;
        $id = $inputParams['id'];
        if ($code == 'placeOrder') {
            $orderId = $inputParams['id'];
            $res = (new SendEmailController())->placeOrder($orderId);
        } elseif ($code == 'paySuccess') {
            $orderId = $inputParams['id'];
            $res = (new SendEmailController())->payment($orderId);
        } elseif ($code == 'contactUs') {
            //联系我们
            $res = (new SendEmailController())->contactUs($id);
        } elseif ($code == 'productSample') {
            //留言
            $res = (new SendEmailController())->productSample($id);
        } elseif ($code == 'sampleRequest') {
            //申请样本
            $res = (new SendEmailController())->productSample($id);
        } elseif ($code == 'customized') {
            //定制报告
            $res = (new SendEmailController())->customized($id);
        }
        ReturnJson($res);
    }

    public function testSendEmail() {
        $inputParams = request()->input();
        $code = $inputParams['action'];
        $testEmail = $inputParams['testEmail'];
        if (empty($code) || empty($testEmail)) {
            ReturnJson(false, '参数错误');
        }
        $AppName = env('APP_NAME');
        request()->headers->set('Site', $AppName); // 设置请求头
        $sendEmailController = new SendEmailController();
        $sendEmailController->testEmail = $testEmail;
        $res = true;
        if ($code == 'placeOrder') {
            //未下单
            $orderId = Order::query()->orderBy('id', 'asc')->value('id');
            $res = ($sendEmailController)->placeOrder($orderId);
        } elseif ($code == 'password') {
            //重置密码
            $res = ($sendEmailController)->ResetPassword($testEmail);
        } elseif ($code == 'contactUs') {
            //联系我们
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->contactUs($id);
        } elseif ($code == 'productSample') {
            //留言
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->Message($id);
        } elseif ($code == 'sampleRequest') {
            //申请样本
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->productSample($id);
        } elseif ($code == 'customized') {
            //定制报告
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->customized($id);
        } elseif ($code == 'payment') {
            //已下单
            $orderId = Order::query()->orderBy('id', 'asc')->value('id');
            $res = ($sendEmailController)->payment($orderId);
        }
        ReturnJson($res);
    }

    public function syncRedisVal() {
        $inputParams = request()->input();
        $key = $inputParams['key'];
        $val = $inputParams['val'];
        $type = $inputParams['type'];
        if (empty($key)) {
            ReturnJson(false, '参数错误');
        }
        if ($type == 'delete') {
            Redis::del($key);
        } else {
            Redis::set($key, $val);
        }
        ReturnJson(true, '请求成功');
    }

    public function clearBan() {
        $inputParams = request()->input();
        $type = $inputParams['type'];
        $key = $inputParams['key'];
        if ($type == 1) {
            //清除IP封禁
            $cache_prefix_key = env('APP_NAME').'_rate_limit:';
        } elseif ($type == 2) {
            //清除UA封禁
            $cache_prefix_key = env('APP_NAME').'_rate_ua_limit:';
        }
        if (empty($cache_prefix_key)) {
            ReturnJson(false, '请求失败');
        }
        $reqKey = $cache_prefix_key.$key;
        //清除缓存
        // 删除多个键
        $keysToDelete = Redis::keys($reqKey."*");
        //\Log::error('$keysToDelete:'.json_encode([$keysToDelete]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        if (!empty($keysToDelete)) {
            Redis::del($keysToDelete);
        }
        ReturnJson(true, '请求成功');
    }
}
