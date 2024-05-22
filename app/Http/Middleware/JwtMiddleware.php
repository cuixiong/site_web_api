<?php
namespace App\Http\Middleware;
use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Modules\Admin\Http\Models\Role;
class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {  //获取到用户数据，并赋值给$user
                return response()->json([
                    'code' => 'B001',
                    'message' => '账号不存在'
                ], 404);
            }
            // 将用户信息存储在请求中，以便后续使用
            $request->user = $user;
            return $next($request);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'code' => 80001,
                'message' => 'token 过期' , //token已过期
            ]);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'code' => 80001,
                'message' => 'token 无效',  //token无效
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'code' => 80001,
                'message' => '缺少token' , //token为空
            ]);
        }
    }
}
