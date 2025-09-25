<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;

class MakeSiteMap extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:make_site_map';

    public function handle() {
        echo "执行站点地图, 执行结果:".PHP_EOL;
       (new SitemapController())->CliMakeSiteMap();
    }
}
