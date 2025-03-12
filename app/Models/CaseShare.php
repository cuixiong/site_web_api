<?php
/**
 * CaseShare.php UTF-8
 * 案例分享
 *
 * @date    : 2025/3/11 10:12 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Models;


class CaseShare extends Base {
    protected $table = 'case_share';
    // 设置允许入库字段,数组形式
    protected $fillable = ['id', 'product_id', 'name', 'product_name_suffix', 'path', 'sort', 'status'];
}
