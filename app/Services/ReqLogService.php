<?php
/**
 * ReqLogService.php UTF-8
 * 请求日志服务
 *
 * @date    : 2024/10/15 15:26 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Services;

use App\Models\RequestLog;

class ReqLogService {
    public function addReqLog($data) {

        $addData = $data;
        $ip = $data['ip'];
        //ip转换地址
        $ipAddr = (new IPAddrService($ip))->getAddrStrByIp();
        $addData['ip_addr'] = $ipAddr;

        return RequestLog::create($addData);
    }
}
