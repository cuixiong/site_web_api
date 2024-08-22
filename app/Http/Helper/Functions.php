<?php
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
    header('Content-Type: application/json');// 设置返回类型
    $code = ($code === true) ? "200" : $code;
    $code = ($code === false) ? 'B001' : $code;
    $html = json_encode(
        [
            'code' => $code,
            'msg'  => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE
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
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;// 找不到为flase,否则为TRUE
    }
    // 判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
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
    if (isset ($_SERVER['HTTP_ACCEPT'])) { // 协议法，因为有可能不准确，放到最后判断
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false)
            && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false
                || (strpos(
                        $_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml'
                    ) < strpos(
                        $_SERVER['HTTP_ACCEPT'], 'text/html'
                    )))) {
            return true;
        }
    }

    return false;
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
    if(in_array($siteName, $siteNameLit)){
        return true;
    }
    return false;
}



