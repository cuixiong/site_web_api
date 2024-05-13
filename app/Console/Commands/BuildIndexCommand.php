<?php

namespace App\Console\Commands;

use App\Models\ProductDescription;
use App\Models\Products;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class BuildIndexCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:buildIndex';

    public function handle() {
        //抓取索引所需的数据 , 添加到新表里
        $product_fields = ['id', 'name', 'english_name', 'category_id', 'country_id', 'price', 'keywords', 'url',
                           'published_date', 'status', 'author', 'discount', 'discount_amount', 'show_hot',
                           'show_recommend', 'sort'];
        $xpiModel = new XunsearchProductIndex();
        $products_list = Products::select($product_fields)->get()->toArray();
        $index = 0;
        foreach ($products_list as $key => $products_data) {

            $year = date('Y', $products_data['published_date']);
            $description = (new ProductDescription($year))->where('product_id', $products_data['id'])->value('description');
            $products_data['description'] = $description;
            $xpiModel->create($products_data);
            if (!empty($xpiModel)) {
                $index++;
                echo "成功处理{$index}条数据".PHP_EOL;
            }
        }
    }
}
