<?php
/**
 * IpBanLogService.php UTF-8
 * ip禁用日志服务
 *
 * @date    : 2024/7/18 14:09 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Services;

use App\Models\IpBanLog;

class IpBanLogService {
    public function addIpBanLog($data) {
        if (empty($data['ip'])) {
            return false;
        }
        $addData = [
            'ip'         => $data['ip'],
            'muti_ip'    => $data['muti_ip'],
            'route'      => $data['route'],
            'ua_header'  => $data['ua_header'],
            'ban_time'   => $data['ban_time'],
            'ban_cnt'    => $data['ban_cnt'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at'],
        ];
        //ip转换地址
        $ipAddr = (new IPAddrService($data['ip']))->getAddrStrByIp();
        $addData['ip_addr'] = $ipAddr;

        return IpBanLog::create($addData);
    }
}
