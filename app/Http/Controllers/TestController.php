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
use Illuminate\Support\Facades\DB;

class TestController extends Controller {
    public function test1() {
        $connection = DB::connection('default');
        $connection->setHost('8.219.5.215');
        $connection->setDatabaseName('yadmin');
        $connection->setUsername('root');
        $connection->setPassword('9d672e87bf75c4e5');
        $list = DB::connection('default')->table('users')->get();
        dd($list);
    }

}
