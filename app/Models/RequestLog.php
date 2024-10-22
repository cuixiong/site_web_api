<?php
/**
 * RequestLog.php UTF-8
 * 请求日志
 *
 * @date    : 2024/10/15 15:10 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Models;

use App\Models\Base;

class RequestLog extends Base {
    protected $table = 'request_log';
    // 设置允许入库字段,数组形式
    protected $fillable = ['id', 'ip', 'ip_addr', 'ua_info', 'ban_time', 'ban_cnt', 'route', 'header', 'sort', 'status'];
}
