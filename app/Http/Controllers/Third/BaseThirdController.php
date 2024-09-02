<?php
/**
 * BaseThirdController.php UTF-8
 * 第三方接口控制器基类
 *
 * @date    : 2024/6/11 14:54 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers\Third;
use App\Http\Controllers\Controller;

class BaseThirdController extends Controller {

    public $signKey = '62d9048a8a2ee148cf142a0e6696ab26';

    public function __construct() {
        $this->checkSign();
    }

    public function checkSign() {
        $inputParams = request()->input();
        if (isset($inputParams['sign'])) {
            $sign = $inputParams['sign'];
            unset($inputParams['sign']);
            $sourceSignStr = '';
            ksort($inputParams);
            foreach ($inputParams as $key => $value) {
                $sourceSignStr .= $key . '=' . $value . '&';
            }
            $sourceSignStr .= 'key='.$this->signKey;
            $signStr = md5($sourceSignStr);
            if ($signStr != $sign) {
                //ReturnJson(false, '签名错误', [$signStr , $sign, $sourceSignStr]);
                ReturnJson(false, '签名错误');
            }
        }else{
            ReturnJson(false, '签名错误', []);
        }
    }
}
