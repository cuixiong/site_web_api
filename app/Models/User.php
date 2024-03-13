<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
class User extends Authenticatable implements JWTSubject
{
    // 时间戳
    protected $dateFormat = 'U';
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * 返回用户登陆状态和用户信息
     */
    public static function IsLogin()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (! $user) {  //获取到用户数据，并赋值给$user
                return NULL;
            }
            return $user;
        } catch (TokenExpiredException $e) {
            return NULL;
        } catch (TokenInvalidException $e) {
            return NULL;
        } catch (JWTException $e) {
            return NULL;
        }
    }
}