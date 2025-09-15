<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitPostSubject extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post_subject:initPostSubject {--pageNum=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $option = $this->option();
        $pageNum = $option['pageNum'] ?? 1;
        $pageSize = 100000;
        $offset = ($pageNum - 1) * $pageSize;
        ini_set('max_execution_time', '0'); // no time limit，不设置超时时间（根据实际情况使用）
        ini_set("memory_limit", -1);
        $data = DB::table('product_routine as p')
                  ->select('p.id')
                  ->where('p.published_date', '>=', 1735660800) // 2025年
                  ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('post_subject as ps')
                      ->whereRaw('ps.product_id = p.id');
            })
                  ->offset($offset)
                  ->limit($pageSize)
                  ->pluck('id')
                  ->toArray();
        if (empty($data)) {
            dd('没数据');
            exit;
        }
        // 将数据分块处理，每块 1000 条
        $data = array_chunk($data, 1000);
        // 定义要插入的字段
        // $columns = [
        //     'name',
        //     'product_id',
        //     'product_category_id',
        //     'version',
        //     'analyst',
        //     'created_at',
        //     'updated_at',
        //     'keywords',
        //     'has_cagr',
        // ];
        $index = 0;
        foreach ($data as $idGroup) {
            // 查询产品数据
            $productData = DB::table('product_routine')
                             ->select(['id', 'name', 'category_id', 'published_date', 'price', 'author', 'keywords', 'cagr'])
                             ->whereIn('id', $idGroup)
                             ->get();
            // dd($productData);
            // exit;
            $rows = [];
            foreach ($productData as $item) {
                $row = [
                    'name'                => $item->name,
                    'type'                => 1,
                    'product_id'          => $item->id,
                    'product_category_id' => $item->category_id,
                    'version'             => $item->price,
                    'analyst'             => $item->author,
                    'created_at'          => time(),
                    'updated_at'          => time(),
                    'keywords'            => $item->keywords,
                    'has_cagr'            => !empty($item->cagr) ? 1 : 0,
                ];
                $rows[] = $row;
                $index++;
                echo "已成功处理{$index}条数据".PHP_EOL;
            }
            // 批量插入数据
            if (!empty($rows)) {
                DB::table('post_subject')->insert($rows);
            }
        }
        dd('完成');
    }
}
