<?php
/**
 * IpBanLog.php UTF-8
 * 封禁IP日志
 *
 * @date    : 2024/7/18 13:53 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */
namespace App\Models;

use App\Models\Base;

class IpBanLog extends Base {
    protected $table = 'ip_ban_log';
    // 设置允许入库字段,数组形式
    protected $fillable = ['id', 'ip', 'ip_addr', 'route', 'sort', 'status'];
}
