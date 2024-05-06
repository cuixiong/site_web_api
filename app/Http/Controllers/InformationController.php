<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Information;
use App\Models\Languages;
use App\Models\PriceEditionValues;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\Common;
use App\Models\ProductDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformationController extends Controller {
    /**
     * 热门资讯列表
     */
    public function Index(Request $request) {
        $page = $request->page ?? 1;
        $pageSize = $request->pageSize ?? 10;
        $keyword = $request->keyword;
        $industry_id = $request->industry_id;
        $tag = trim($request->tag);
        $where = [
            'status' => 1,
            // 'category_id' => 1
        ];
        $query = Information::select([
                                         'thumb',
                                         'title',
                                         'upload_at as release_at',
                                         'tags',
                                         'description',
                                         'type as industry_id',
                                         'id',
                                         'url'
                                     ])
                            ->where($where)
                            ->where('upload_at', '<=', time());
        $count = Information::where($where);
        if (!empty($keyword)) {
            $keyword = explode(" ", $keyword);
            for ($i = 0; $i <= count($keyword); $i++) {
                if (!empty($keyword[$i])) {
                    $query = $query->where('title', 'LIKE', '%'.$keyword[$i].'%');
                    $count = $count->where('title', 'LIKE', '%'.$keyword[$i].'%');
                }
            }
        }
        if (!empty($industry_id)) {
            $industryIdWhere = ['type' => $industry_id];
            $query = $query->where($industryIdWhere);
            $count = $count->where($industryIdWhere);
        }
        if (!empty($tag)) {
            $query = $query->whereRaw(DB::raw('FIND_IN_SET("'.$tag.'",tags)'));
            $count = $count->whereRaw(DB::raw('FIND_IN_SET("'.$tag.'",tags)'));
        }
        $result = $query->orderBy('sort', 'asc')->orderBy('upload_at', 'desc')
                        ->offset(($page - 1) * $pageSize)
                        ->limit($pageSize)
                        ->get()
                        ->toArray();
        $count = $count->count();
        $news = [];
        if (!empty($result) && is_array($result)) {
            foreach ($result as $key => $value) {
                $news[$key]['thumb'] = $value['thumb'];
                $news[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($news[$key]['thumb']);
                $news[$key]['title'] = $value['title'];
                $news[$key]['month_day'] = $value['release_at'] ? date('m-d', $value['release_at']) : '';
                $news[$key]['year'] = $value['release_at'] ? date('Y', $value['release_at']) : '';
                $news[$key]['category'] = ProductsCategory::select(['id', 'name', 'link'])->where(
                    'id', $value['industry_id']
                )->first();
                $news[$key]['tags'] = $value['tags'] ? explode(',', $value['tags']) : [];
                $news[$key]['description'] = $value['description'];
                $news[$key]['id'] = $value['id'];
                $news[$key]['url'] = $value['url'];
            }
        }
        $data = [
            'news'      => $news ? $news : [],
            "page"      => $page,
            "pageSize"  => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            "count"     => intval($count),
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 热门资讯详情
     */
    public function View(Request $request) {
        $id = $request->id;
        if (!isset($id)) {
            ReturnJson(false, 'id is empty');
        }
        $data = Information::select([
                                        'title',
                                        'upload_at',
                                        'hits',
                                        'tags',
                                        'content',
                                        'keywords',
                                        'description'
                                    ])
                           ->where(['id' => $id, 'status' => 1])
                           ->first();
        if ($data) {
            // real_hits + 1
            Information::where(['id' => $id])->increment('real_hits');
            Information::where(['id' => $id])->increment('hits');
            $data['tags'] = $data['tags'] ? explode(',', $data['tags']) : [];
            $data['upload_at_format'] = $data['upload_at'] ? date('Y-m-d', $data['upload_at']) : '';
        } else {
            $data = [];
        }
        list($prevId, $nextId) = $this->getNextPrevId($request, $id);
        //查询上一篇
        if (!empty($prevId)) {
            $prev = Information::select(['id', 'title', 'url', 'category_id'])
                        ->where("id", $prevId)
                        ->first();
        }
        //查询下一篇
        if (!empty($nextId)) {
            $next = Information::select(['id', 'title', 'url', 'category_id'])
                        ->where("id", $nextId)
                        ->first();
        }
        $prev_next = [];
        if (!empty($prev)) {
            $prev['routeName'] = $prev['category_id'];
            $prev_next['prev'] = $prev;
        } else {
            $prev_next['prev'] = [];
        }
        if (!empty($next)) {
            $next['routeName'] = $next['category_id'];
            $prev_next['next'] = $next;
        } else {
            $prev_next['next'] = [];
        }
        $data['prev_next'] = $prev_next;
        ReturnJson(true, 'success', $data);
    }

    /**
     * 相关新闻资讯列表：由于数据之间没有相关性，所以取随机的几条数据
     */
    public function Relevant(Request $request) {
        $id = $request->id; // 从详情页获取id，根据该id获取相关数据
        if (empty($id)) {
            ReturnJson(false, 'id is empty');
        }
        // $category_id = 1;
        $data = Information::select([
                                        'title',
                                        'keywords',
                                        'id',
                                        'url',
                                    ])
                           ->where('status', 1)
                           ->where('id', '<>', $id)
            // ->where($category_id)
                           ->orderBy('sort', 'asc')
                           ->orderBy('created_at', 'desc')
                           ->limit(5)
                           ->get()
                           ->toArray();
        ReturnJson(true, 'success', $data);
    }

    /**
     * 相关报告列表
     * 取新闻的关键词去搜报告的关键词，只有完全匹配的并且报告的出版日期是当前年份的才取出来
     * "热点资讯详情"和“行业新闻详情”两个页面通用的【相关报告列表】接口。
     */
    public function RelevantProducts(Request $request) {
        $id = $request->id ? $request->id : null;
        $keyword = Information::where('id', $id)->value('tags');
        if (!empty($keyword)) {
            $keyword = explode(',', $keyword);
        }
        $data = [];
        if ($keyword) {
            $begin = strtotime("-2 year", strtotime(date('Y-01-01', time()))); // 前两年
            $result = Products::select([
                                           'id',
                                           'name',
                                           'english_name',
                                           'keywords',
                                           'published_date',
                                           'category_id',
                                           'price',
                                           'url',
                                           'discount_type',
                                           'discount_amount as discount_value',
                                           // 'description_seo'
                                       ])
                              ->whereIn('keywords', $keyword)
                              ->where('published_date', '>', $begin)
                              ->orderBy('published_date', 'desc')
                              ->orderBy('id', 'desc')
                              ->limit(2)
                              ->get()
                              ->toArray();
            if ($result) {
                foreach ($result as $key => $value) {
                    $data[$key]['thumb'] = ProductsCategory::where('id', $value['category_id'])->value('thumb');
                    $data[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($data[$key]['thumb']);
                    $data[$key]['name'] = $value['name'];
                    $data[$key]['keyword'] = $value['keywords'];
                    $data[$key]['english_name'] = $value['english_name'];
                    // $data[$key]['description'] = $value['description_seo'];
                    $data[$key]['date'] = $value['published_date'] ? $value['published_date'] : '';
                    $data[$key]['categoryName'] = ProductsCategory::where('id', $value['category_id'])->value('name');
                    $data[$key]['discount_type'] = $value['discount_type'];
                    $data[$key]['discount_value'] = $value['discount_value'];
                    $data[$key]['description'] = (new ProductDescription(
                        date('Y', strtotime($value['published_date']))
                    ))->where('product_id', $value['id'])->value('description');
                    $data[$key]['description'] = mb_substr($data[$key]['description'], 0, 100, 'UTF-8');
                    // 这里的代码可以复用 开始
                    $prices = [];
                    // 计算报告价格
                    $languages = Languages::select(['id', 'name'])->get()->toArray();
                    if ($languages) {
                        foreach ($languages as $index => $language) {
                            $priceEditions = PriceEditionValues::select(
                                ['id', 'name as edition', 'rules as rule', 'notice']
                            )->where(['language_id' => $language['id']])->get()->toArray();
                            $prices[$index]['language'] = $language['name'];
                            if ($priceEditions) {
                                foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                                    $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                                    $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['edition'];
                                    $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                                    $prices[$index]['data'][$keyPriceEdition]['price'] = eval(
                                        "return ".sprintf(
                                            $priceEdition['rule'], $value['price']
                                        ).";"
                                    );
                                }
                            }
                        }
                    }
                    $data[$key]['prices'] = $prices;
                    // 这里的代码可以复用 结束
                    $data[$key]['id'] = $value['id'];
                    $data[$key]['url'] = $value['url'];
                }
            }
        }
        ReturnJson(true, 'success', $data);
    }

    /**
     *
     * @param Request $request
     * @param mixed   $id
     *
     * @return int[]
     */
    private function getNextPrevId(Request $request, mixed $id): array {
        //查询上一个 ， 下一个
        $page = $request->page ?? 1;
        $pageSize = $request->pageSize ?? 10;
        //数据列表末尾一条， 下一篇需要这样处理
        $offset = ($page - 1) * $pageSize;
        $pageSize += 2;
        //数据列表第一条， 上一篇需要这样处理
        if ($offset > 1) {
            $offset -= 1;
        }
        $keyword = $request->keyword;
        $industry_id = $request->industry_id;
        $tag = trim($request->tag);
        $query = Information::query()->where('status', 1)
                            ->where("upload_at", "<=", time());
        if (!empty($keyword)) {
            $keyword = explode(" ", $keyword);
            for ($i = 0; $i <= count($keyword); $i++) {
                if (!empty($keyword[$i])) {
                    $query = $query->where('title', 'LIKE', '%'.$keyword[$i].'%');
                }
            }
        }
        if (!empty($industry_id)) {
            $industryIdWhere = ['type' => $industry_id];
            $query = $query->where($industryIdWhere);
        }
        if (!empty($tag)) {
            $query->whereRaw(DB::raw('FIND_IN_SET("'.$tag.'",tags)'));
        }
        $sortIdList = $query->orderBy('sort', 'asc')
                            ->orderBy('upload_at', 'desc')
                            ->offset($offset)
                            ->limit($pageSize)
                            ->pluck('id')
                            ->toArray();
        $prevId = 0;
        $nextId = 0;
        foreach ($sortIdList as $key => $sortId) {
            if ($sortId == $id) {
                $prevId = isset($sortIdList[$key - 1]) ? $sortIdList[$key - 1] : 0;
                $nextId = isset($sortIdList[$key + 1]) ? $sortIdList[$key + 1] : 0;
            }
        }

        return array($prevId, $nextId);
    }
}
