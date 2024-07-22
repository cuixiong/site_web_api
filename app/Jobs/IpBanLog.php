<?php
/**
 * IpBanLog.php UTF-8
 * 添加封禁日志队列
 *
 * @date    : 2024/7/18 14:08 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class IpBanLog implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable;

    public $data = '';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        echo "开始".PHP_EOL;
        try {
            $data = json_decode($this->data, true);
            $class = $data['class'];
            $method = $data['method'];
            $instance = new $class();
            call_user_func([$instance, $method],$data);
        } catch (\Exception $e) {
            $errData = [
                'data'  => $this->data,
                'error' => $e->getMessage(),
            ];
            \Log::error('添加封禁IP失败--错误信息与数据:'.json_encode($errData));
        }

        return true;
    }
}
