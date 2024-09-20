<?php

namespace App\Http\Controllers;

use App\Services\IpBanLogService;
use App\Services\SlidingWindowRateLimiter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct() {
        // 排除一些不需要验证的路由
        $excludeRoute = [
            // 'api/common/top-menus',//测试
            //支付相关
            'api/order/wechat-order',
            'api/wx-empower/index1',
            'api/order/pay',
        ];
        $route = request()->route()->uri();
        if ($route && in_array($route, $excludeRoute)) {
            return;
        }
        //值为1开启,默认关闭  接口安全检查
        $setKeyPrefix = env('APP_NAME').':';
        $is_open_check_key = $setKeyPrefix.'is_open_check_security';
        $is_open_check_security = Redis::get($is_open_check_key) ?? 0;
        if ($is_open_check_security > 0) {
            $securityCheckWhiteIplist = [];
            $white_ip_securty_check_key = $setKeyPrefix.'white_ip_security_check';
            $securityCheckWhiteIps = Redis::get($white_ip_securty_check_key) ?? '';
            if (!empty($securityCheckWhiteIps)) {
                $securityCheckWhiteIplist = explode(',', $securityCheckWhiteIps);
            }
            $securityCheckWhiteIplist[] = '127.0.0.1';
            if (!in_array(request()->ip(), $securityCheckWhiteIplist)) {
                $this->securityCheck();
            }
        }
        //值为1开启,默认关闭,  接口限流策略
        $is_open_limit_key = $setKeyPrefix.'is_open_limit_req';
        $is_open_limit_req = Redis::get($is_open_limit_key) ?? 0;
        if ($is_open_limit_req > 0) {
            //ip封禁验证
            $route = request()->route();
            $routeUril = '';
            if (!empty($route->uri)) {
                $routeUril = $route->uri;
            }
            $ip = request()->ip();
            $ip_white_rules_key = $setKeyPrefix.'ip_white_rules';
            $whiteIplist = Redis::get($ip_white_rules_key) ?? [];
            //ip白名单验证
            $checkRes = $this->isIpAllowed($ip, $whiteIplist);
            if (!$checkRes) {
                //获取封禁配置
                $windows_time_key = $setKeyPrefix.'window_time';
                $windowsTime = Redis::get($windows_time_key) ?? 5;
                $req_limit_key = $setKeyPrefix.'req_limit';
                $reqLimit = Redis::get($req_limit_key) ?? 10;
                $expire_time_key = $setKeyPrefix.'expire_time';
                $expireTime = Redis::get($expire_time_key) ?? 60;
                $ipCacheKey = $ip.':'.$routeUril;
                $res = (new SlidingWindowRateLimiter($windowsTime, $reqLimit, $expireTime))->slideIsAllowed(
                    $ipCacheKey
                );
//            $res = (new SlidingWindowRateLimiter($windowsTime, $reqLimit, $expireTime))->simpleIsAllowed($ipCacheKey);
                if (!$res) {
                    //添加封禁日志
                    $this->addBanLog($ip, $routeUril);
                    http_response_code(429);
                    ReturnJson(false, '请求频率过快~');
                }
            }
        }
    }

    /**
     * 添加封禁日志
     *
     * @param $ip
     * @param $route
     *
     */
    public function addBanLog($ip, $route) {
        $data = [
            'ip'         => $ip,
            'route'      => $route,
            'created_at' => time(),
            'updated_at' => time(),
        ];
        (new IpBanLogService())->addIpBanLog($data);
    }

    // TODO: cuizhixiong 2024/6/28 暂时写死,后期弄成可配置的签名key
    public $signKey = '62d9048a8a2ee148cf142a0e6696ab26';

    public function securityCheckList() {
        //需要签名检查的接口
        $needSignCheckList = [
        ];
        $route = request()->route();
        $actionInfo = $route->getAction();
        if (in_array($actionInfo['controller'], $needSignCheckList)) {
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
        $sign = $this->getSign();
        $ts = $this->getTs();
        $params['ts'] = $ts;
        $checkRes = $this->verifySign($params, $this->signKey, $sign);
        if (!$checkRes) {
            ReturnJson(false, '签名错误');
        }
    }

    /**
     * 校验请求事件
     */
    protected function checkTime() {
        $ts = $this->getTs();
        if (time() - $ts > 5) {
            ReturnJson(false, '接口参数已过期');
        }
    }

    /**
     * 校验签名是否正确
     *
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
        ksort($params, 2);
        reset($params);
        $query_string = array();
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                ksort($val, 2);
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

    public function isIpAllowed($ip, $whitelist) {
        if (empty($whitelist)) {
            return false;
        }
        $whitelist = explode("\n", $whitelist);
        if (!is_array($whitelist)) {
            return false;
        }
        // 将IP地址分割为四部分
        $ipParts = explode('.', $ip);
        if (count($ipParts) != 4) {
            return false; // 确保是IPv4地址
        }
        foreach ($whitelist as $pattern) {
            $patternList = explode('.', $pattern);
            if (count($patternList) != 4) {
                continue;   // 确保模式是IP地址
            }
            //俩个通配符
            if ($patternList[2] == '*' && $patternList[3] == '*') {
                if ($patternList[0] == $ipParts[0] && $patternList[1] == $ipParts[1]) {
                    return true;
                }
            } elseif ($patternList[3] == '*') {
                //一个通配符
                if ($patternList[0] == $ipParts[0]
                    && $patternList[1] == $ipParts[1]
                    && $patternList[2] == $ipParts[2]) {
                    return true; // 前三部分匹配，最后一位是通配符，所以允许
                }
            } elseif ($pattern == $ip) {
                // 没有通配符，直接比较整个IP
                return true;
            }
        }

        return false; // 没有匹配项
    }

    /**
     * 获取时间戳
     *
     * @return array|mixed|string|null
     */
    public function getTs() {
        $ts = request()->header('ts', 0);
        if (empty($ts)) {
            $ts = request()->input('ts', 0);
        }

        return $ts;
    }

    public function getSign() {
        $sign = request()->header('sign', '');
        if (empty($sign)) {
            $sign = request()->input('sign', '');
        }

        return $sign;
    }
}
