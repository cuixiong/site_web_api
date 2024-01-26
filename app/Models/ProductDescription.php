<?php

namespace App\Models;
use App\Models\Base;
class ProductDescription extends Base
{
    protected $table = 'product_description';

    public function __construct($year = '')
    {
        parent::__construct();
        // 年份必传、数字且为四位
        if (!empty($year) && is_numeric($year) && strlen($year) == 4) {
            $this->setTableName($year);
        }
    }
    //设置表名
    protected function setTableName($year = '')
    {
        $year = $year ? $year : date('Y');
        $table = 'product_description_' . $year;
        $this->table = $table;
        return $table;
    }
}
