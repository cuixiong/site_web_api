<?php

use Illuminate\Support\Facades\Redis;

/**
 * 返回JSON格式响应
 *
 * @param $code    状态码=>TRUE是200，false是-200，其他值是等于$code本身
 * @param $message 提示语
 * @param $data    需要返回的数据数组
 */
function ReturnJson($code, $message = '请求成功', $data = []) {
    header('Access-Control-Allow-Origin: *'); // 允许所有源进行跨域访问
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE'); // 允许使用的HTTP方法
    header('Content-Type: application/json'); // 设置返回类型
    $code = ($code === true) ? "200" : $code;
    $code = ($code === false) ? 'B001' : $code;
    $html = json_encode(
        [
            'code' => $code,
            'msg'  => $message,
            'data' => $data
        ],
        JSON_UNESCAPED_UNICODE
    );
    echo $html;
    exit;
}

/**
 * 判断是否是手机访问
 *
 * @return bool
 */
function isMobile() {
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false; // 找不到为flase,否则为TRUE
    }
    // 判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'mobile',
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(".implode('|', $clientkeywords).")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) { // 协议法，因为有可能不准确，放到最后判断
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false)
            && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false
                || (strpos(
                        $_SERVER['HTTP_ACCEPT'],
                        'vnd.wap.wml'
                    ) < strpos(
                        $_SERVER['HTTP_ACCEPT'],
                        'text/html'
                    )))
        ) {
            return true;
        }
    }

    return false;
}

function phpEncodeURIComponent($str) {
    $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')', '%7E' => '~');

    return strtr(rawurlencode($str), $revert);
}

function phpDecodeURIComponent($encodedStr) {
    // 将特殊字符转换回原始编码格式（反向操作）
    $replacements = array(
        '!' => '%21',
        '*' => '%2A',
        "'" => '%27',
        '(' => '%28',
        ')' => '%29',
        '~' => '%7E'
    );
    // 替换特殊字符为编码后的形式
    $strWithEncodedChars = strtr($encodedStr, $replacements);

    return rawurldecode($strWithEncodedChars);
}

/**
 * 判断是否是微信访问
 *
 * @return bool
 */
function isWeixin() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    }

    return false;
}

function checkSiteAccessData($siteNameLit) {
    $siteName = request()->header('Site');
    if (in_array($siteName, $siteNameLit)) {
        return true;
    }

    return false;
}

function get_client_ip() {
    $header = request()->header();
    $ip = $header['client-ip'] ?? '';
    if (empty($ip)) {
        $ip = $header['x-forwarded-for'] ?? '';
    }
    if (empty($ip)) {
        $ip = request()->ip();
    }
    if (!empty($ip) && is_array($ip)) {
        $ip = array_shift($ip);
    }

    return $ip;
}

function get_real_client_ip() {
    $headers = [
        'REMOTE_ADDR',                // 直接连接
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_REAL_IP',            // Nginx等反向代理
        'HTTP_CLIENT_IP',            // 代理服务器
        // 'HTTP_X_FORWARDED_FOR',   // 暂时禁用，这是导致问题的原因
        'HTTP_X_FORWARDED',          // 代理
        'HTTP_X_CLUSTER_CLIENT_IP',  // 集群
        'HTTP_FORWARDED_FOR',        // 标准转发
        'HTTP_FORWARDED'            // RFC 7239
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // 处理多个IP的情况（逗号分隔）
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // 验证IP格式
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    // 回退到Laravel的方法
    return request()->ip();
}

/**
 * 接口请求频率限制
 */
function currentLimit($request, $second = 10, $site = '', $userId = '') {
    $route = $request->route();
    $actionInfo = $route->getAction();
    $currentLimitKey = $actionInfo['controller'];
    if (empty($site)) {
        $site = $request->header('Site');
    }
    $currentLimitKey = $currentLimitKey."_{$site}_{$userId}";
    $isExist = Redis::get($currentLimitKey);
    if (!empty($isExist)) {
        $err_msg = '频繁请求';
        if (checkSiteAccessData(['mrrs', 'yhen', 'qyen', 'mmgen', 'lpien', 'giren'])) {
            $err_msg = 'Frequent requests';
        } elseif (checkSiteAccessData(['lpijp', 'qycojp'])) {
            $err_msg = '頻繁なリクエスト';
        }
        ReturnJson(false, $err_msg);
    }
    Redis::setex($currentLimitKey, $second, 1);
}

function setHeaderRobotsTag() {
    if (checkSiteAccessData(['qyen'])) {
        header('X-Robots-Tag:noindex'); // 设置返回类型
    }
}
