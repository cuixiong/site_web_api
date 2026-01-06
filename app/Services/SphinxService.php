<?php
/**
 * SphinxService.php UTF-8
 * sphinx 服务方法
 *
 * @date    : 2024/6/26 9:48 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Services;

use App\Models\System;
use App\Models\SystemValue;
use Foolz\SphinxQL\Drivers\Pdo\Connection;
use Foolz\SphinxQL\SphinxQL;

class SphinxService {
    public $site      = '';
    public $sphinxKey = 'sphinxConnectInfo';


    /**
     *
     *
     * @return false|Connection
     */
    public function getConnection() {
        $systemId = System::query()->where('alias', $this->sphinxKey)->value("id");
        if (empty($systemId)) {
            return false;
        }
        $confList = SystemValue::query()->where('parent_id', $systemId)->select(['key', 'value'])->get()->toArray();
        $comParams = [];
        foreach ($confList as $conf) {
            if ($conf['key'] == 'port') {
                $comParams['port'] = $conf['value'];
            } elseif ($conf['key'] == 'host') {
                $comParams['host'] = $conf['value'];
            }
        }
        //$comParams = array('host' => '39.108.67.106', 'port' => 9306);
        $conn = new Connection();
        $conn->setParams($comParams);

        return $conn;
    }


}
