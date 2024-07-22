<?php
namespace App\Services;
use Illuminate\Support\Facades\Redis;

class SlidingWindowRateLimiter {
    private $windowSize;
    private $limit;
    private $prefix;

    public function __construct($windowSize, $limit, $prefix = 'rate_limit:') {
        $this->windowSize = $windowSize; // 窗口大小，单位秒
        $this->limit = $limit; // 最大请求数
        $this->prefix = $prefix; // Redis 键前缀
    }

    /**
     * 滑动校验
     * @param $key
     * @return bool
     */
    public function slideIsAllowed($key) {
        $currentTimestamp = time();
        $key = $this->prefix . $key;
        $windowStartTimestamp = $currentTimestamp - $this->windowSize;

        // 启用 Redis 事务
        Redis::multi();

        // 移除窗口外的请求计数
        Redis::zRemRangeByScore($key, 0, $windowStartTimestamp);
        // 获取窗口内的请求计数
        Redis::zCard($key);
        // 添加当前请求计数
        Redis::zAdd($key, $currentTimestamp, $currentTimestamp);
        // 设置键的过期时间为窗口大小
        Redis::expire($key, $this->windowSize);

        // 执行事务
        $results = Redis::exec();
        //\Log::error('返回结果数据$results:'.json_encode([$results]));
        // 获取当前窗口内的请求计数
        $currentCount = $results[1];

        // 判断是否超过限流阈值
        return $currentCount <= $this->limit;
    }

    //简单校验
    public function simpleIsAllowed($ip) {
        $redisKey = "check_req_limit_".$ip;
        if (!empty(Redis::get($redisKey))) {
            //如果增加次数大于限制次数
            if (Redis::incrby($redisKey, 1) > $this->limit) {
                // 超过限制次数
                return false;
            }
        } else {
            //首次进来,  增加访问量, 设置过期时间
            Redis::incrby($redisKey, 1);
            Redis::expire($redisKey, $this->windowSize);
        }
        return true;
    }



}


