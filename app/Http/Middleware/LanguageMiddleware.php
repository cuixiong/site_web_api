<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class LanguageMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next) {
        try {
            // 当前语言
            $language = $request->header('Langaue');
            if (empty($language)) {
                $acceptLanguage = $request->header('Accept-Language');
                if (!empty($acceptLanguage)) {
                    if (strpos($acceptLanguage, 'zh') !== false) {
                        $language = 'zh';
                    } else {
                        $language = 'en';
                    }
                }
            }
            // 语言全局变量
            $request->HeaderLanguage = $language ?: 'en';
            // 设置当前语言
            App::setlocale($request->HeaderLanguage);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                                        'code'    => 'B001',
                                        'message' => $e->getMessage(),
                                    ]);
        }
    }
}
