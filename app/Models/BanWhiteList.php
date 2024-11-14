<?php
/**
 * BanWhiteList.php UTF-8
 * 封禁白名单列表
 *
 * @date    : 2024/10/24 16:07 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Models;

use App\Models\Base;

class BanWhiteList extends Base {
    protected $table = 'ban_white_list';
    // 设置允许入库字段,数组形式
    protected $fillable = ['id', 'type', 'ban_str', 'remark', 'status', 'sort'];
}
