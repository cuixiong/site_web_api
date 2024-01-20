<?php

namespace App\Models;
use App\Models\Base;
use Illuminate\Support\Facades\DB;

class ProductsDescription extends Base
{

    protected $table = 'product_description';

    public function __construct($year = '')
    {
        parent::__construct();
        //年份必传、数字且为四位
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

    public function getTableName()
    {
        return $this->table;
    }

    public function saveWithAttributes($attributes)
    {

        $attributes = \Illuminate\Support\Arr::only($attributes, $this->fillable);
        $attributes['updated_at'] = time();

        return DB::table($this->table)->insert($attributes);
    }


    public function updateWithAttributes($attributes)
    {
        $attributes = \Illuminate\Support\Arr::only($attributes, $this->fillable);
        $attributes['updated_at'] = time();
        return DB::table($this->table)->where('product_id', '=', $attributes['product_id'])->update($attributes);
    }
}
