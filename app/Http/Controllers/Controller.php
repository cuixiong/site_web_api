<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\BanWhiteList;
use App\Models\SystemValue;
use App\Services\IPAddrService;
use App\Services\IpBanLogService;
use App\Services\ReqLogService;
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
            'api/order/details', //微信扫码轮询是否支付成功
            'api/wx-empower/index1',
            'api/order/pay',
        ];
        $route = request()->route()->uri();
        if ($route && in_array($route, $excludeRoute)) {
            return;
        }

        // 签名检查 (系统配置那一个)
        $checkRes = $this->checkWhiteIp();
        if (!$checkRes) {
            //签名检查
            $this->signCheck();
            //请求日志记录
            $this->accessLog();
            //UA请求头封禁
            $this->checkUaHeader();
            //IP限流封禁
            $this->ipRateLimit();
        }
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
        if ($ip == '127.0.0.1') {
            //本地的直接跳过
            return true;
        }
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

    /**
     *  校验UA头
     */
    public function checkUaHeader() {
        $header = request()->header();
        if (empty($header['user-agent'])) {
            return true;
        }
        $ip = get_client_ip();
        $real_ip = $ip;
        $hidden = $this->getSetValByKey('is_open_ua_limit_req');
        if ($hidden > 0) {
            $req_ua_limit = $this->getSetValByKey('ua_limit');
            $req_ua_window_time = $this->getSetValByKey('ua_window_time');
            $ua_expire_time = $this->getSetValByKey('ua_expire_time') ?? 60;
            $append_ua_expire_time = $this->getSetValByKey('append_ua_expire_time') ?? 5;
            $route = request()->route();
            $routeUril = '';
            if (!empty($route->uri)) {
                $routeUril = $route->uri;
            }
            $cache_prefix_key = env('APP_NAME').'_rate_ua_limit:';
            //白名单
            //$ua_info = $this->getSetValByKey('ua_white_list') ?? '';
            //$ua_list = BanWhiteList::query()->where("type" , 2)->where("status" , 1)->pluck('ban_str')->toArray();
//            $banStrs = BanWhiteList::query()->where("type", 1)
//                                   ->where("status", 1)
//                                   ->pluck('ban_str')
//                                   ->toArray();
//            $whiteIplist = implode("\n", $banStrs);
//            //ip白名单验证
//            $checkRes = $this->isIpAllowed($ip, $whiteIplist);
            $checkRes = false; //需求改动, 白名单已经在上面验证了, 所以暂时关闭
            if (!$checkRes) {
                $slidingWindowRateLimiter = new SlidingWindowRateLimiter(
                    $req_ua_window_time, $req_ua_limit, $ua_expire_time, $cache_prefix_key
                );
                $slidingWindowRateLimiter->appendExpireTime = $append_ua_expire_time;
                foreach ($header['user-agent'] as $forUserAgent) {
                    $reqKey = $forUserAgent.":".$routeUril;
                    //$reqKey = $forUserAgent;
                    $res = $slidingWindowRateLimiter->slideIsAllowedPlus($reqKey);
                    if (!$res) {
                        //添加封禁日志
                        $cacheKey = $cache_prefix_key.$reqKey;
                        $banKey = $cacheKey.":ban";
                        $ban_time = Redis::ttl($banKey);
                        $ban_cnt_key = $cacheKey.":banCount";
                        $ban_cnt = Redis::get($ban_cnt_key);
                        $header = request()->header();
                        $user_agent = $header['user-agent'] ?? [];
                        $data = [
                            'ip'         => $real_ip,
                            'ua_info'    => implode("\n", $user_agent),
                            'ban_time'   => $ban_time,
                            'ban_cnt'    => $ban_cnt,
                            'route'      => $routeUril,
                            'created_at' => time(),
                            'updated_at' => time(),
                        ];
                        (new ReqLogService())->addReqLog($data);
                        http_response_code(403);
                        ReturnJson(false, '请稍后重试1');
                    }
                }
            }
        }
    }

    /**
     *
     */
    private function signCheck() {
        //$setKeyPrefix = env('APP_NAME').':';
        //值为1开启,默认关闭  接口安全检查
        //$is_open_check_key = $setKeyPrefix.'is_open_check_security';
        //$is_open_check_security = Redis::get($is_open_check_key) ?? 0;
        $is_open_check_security = $this->getSetValByKey('is_open_check_security');
        if ($is_open_check_security > 0) {
            //$white_ip_securty_check_key = $setKeyPrefix.'white_ip_security_check';
            //$securityCheckWhiteIps = Redis::get($white_ip_securty_check_key) ?? '';
//            $securityCheckWhiteIps = $this->getSetValByKey('white_ip_security_check') ?? '';
//            $checkRes = $this->isIpAllowed(request()->ip(), $securityCheckWhiteIps);
//            if (!$checkRes) {
                $this->securityCheck();
//            }
        }
    }

    private function getSetValByKey($setKey) {
        if (in_array($setKey, ['is_open_limit_req', 'is_open_ua_limit_req', 'is_open_check_security'])) {
            $valueFiled = 'hidden';
        } else {
            $valueFiled = 'value';
        }
        $val = SystemValue::query()->where("key", $setKey)->value($valueFiled);

        return $val;
        //todo  缓存需要维护, 先暂时读数据库, 后续再优化
        $setKeyPrefix = env('APP_NAME').':';
        $cache_key = $setKeyPrefix.$setKey;
        if (Redis::EXISTS($cache_key)) {
            return Redis::get($cache_key);
        } else {
            if (in_array($setKey, [])) {
                $valueFiled = 'hidden';
            } else {
                $valueFiled = 'value';
            }
            $val = SystemValue::query()->where("key", $setKey)->value($valueFiled);
            Redis::set($cache_key, $val);

            return $val;
        }
    }

    /**
     *
     * @param string $setKeyPrefix
     * @param string $ip
     *
     */
    private function ipRateLimit() {
        $ip = get_client_ip();
        $real_ip = $ip;
        //值为1开启,默认关闭,  接口限流策略
        $is_open_limit_req = $this->getSetValByKey('is_open_limit_req') ?? 0;
        if ($is_open_limit_req > 0) {
            //ip封禁验证
            $route = request()->route();
            $routeUril = '';
            if (!empty($route->uri)) {
                $routeUril = $route->uri;
            }
            //获取封禁配置
            $windowsTime = $this->getSetValByKey('window_time') ?? 5;
            $reqLimit = $this->getSetValByKey('req_limit') ?? 10;
            $expireTime = $this->getSetValByKey('expire_time') ?? 60;
            $append_expire_time = $this->getSetValByKey('append_expire_time') ?? 60;
            //多段IP
            $afterIp = explode(".", $ip);
            $ban_ip_level = $this->getSetValByKey('ban_ip_level') ?? 0;
            if (in_array($ban_ip_level, [1, 2, 3])) {
                //获取当前ip的请求次数
                for ($i = 4 - $ban_ip_level; $i < 4; $i++) {
                    $afterIp[$i] = '*';
                }
                $ip = implode('.', $afterIp);
            }
            $ipCacheKey = $ip.':'.$routeUril;
            $prefix = env('APP_NAME').'_rate_limit:';
            $slidingWindowRateLimiter = new SlidingWindowRateLimiter($windowsTime, $reqLimit, $expireTime, $prefix);
            $slidingWindowRateLimiter->appendExpireTime = $append_expire_time;
            $res = $slidingWindowRateLimiter->slideIsAllowedPlus(
                $ipCacheKey
            );
            //$res = (new SlidingWindowRateLimiter($windowsTime, $reqLimit, $expireTime))->simpleIsAllowed($ipCacheKey);
            if (!$res) {
                $reqKey = $prefix.$ipCacheKey;
                $banKey = $reqKey.":ban";
                $ban_time = Redis::ttl($banKey);
                $ban_cnt_key = $reqKey.":banCount";
                $ban_cnt = Redis::get($ban_cnt_key);
                $header = request()->header();
                $user_agent = $header['user-agent'] ?? [];
                $data = [
                    'ip'         => $real_ip,
                    'muti_ip'    => $ip,
                    'ua_header'  => implode("\n", $user_agent),
                    'ban_time'   => $ban_time,
                    'ban_cnt'    => $ban_cnt,
                    'route'      => $routeUril,
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
                //添加封禁日志
                (new IpBanLogService())->addIpBanLog($data);
                http_response_code(429);
                ReturnJson(false, '请求频率过快~');
            }
        }
    }

    public function accessLog() {
        $header = request()->header();
        $ua_info = $header['user-agent'];
        $route = request()->route();
        $routeUril = '';
        if (!empty($route->uri)) {
            $routeUril = $route->uri;
        }
        $input = request()->input();
        $url_view = $input['url'] ?? '';
        if ($routeUril == 'api/product/description') {
            $url_id = $input['product_id'] ?? '';
            $routeUril = "/reports/{$url_id}/{$url_view}";
        } elseif ($routeUril == 'api/news/view') {
            $url_id = $input['id'] ?? '';
            $routeUril = "/news/{$url_id}/{$url_view}";
        } elseif ($routeUril == 'api/information/view') {
            $url_id = $input['id'] ?? '';
            $routeUril = "/information/{$url_id}/{$url_view}";
        } else {
            return true;
        }
        $ip = get_client_ip();
        //ip转换地址
        $ipAddr = (new IPAddrService($ip))->getAddrStrByIp();
        $afterIp = explode(".", $ip);
        if(!empty($afterIp) && is_array($afterIp)) {
            $ip_muti_second = $afterIp[0].".".$afterIp[1];
            $ip_muti_third = $afterIp[0].".".$afterIp[1].".".$afterIp[2];
        }else{
            $ip_muti_second = '';
            $ip_muti_third = '';
        }
        $addData = [];
        $addData['ip'] = $ip;
        $addData['ip_muti_second'] = $ip_muti_second;
        $addData['ip_muti_third'] = $ip_muti_third;
        //$contentLength = $_SERVER['CONTENT_LENGTH']; bytes
        //优点:可以获取 POST 请求的请求体大小。
        //缺点:只能获取 POST 请求的数据，无法统计整个页面的流量。
        if(empty($_SERVER['CONTENT_LENGTH'] ) && $_SERVER['CONTENT_LENGTH'] <= 0){
            $addData['content_size'] = 0;
        }else{
            $addData['content_size'] = $_SERVER['CONTENT_LENGTH'];
        }

        $addData['ip_addr'] = $ipAddr;
        $addData['route'] = $routeUril;
        $addData['ua_info'] = implode("\n", $ua_info);
        $addData['referer'] = $_SERVER['HTTP_REFERER'] ?? '';
        $addData['log_time'] = time();
        $addData['log_date'] = date('Y-m-d');
        AccessLog::create($addData);
    }

    /**
     * 白名单校验
     *
     * @return bool
     */
    private function checkWhiteIp(): bool {
        $banStrs = BanWhiteList::query()->where("type", 1)->where("status", 1)->pluck('ban_str')->toArray();
        $banIpList = [];
        foreach ($banStrs as $banjsonStr) {
            $forBanIpList = @json_decode($banjsonStr, true);
            if (!empty($forBanIpList) && is_array($forBanIpList)) {
                $banIpList = array_merge($banIpList, $forBanIpList);
            }
        }
        $whiteIplist = implode("\n", $banIpList);
        //ip白名单验证
        $ip = get_client_ip();
        $checkRes = $this->isIpAllowed($ip, $whiteIplist);

        return $checkRes;
    }
}
