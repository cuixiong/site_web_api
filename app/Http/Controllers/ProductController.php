<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Products;
use App\Models\ProductsCategory;

class ProductController extends Controller
{
    // 获取报告列表信息
    public function list(Request $request){
        $page = $request->page ? intval($request->page) : 1; // 页码
        $pageSize = $request->pageSize ? intval($request->pageSize) : 10; // 每页显示数量
        $category_id = $request->category_id ?? 0; // 分类ID
        $keyword = trim($request->keyword) ?? null;// 搜索关键词
        // $query = Query();
        $query = Products::where(['status' => 1])
            ->select([
                'name',
                'english_name',
                'published_date',
                'keywords',
                'id',
                'url',
                'price',
                'discount_type',
                'discount_amount',
                'category_id',
            ]);
        // 分类ID
        if($category_id){
            $query = $query->where('category_id', $category_id);
        }
        // 关键词
        if($keyword){
            $query = $query->where(function($query) use ($keyword){
                $query->where('name', 'like', '%'.$keyword.'%');
                // ->orWhere('description', 'like', '%'.$keyword.'%');
            });
        }
        // 获取当前复合条件的总数量
        $count = $query->count();

        // 排序 显示发布时间 》 排序 》 id
        $query = $query->orderBy('published_date','desc')->orderBy('sort','asc');
        // 分页
        $offset = ($page -1) * $pageSize;
        $result = $query->offset($offset)->limit($pageSize)->get()->toArray();
        if ($result) {
            $products = [];
            foreach ($result as $key => $value) {
                $prices = [];
                // 计算报告价格
                // $languages = PriceLanguage::select(['id', 'language'])->get()->toArray();
                // if ($languages) {
                //     foreach ($languages as $index => $language) {
                //         $priceEditions = PriceEdition::select(['id', 'edition', 'rule', 'notice'])->where(['language_id' => $language['id']])->asArray()->all();
                //         $prices[$index]['language'] = $language['language'];
                //         if ($priceEditions) {
                //             foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                //                 $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                //                 $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['edition'];
                //                 $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                //                 $prices[$index]['data'][$keyPriceEdition]['price'] = eval("return " . sprintf($priceEdition['rule'], $value['price']) . ";");
                //             }
                //         }
                //     }
                // }

                $category = ProductsCategory::select([
                    'id',
                    'name',
                    'link',
                    'thumb'
                ])->find($value['category_id']);
                $products[$key]['thumb'] = $category ? $category['thumb'] : '';
                $products[$key]['name'] = $value['name'];
                $products[$key]['english_name'] = $value['english_name'];
                // $products[$key]['description_seo'] = $value['description_seo'];
                $products[$key]['published_date'] = $value['published_date'] ? $value['published_date'] : '';
                $products[$key]['category'] = $category ? [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'link' => $category['link'],
                ] : [];
                $products[$key]['keywords'] = $value['keywords'];
                $products[$key]['discount_type'] = $value['discount_type'];
                $products[$key]['discount_amount'] = $value['discount_amount'];
                $products[$key]['prices'] = $prices ?? [];
                $products[$key]['id'] = $value['id'];
                $products[$key]['url'] = $value['url'];
            }
        }

        $data = [
            'products' => $products ? $products : [],
            "page" => intVal($page),
            "pageSize" => intVal($pageSize),
            "count" => intVal($count),
            'pageCount' => ceil($count / $pageSize),
        ];
        ReturnJson(true,'请求成功',$data);
    }
}