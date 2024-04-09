<?php

namespace App\Http\Controllers;

use App\Models\Common;
use App\Models\News;
use App\Models\Office;
use App\Models\Partner;
use App\Models\Products;
use App\Http\Controllers\Controller;
use App\Models\ProductDescription;
use App\Models\ProductsCategory;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    // 最新报告
    public function NewsProduct(Request $request)
    {
        $list = Products::where('status', 1)
            ->select([
                'id',
                'thumb',
                'name',
                'published_date',
                'price',
                'url'
            ])
            ->orderBy('sort', 'asc') // 排序权重：sort > 发布时间 > id
            ->orderBy('published_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(6)->get()->toArray();
        foreach ($list as $key => &$value) {
            $description = (new ProductDescription(date('Y', strtotime($value['published_date']))))->where('product_id', $value['id'])->value('description');
            $value['description'] = mb_substr($description, 0, 100, 'UTF-8');
            $value['published_date'] = date('Y-m-d', strtotime($value['published_date']));
            $value['thumb'] = Common::cutoffSiteUploadPathPrefix($value['thumb']);
        }
        $list = $list ? $list : [];
        ReturnJson(true, '', $list);
    }

    // 推荐报告
    public function RecommendProduct(Request $request)
    {
        $categories = ProductsCategory::select([
            'id',
            'name',
            'link',
        ])
            ->orderBy('sort', 'asc')
            ->where('show_home', 1)
            ->where('pid', 0)
            ->limit(4)
            ->get()
            ->toArray();

        $data = [];
        if (!empty($categories) && is_array($categories)) {
            foreach ($categories as $index => $category) {
                $data[$index]['category'] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'url' => $category['link'],
                ];

                $keywords = Products::where('category_id', $category['id'])->limit(5)->pluck('keywords');
                if (!empty($keywords)) {
                    $data[$index]['keywords'] = $keywords;
                } else {
                    continue;
                }

                $firstProduct = Products::select([
                    'name',
                    'keywords',
                    'price',
                    'id',
                    'url',
                    'category_id',
                    'published_date',
                ])
                    ->where('category_id', $category['id'])
                    ->where('show_recommend', 1)
                    ->orderBy('sort', 'asc')
                    ->first();

                if (!empty($firstProduct)) {
                    $thumb = ProductsCategory::where('id', $firstProduct['category_id'])->value('thumb');
                    $thumb = Common::cutoffSiteUploadPathPrefix($thumb);

                    $firstProduct['thumb'] = $thumb;
                    $firstProduct['description'] = (new ProductDescription(date('Y', strtotime($firstProduct['published_date']))))
                        ->where('product_id', $firstProduct['id'])
                        ->value('description');
                }
                if (!empty($firstProduct)) {
                    $data[$index]['firstProduct'] = $firstProduct;
                    $otherProducts = Products::select([
                        'name',
                        'keywords',
                        'id',
                        'url'
                    ])
                        ->where('category_id', $category['id'])
                        ->where('show_recommend', 1)
                        ->where('id', '<>', $firstProduct['id'])
                        ->orderBy('sort', 'asc')
                        ->limit(4)
                        ->get()
                        ->toArray();
                } else {
                    $data[$index]['firstProduct'] = [];
                }
                if (!empty($otherProducts)) {
                    $data[$index]['otherProducts'] = $otherProducts;
                } else {
                    $data[$index]['otherProducts'] = [];
                }
            }
        }
        ReturnJson(true, 'success', $data);
    }

    // 行业新闻
    public function RecommendNews(Request $request)
    {
        $list = News::where('status', 1)
            ->select([
                'id',
                'thumb',
                'title',
                'description',
                'upload_at',
                'url'
            ])
            ->where('show_home', 1) // 是否在首页显示
            ->orderBy('sort', 'desc')
            ->orderBy('upload_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(6)->get()->toArray();
        if ($list) {
            $list = array_map(function ($item) {
                $item['upload_at_format'] = date('Y-m-d', $item['upload_at']);
                $item['thumb'] = Common::cutoffSiteUploadPathPrefix($item['thumb']);
                return $item;
            }, $list);
        }
        ReturnJson(true, '', $list);
    }

    // 合作伙伴
    public function partners(Request $request)
    {
        $list = Partner::where('status', 1)
            ->select([
                'id',
                'name',
                'logo',
            ])
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        ReturnJson(true, '', $list);
    }

    // 办公室
    public function office(Request $request)
    {
        $list = Office::where('status', 1)
            ->select([
                'id',
                'city',
                'name',
                'language_alias',
                'region',
                'area',
                'image',
                'national_flag',
                'phone',
                'address',
                'working_language',
                'working_time',
                'time_zone',
            ])
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        ReturnJson(true, '', $list);
    }

    /**
     * 首页底部提示语
     */
    public function WebpageOrientation(Request $request)
    {
        $id = $request->id;
        if (empty($id)) {
            ReturnJson(false, 'ID不能为空！');
        }
    }
}
