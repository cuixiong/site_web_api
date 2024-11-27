<?php

namespace App\Console\Commands;

use App\Models\AccessLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ClearAccessLog extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:clear_access_log';

    public function handle() {
        $srartTimeCoon = Carbon::now()->subDays(7);
        $endTimeCoon = Carbon::now();
        $start_time = $srartTimeCoon->getTimestamp();
        $end_time = $endTimeCoon->getTimestamp();
        $accessLogModel = new AccessLog();
        $cnt = $accessLogModel::query()->whereBetween('created_at', [$start_time, $end_time])->count();
        if($cnt > 0){
            $accessLogModel::query()->whereBetween('created_at', [$start_time, $end_time])->delete();
        }
        echo "本周清除访问日志记录数：".$cnt.PHP_EOL;
    }


}
