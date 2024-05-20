<?php
/**
 * TestController.php UTF-8
 * 测试demo, 单元测试等
 *
 * @date    : 2024/5/20 9:29 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TestController extends Controller {
    public function test1() {
        try{
            // 定义新的数据库配置
            $newDatabaseConfig = [
                'driver' => 'mysql',
                'host' => '8.219.5.215',
                'port' => '3306',
                'database' => 'yadmin',
                'username' => 'root',
                'password' => '9d672e87bf75c4e5',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ];
            // 切换到新的数据库配置
            $mysql = "mysql";
            Config::set("database.connections.{$mysql}", $newDatabaseConfig);
            // 断开当前连接
            DB::purge($mysql);
            // 重新连接
            DB::reconnect($mysql);

            // 现在您可以使用新的数据库连接执行查询
            $list = DB::table('users')->get();

            dd($list);
        }catch (\Exception $e){
            dd($e->getMessage());
        }

    }

}
