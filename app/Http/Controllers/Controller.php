<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function __construct() {
//        $route = request()->route();
//        $action = $route->getAction();
//        \Log::error('返回结果数据:'.json_encode([$_SERVER,  request()->header() , $action ]));
    }

    // TODO: cuizhixiong 2024/6/28 暂时写死,后期弄成可配置的签名key
    public $signKey = '62d9048a8a2ee148cf142a0e6696ab26';

    public function securityCheckList() {
        //需要签名检查的接口
        $needSignCheckList = [

        ];
        $route = request()->route();
        $actionInfo = $route->getAction();
        if(in_array($actionInfo['controller'] , $needSignCheckList)){
            $this->checkTime();
            $this->checkSign();
        }
    }

    /**
     *  安全检查接口
     */
    public function securityCheck() {
        $this->checkTime();
        $this->checkSign();
    }

    /**
     * 校验签名
     */
    public function checkSign() {
        $params = request()->input();
        $sign = $params['sign'] ?? '';
        $checkRes = $this->verifySign($params, $this->signKey, $sign);
        if (!$checkRes) {
            ReturnJson(false, '签名错误');
        }
    }

    /**
     * 校验请求事件
     */
    protected function checkTime() {
        $params = request()->input();
        $ts = $params['ts'] ?? 0;
        if (time() - $ts  > 5) {
            ReturnJson(false, '接口参数已过期');
        }
    }

    /**
     * 校验签名是否正确
     * @param $params
     * @param $secret
     * @param $sign
     *
     * @return bool
     */
    public function verifySign($params, $secret, $sign) {
        unset($params['sign']);
        //计算签名
        $mk = $this->makeSource($params);
        $sourceSignStr = $mk.'&'.$secret;
        $sourceSignStr = urlencode($sourceSignStr);
        $mySign = md5($sourceSignStr);

        //\Log::error('签名原串:'.$sourceSignStr.'-----------服务器签名:'.$mySign.'-----提交签名:'.$sign);
        return strtoupper($mySign) == strtoupper($sign);
    }



    private function makeSource($params) {
        ksort($params);
        reset($params);
        $query_string = array();
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                ksort($val);
                reset($val);
                foreach ($val as $_k2 => $_v2) {
                    array_push($query_string, $key.'-'.$_k2.'='.$_v2);
                }
            } else {
                array_push($query_string, $key.'='.$val);
            }
        }
        $query_string = join('&', $query_string);

        return $query_string;
    }

}
