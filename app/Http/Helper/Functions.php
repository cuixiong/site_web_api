<?php
/** 
 * 返回JSON格式响应
 * @param $code 状态码=>TRUE是200，false是-200，其他值是等于$code本身
 * @param $message 提示语
 * @param $data 需要返回的数据数组
 */
function ReturnJson($code,$message = '请求成功',$data = []){
    header('Access-Control-Allow-Origin: *'); // 允许所有源进行跨域访问
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE'); // 允许使用的HTTP方法
    header('Access-Control-Max-Age: 86400'); // 最大缓存时间（单位为秒）
    header('Access-Control-Allow-Headers: Content-Type, Authorization'); // 允许自定义的HTTP头部字段
    $code = ($code === TRUE) ? "200" : $code;
    $code = ($code === FALSE) ? 'B001' : $code;
    echo json_encode(
        [
            'code' => $code,
            'msg' => $message,
            'data' => $data
        ]);
    exit;
}
