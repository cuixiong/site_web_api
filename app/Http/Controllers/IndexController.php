<?php

namespace App\Http\Controllers;
use App\Models\News;
use App\Models\Office;
use App\Models\Partner;
use App\Models\Products;
use App\Models\ProductsDescription;
use App\Http\Controllers\Controller;

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
                ->orderBy('sort', 'desc') // 排序权重：sort > 发布时间 > id
                ->orderBy('published_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(6)->get();
        foreach ($list as $key => &$value) {
            $description = (new ProductsDescription(date('Y')))->where('product_id',$value->id)->value('description');
            $value['description'] = substr($description,0,255);
        }
        ReturnJson(true,'',$list);
    }
    // 推荐报告
    public function RecommendProduct(Request $request)
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
                ->where('show_recommend',1) // 推荐报告
                ->orderBy('sort', 'desc')
                ->orderBy('published_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(6)->get();
        foreach ($list as $key => &$value) {
            $description = (new ProductsDescription(date('Y')))->where('product_id',$value->id)->value('description');
            $value['description'] = substr($description,0,255);
        }
        ReturnJson(true,'',$list);
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
                ->where('show_home',1) // 是否在首页显示
                ->orderBy('sort', 'desc')
                ->orderBy('upload_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit(6)->get();
        ReturnJson(true,'',$list);
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
        ReturnJson(true,'',$list);
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
        ReturnJson(true,'',$list);
    }
}