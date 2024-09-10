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
        (new SitemapController())->CliMakeSiteMap();
        echo "生成站点地图成功".PHP_EOL;
    }


}
