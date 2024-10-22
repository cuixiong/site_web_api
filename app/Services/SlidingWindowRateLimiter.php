<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Redis;

class SlidingWindowRateLimiter {
    private $windowSize;
    private $limit;
    public  $prefix;
    private $expireTime;
    public  $appendExpireTime = 5;

    public function __construct($windowSize, $limit, $expireTime, $prefix = 'rate_limit:') {
        $this->windowSize = $windowSize; // 窗口大小，单位秒
        $this->limit = $limit; // 最大请求数
        $this->prefix = $prefix; // Redis 键前缀
        $this->expireTime = $expireTime;
    }

    /**
     * 滑动校验
     *
     * @param $key
     *
     * @return bool
     */
    public function slideIsAllowed($key) {
        $currentTimestamp = microtime(true);
        $uniqueId = $currentTimestamp.'-'.uniqid();
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
        $result = Redis::eval(
            $luaScript, 1, $key, $currentTimestamp, $this->windowSize, $this->limit, $uniqueId, $this->expireTime
        );

        return $result == 1;
    }

    public function slideIsAllowedPlus($key) {
        $luaScript = '
            local key = KEYS[1]
            local currentTimestamp = tonumber(ARGV[1])
            local windowSize = tonumber(ARGV[2])
            local maxRequests = tonumber(ARGV[3])
            local initialBanTime = tonumber(ARGV[4])
            local additionalBanTime = tonumber(ARGV[5])
            local maxBanTime = 86400  -- 最大封禁时间（24小时）
            local ttl = tonumber(ARGV[6])
            local uniqueId = ARGV[7]
            local banKey = key .. ":ban"
            if redis.call("EXISTS", banKey) == 1 then
                return 0
            end
            -- 删除窗口外的请求记录
            redis.call("ZREMRANGEBYSCORE", key, 0, currentTimestamp - windowSize)
            -- 添加当前请求的时间戳到有序集合
            redis.call("ZADD", key, currentTimestamp, uniqueId)
            -- 设置键的过期时间
            redis.call("EXPIRE", key, ttl)

            local requestCount = redis.call("ZCARD", key)

            if requestCount > maxRequests then
                local banCount = tonumber(redis.call("GET", key .. ":banCount") or 0) + 1  -- 递增封禁次数
                local banTime = initialBanTime + (banCount - 1) * additionalBanTime
                banTime = math.min(banTime, maxBanTime)

                -- 设置封禁状态
                redis.call("SETEX", banKey, banTime, banCount)

                -- 仅当封禁次数为1时，设置封禁次数的过期时间为24小时
                if banCount == 1 then
                    redis.call("SETEX", key .. ":banCount", 86400, banCount)  -- 设置封禁次数，过期时间24小时
                else
                     redis.call("SET", key .. ":banCount", banCount)  -- 其他情况下只更新次数，不设置过期时间
                end

                return 0
            else
                return 1
            end
         ';
        $reqkey = $this->prefix.$key;
        $currentTimestamp = time();
        $windowSize = $this->windowSize; // 60秒窗口
        $maxRequests = $this->limit; // 每个窗口允许的最大请求数
        $initialTtl = $this->expireTime; // 初始过期时间
        $appendExpireTime = $this->appendExpireTime;  // 追加的封禁时间
        $ttl = 3600;  // 键的过期时间（1小时）
        $uniqueId = $currentTimestamp.'-'.uniqid();
        $result = Redis::eval(
            $luaScript, 1, $reqkey, $currentTimestamp, $windowSize, $maxRequests, $initialTtl,
            $appendExpireTime , $ttl, $uniqueId
        );

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


