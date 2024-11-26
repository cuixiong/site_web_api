<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccessLog extends Base {
    protected $table = 'access_log';
    // 设置允许入库字段,数组形式
    protected $fillable
        = [
            'id', 'ip', 'ip_muti_second', 'ip_muti_third', 'content_size', 'ip_addr', 'route', 'ua_info', 'referer', 'log_time', 'log_date', 'sort', 'status'
        ];

//    public function __construct($log_date = '') {
//        parent::__construct();
//        if(empty($log_date )){
//            $log_date = date('Ym');
//        }
//        if (!empty($log_date) && is_numeric($log_date)) {
//            $this->setTableName($log_date);
//            $this->checkAndCreateTable();
//        }
//    }

    //设置表名
    protected function setTableName($log_date = '') {
        $table = 'access_log_'.$log_date;
        $this->table = $table;

        return $table;
    }

    //查询是否存在表否则新建
    private function checkAndCreateTable() {
        if (!Schema::hasTable($this->table)) {
            $this->createTable();
        }
    }

    //数据库新建表
    private function createTable() {
        // 显式启动事务
        // CREATE TABLE会引起隐式提交事务，导致新增修改报告事务失效报错There is no active transaction
        // https://qa.1r1g.com/sf/ask/4703871091/
        // https://dev.mysql.com/doc/refman/8.0/en/implicit-commit.html
        DB::beginTransaction();
        $res = DB::select("SHOW CREATE TABLE `access_log` ");
        $array = get_object_vars($res[0]);
        $createTableStatement = '';
        foreach ($array as $key => $value) {
            if ($key == 'Create Table') {
                $createTableStatement = $value;
            }
        }
        $createTableStatement = str_replace('access_log', $this->table, $createTableStatement);
        DB::unprepared($createTableStatement);
    }

    public function getTableName() {
        return $this->table;
    }


}
