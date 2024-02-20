<?php
/**
 * php artisan make:job UpdateProduct
 */

namespace App\Http\Helper;

use App\Models\Products;
use XS;
use XSDocument;

class XunSearch {
    public $xs;
    public function __construct()
    {
        $this->xs = new XS('/www/wwwroot/yapi.qyrdata.com/wwwroot/config/xunsearch/product.ini');
    }
    /**
     * 新增文档
     */
    public function add($id)
    {
        
        $index = $this->xs->index;
        $ini = $this->GetProductData($id);
        $doc = new XSDocument();
        $doc->setFields($ini);
        $index->add($doc); 
        return true;
    }

    /**
     * 删除文档
     */
    public function delete($id)
    {
        $index = $this->xs->index;
        $index->delete($id);
        return true;
    }

    /**
     * 更新文档
     */
    public function update($id)
    {
        $index = $this->xs->index;
        $ini = $this->GetProductData($id);
        $doc = new XSDocument();
        $doc->setFields($ini);
        $index->add($doc); 
        return true;
    }

    /**
     * 清除索引
     */
    public function clean()
    {
        $index = $this->xs->index;
        $index->clean();
    }

    /**
     * 搜索索引
     */
    public function search($keyword)
    {
        $search = $this->xs->search;
        $docs = $search->search($keyword);
        $products = [];
        if(!empty($docs)){
            foreach ($docs as $key => $doc) {
                $product = [];
                foreach ($doc as $key2 => $value2) {
                    $product[$key2] = $value2;
                }
                $products[] = $product;
            }
        }
        $count = $search->count($keyword);
        $all_cunt = $search->count();
        $data = [
            'docs' => $products,
            'count' => $count,
            'keyword' => $keyword,
            'all_cunt' => $all_cunt,
        ];
        return $data;
    }

    /**
     * 搜索列表数据
     */
    public function GetList($page,$pageSize,$where = []){
        $search = $this->xs->search;
        // foreach ($where as $key => $value) {
        //     if($key != 'keyword'){
        //         $search->setQuery($key.':'.$value);
        //     } else {
        //         $search->setQuery($value);
        //     }
        // }
        if(!empty($where['keyword'])){
            $search->setQuery($where['keyword']);
        }
        // 表示先以 published_date 反序、再以 sort 正序
        $sorts = array('published_date' => false, 'sort' => true);
        // 设置搜索排序
        $search->setMultiSort($sorts);
        // 设置返回结果为 5 条，但要先跳过 15 条，即第 16～20 条。
        $search->setLimit($pageSize, $pageSize * ($page - 1));
        $docs = $search->search();
        $count = $search->count();
        $products = [];
        if(!empty($docs)){
            foreach ($docs as $key => $doc) {
                $product = [];
                foreach ($doc as $key2 => $value2) {
                    $product[$key2] = $value2;
                }
                $products[] = $product;
            }
        }
        $data = [
            'list' => $products,
            'count' => $count,
        ];
        return $data;
    }

    /**
     * 获取产品数据
     */
    private function GetProductData($id)
    {
        $data = Products::where('id',$id)->first()->toArray();
        $ini = [
            "id" => $data['id'],
            "name" => $data['name'],
            "english_name" => $data['english_name'],
            "thumb" => $data['thumb'],
            "publisher_id" => $data['publisher_id'],
            "category_id" => $data['category_id'],
            "country_id" => $data['country_id'],
            "price" => $data['price'],
            "keywords" => $data['keywords'],
            "url" => $data['url'],
            "published_date" => $data['published_date'],
            "status" => $data['status'],
            "author" => $data['author'],
            "show_home" => $data['show_home'],
            "have_sample" => $data['have_sample'],
            "discount" => $data['discount'],
            "discount_amount" => $data['discount_amount'],
            "discount_type" => $data['discount_type'],
            "discount_time_begin" => $data['discount_time_begin'],
            "discount_time_end" => $data['discount_time_end'],
            "pages" => $data['pages'],
            "tables" => $data['tables'],
            "hits" => $data['hits'],
            "show_hot" => $data['show_hot'],
            "show_recommend" => $data['show_recommend'],
            "sort" => $data['sort'],
            "updated_at" => $data['updated_at'],
            "created_at" => $data['created_at'],
            "updated_by" => $data['updated_by'],
            "created_by" => $data['created_by'],
            "downloads" => $data['downloads'],
        ];
        return $ini;
    }
}