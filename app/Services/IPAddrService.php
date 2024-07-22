<?php
/**
 * IPAddrService.php UTF-8
 * 获取IP 归属地
 *
 * @date    : 2024/7/19 16:59 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Services;
class IPAddrService {
    public $ip = '';

    public function __construct($ip) {
        $this->ip = $ip;
    }

    public function getAddrStrByIp() {
        $ipdb = new \PhpCpm\IpAddress\IpRegion();
        $driver = \PhpCpm\IpAddress\drivers\Ip2region::class;
        $ipAddrObj = $ipdb->drvier($driver)->init($this->ip)->getMap();
        if(empty($ipAddrObj )){
            return '';
        }

        $ipAddr = $ipAddrObj->country ?? '';
        if (!empty($ipAddrObj->province) && $ipAddrObj->province != '-') {
            $ipAddr = $ipAddr."-".$ipAddrObj->province;
        }

        if(empty($ipAddr ) && !empty($ipAddrObj->city)){
            $ipAddr = $ipAddrObj->city;
        }elseif(!empty($ipAddrObj->isp )){
            $ipAddr = $ipAddr."-".$ipAddrObj->isp;
        }

        return $ipAddr;
    }
}

