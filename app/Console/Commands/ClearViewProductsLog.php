<?php

namespace App\Console\Commands;

use App\Models\Authority;
use App\Models\Products;
use App\Models\ViewProductsLog;
use Illuminate\Console\Command;

class ClearViewProductsLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:ViewProductsLog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * 保留30天浏览记录
     *
     * @return int
     */
    public function handle()
    {
        
        $timestamp = time()-3600*24*30;
        $result = ViewProductsLog::where('created_at', '<', $timestamp)->delete();
        echo $result;
    }
}
