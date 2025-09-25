<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicController extends Controller {

    public function getClientIp(Request $request) {
        // 设置强制断开连接的响应头
        return response()->json([
                                    'ip'        => get_real_client_ip(),
                                    'timestamp' => time(),
                                    'random'    => uniqid() // 防止缓存
                                ])
                         ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                         ->header('Pragma', 'no-cache')
                         ->header('Expires', '0')
                         ->header('Connection', 'close')  // 强制关闭连接
                         ->header('Keep-Alive', 'timeout=0, max=0'); // 禁用Keep-Alive
    }

    /**
     * 强制刷新IP
     */
    public function forceRefreshIp(Request $request) {
        // 添加更多随机因素，确保不会被缓存
        $randomId = uniqid() . '_' . mt_rand(1000, 9999);
        // 创建响应
        $response = response()->json([
            'ip'        => get_real_client_ip(),
            'timestamp' => time(),
            'random_id' => $randomId,
            'headers'   => $this->getDebugHeaders($request),
            'protocol'  => $_SERVER['SERVER_PROTOCOL'] ?? 'unknown',
            'http_version' => $request->server('HTTP_VERSION', 'unknown')
        ])
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
        ->header('Pragma', 'no-cache')
        ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
        ->header('Connection', 'close')
        ->header('Keep-Alive', 'timeout=0, max=0')
        ->header('Vary', 'User-Agent, Accept-Encoding')
        ->header('X-Accel-Expires', '0')
        ->header('X-Force-Close', '1'); // 自定义标识


        return $response;
    }

    /**
     * 获取调试头部信息
     */
    private function getDebugHeaders(Request $request) {
        return [
            'user_agent' => $request->header('User-Agent'),
            'real_ip' => get_real_client_ip(),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'x_real_ip' => $request->header('X-Real-IP'),
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
    }
}
