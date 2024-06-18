<?php

namespace App\Console\Commands;

use App\Models\Information;
use App\Models\News;
use App\Models\ProductDescription;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\XunsearchProductIndex;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SyncDataCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:syncdata {o} {--pageNum=}';

    public function handle() {
        $task = $this->argument('o');
        $option = $this->option();
        if ($task == 'sync_category') {
            $this->syncCategory($option);
        } elseif ($task == 'sync_product') {
            $this->syncProduct($option);
        } elseif ($task == 'sync_news') {
            $this->syncNews($option);
        }
    }

    public function syncNews($option) {
        ini_set('memory_limit', '1024M');
        $this->useRemoteDb();
        $list = DB::table('news')->get()->toArray();
        $infoMationList = [];
        $newsList = [];
        $i = 0;
        foreach ($list as $for_info) {
            $data = [
                'id'          => $for_info->id,
                'category_id' => $for_info->industry_id,
                'type'        => $for_info->category_id,
                'title'       => $for_info->title,
                'keywords'    => $for_info->keyword,
                'thumb'       => $for_info->thumb,
                'description' => $for_info->description,
                'content'     => $for_info->content,
                'url'         => $for_info->url,
                'hits'        => $for_info->hits,
                'status'      => $for_info->status,
                'sort'        => $for_info->order,
                'tags'        => $for_info->tags,
                'created_by'  => 0,
                'created_at'  => $for_info->created_at,
                'updated_by'  => 0,
                'updated_at'  => $for_info->updated_at,
                'upload_at'   => $for_info->release_at,
            ];
            $i++;
            echo "已组装{$i}条数据".PHP_EOL;
            if ($for_info->category_id == 1) {
                //行业新闻
                $newsList[] = $data;
            } else {
                //热点资讯
                $infoMationList[] = $data;
            }
        }
        //切换本地数据库
        $this->uslocalDb();
        foreach ($newsList as $news) {
            News::insert($news);
        }
        foreach ($infoMationList as $infomat) {
            Information::insert($infomat);
        }
        echo "ok".PHP_EOL;
    }

    /**
     * 同步报告
     *
     * @param $option
     *
     */
    public function syncProduct($option) {
        ini_set('memory_limit', '2048M');
        $pageNum = $option['pageNum'];
//        $pageSize = 1000;
//        $offset = ($pageNum - 1) * $pageSize;
        $this->useRemoteDb();
        $q_start = microtime(true);
        //只查询发布时间2024年的数据
        $list = DB::table('product_routine')
                  ->where("published_date", ">=", 1672502400)
                  ->where("published_date", "<", 1704038400)
//                  ->offset($offset)
//                  ->limit($pageSize)
                  ->get()
                  ->toArray();
//        $q_end = microtime(true);
//        var_dump("开始时间:" . $q_start . " 结束时间:" . $q_end . " 耗时:" . ($q_end - $q_start) . "秒");
//        die;
        $productList = [];
        $index = 0;
        //切换本地数据库
        $this->uslocalDb();
        foreach ($list as $key => $value) {
            $published_date = $value->published_date ?? 0;
            $year = date("Y", $published_date);
            $data = [
                'id'                  => $value->id,
                'name'                => $value->name,
                'english_name'        => $value->english_name,
                'thumb'               => '',
                'publisher_id'        => 0,
                'category_id'         => $value->category_id,
                'country_id'          => $value->country_id,
                'price'               => $value->price,
                'keywords'            => $value->keyword,
                'url'                 => $value->url,
                'published_date'      => $published_date,
                'status'              => $value->status,
                'author'              => $value->author,
                //'discount'            => $value->id,
                'discount_amount'     => $value->discount_value,
                'discount_type'       => $value->discount_type,
                'discount_time_begin' => $value->discount_begin,
                'discount_time_end'   => $value->discount_end,
                'pages'               => $value->pages,
                'tables'              => $value->tables,
                'sort'                => $value->order,
                'downloads'           => $value->downloads,
                'year'                => $year,
            ];
            $index += 1;
            Products::insert($data);
            echo "已处理{$index}条数据".PHP_EOL;
//            $productList[] = $data;
//            if($index % 100 == 0){
//                Products::insert($productList);
//                $productList = [];
//            }
//            $pdInfo = (new ProductDescription($year))->where("product_id", $value->id)->first();
//            $data['pd_desc'] = [
//                'product_id'            => $value->id,
//                'description'           => $pdInfo->description,
//                'table_of_content'      => $pdInfo->table_of_content,
//                'tables_and_figures'    => $pdInfo->tables_and_figures,
//                'companies_mentioned'   => $pdInfo->companies_mentioned,
//                'description_en'        => $pdInfo->description_en,
//                'table_of_content_en'   => $pdInfo->table_of_content_en,
//                'tables_and_figures_en' => $pdInfo->tables_and_figures_en,
//            ];
        }
//        foreach ($productList as $productinfo) {
////            $pd_desc = $productinfo['pd_desc'];
////            unset($productinfo['pd_desc']);
////            $year = $productinfo['year'];
//            $re = (new Products())->insert($productinfo);
//            if ($re) {
//                //(new ProductDescription($year))->insert($pd_desc);
//                $index++;
//                echo "已成功处理{$index}条数据".PHP_EOL;
//            }
//        }
        echo "OK".PHP_EOL;
    }

    public function syncCategory($option) {
        $mysql = $this->useRemoteDb();
        // 现在您可以使用新的数据库连接执行查询
        $list = DB::table('product_category')->get()->toArray();
        $add_category = [];
        foreach ($list as $key => $value) {
            $add_category[] = [
                'id'                  => $value->id,
                'name'                => $value->name,
                'pid'                 => 0,
                'link'                => $value->link,
                'thumb'               => $value->thumb,
                'home_thumb'          => $value->thumb,
                'icon'                => $value->icon,
                'sort'                => $value->order,
                'status'              => $value->status,
                'is_recommend'        => 0,
                'show_home'           => 1,
                'discount_amount'     => $value->discount_value,
                'discount_type'       => $value->discount_type,
                'discount_time_begin' => $value->discount_begin,
                'discount_time_end'   => $value->discount_end,
                'seo_title'           => $value->seo_title,
                'seo_keyword'         => $value->seo_keyword,
                'seo_description'     => $value->seo_description,
                'email'               => '',
                'keyword_suffix'      => $value->keyword_suffix,
                'product_tag'         => $value->product_tag,
            ];
        }
        //切换本地数据库
        $this->uslocalDb();
        $res = ProductsCategory::insert($add_category);
        var_dump($res);
    }

    /**
     *
     *
     * @return string
     */
    private function useRemoteDb(): string {
        // 定义新的数据库配置
        $newDatabaseConfig = [
            'driver'    => 'mysql',
            'host'      => 'rm-wz9a0f5nwrgw487b9ro.mysql.rds.aliyuncs.com',
            'port'      => '3306',
            'database'  => 'mmg_cn',
            'username'  => 'report168',
            'password'  => '#Fh7D4@Qr*3B&S4AE',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ];
        // 切换到新的数据库配置
        $mysql = "mysql";
        Config::set("database.connections.{$mysql}", $newDatabaseConfig);
        // 断开当前连接
        DB::purge($mysql);
        // 重新连接
        DB::reconnect($mysql);

        return $mysql;
    }

    private function uslocalDb() {
        // 定义新的数据库配置
        $newDatabaseConfig = [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '3306',
            'database'  => 'platform-mmg-cn',
            'username'  => 'root',
            'password'  => '9d672e87bf75c4e5',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ];
        // 切换到新的数据库配置
        $mysql = "mysql";
        Config::set("database.connections.{$mysql}", $newDatabaseConfig);
        // 断开当前连接
        DB::purge($mysql);
        // 重新连接
        DB::reconnect($mysql);

        return $mysql;
    }
}
