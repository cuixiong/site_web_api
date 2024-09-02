<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use GrahamCampbell\ResultType\Success;
use Illuminate\Console\Command;

class SiteMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成或更新网站地图';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new SitemapController)
            ->clearMap()
            ->sitemapMenus()
            ->sitemapNews()
            ->sitemapHotInfo()
            ->sitemapProducts()
            ->sitemapMain()
            ->sitemapCategory();
        return 'Success';
    }
}
