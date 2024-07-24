<?php
namespace App\Services;
use Illuminate\Support\Facades\Redis;

class SlidingWindowRateLimiter {
    private $windowSize;
    private $limit;
    private $prefix;
    private $expireTime;

    public function __construct($windowSize, $limit, $expireTime, $prefix = 'rate_limit:') {
        $this->windowSize = $windowSize; // 窗口大小，单位秒
        $this->limit = $limit; // 最大请求数
        $this->prefix = $prefix; // Redis 键前缀
        $this->expireTime = $expireTime;
    }

    /**
     * 滑动校验
     * @param $key
     * @return bool
     */
    public function slideIsAllowed($key) {
        $currentTimestamp = microtime(true);
        $uniqueId = $currentTimestamp . '-' . uniqid();
        $key = $this->prefix.$key;
        $luaScript = '
            local key = KEYS[1]
            local currentTimestamp = tonumber(ARGV[1])
            local windowSize = tonumber(ARGV[2])
            local maxRequests = tonumber(ARGV[3])
            local uniqueId = ARGV[4]
            local ttl = tonumber(ARGV[5])

            -- 删除窗口外的请求记录
            redis.call("ZREMRANGEBYSCORE", key, 0, currentTimestamp - windowSize)
            -- 添加当前请求计数
            redis.call("ZADD", key, currentTimestamp, uniqueId)
            -- 设置键的过期时间
            redis.call("EXPIRE", key, ttl)
            -- 获取当前窗口内的请求数
            local requestCount = redis.call("ZCARD", key)

            if requestCount <= maxRequests then
                return 1
            else
                return 0
            end
        ';

        $result = Redis::eval($luaScript, 1, $key, $currentTimestamp, $this->windowSize, $this->limit, $uniqueId, $this->expireTime);

        return $result == 1;
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


