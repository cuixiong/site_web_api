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

class ThirdRespController extends BaseThirdController {
    public function sendEmail() {
        $inputParams = request()->input();
        $code = $inputParams['code'];
        $res = false;
        if($code == 'placeOrder'){
            $orderId = $inputParams['id'];
            $res = (new SendEmailController())->placeOrder($orderId);
        } elseif($code == 'paySuccess'){
            $orderId = $inputParams['id'];
            $res = (new SendEmailController())->payment($orderId);
        }

        ReturnJson($res);
    }
}
