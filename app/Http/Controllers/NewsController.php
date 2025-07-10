<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Languages;
use App\Models\News;
use App\Models\PriceEditionValues;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\Common;
use App\Models\ProductDescription;
use App\Models\SystemValue;
use App\Services\SphinxService;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller {
    /**
     * 行业新闻列表
     */
    public function Index(Request $request) {
        $page = $request->page ?? 1;
        $pageSize = $request->pageSize ?? 10;
        $keyword = $request->keyword;
        $industry_id = $request->industry_id;
        $tag = trim($request->tag);
        $type_id = $request->type ?? 0;
        $where = [
            'status' => 1,
        ];
        $field = [
            'thumb',
            'title',
            'upload_at as release_at',
            'tags',
            'description',
            'category_id',
            'id',
            'url',
            'hits',
            'keywords'
        ];
        $query = News::select($field)
                     ->where($where)
                     ->where('upload_at', '<=', time());
        if (!empty($type_id)) {
            $query = $query->where('type', $type_id);
        }
        if (!empty($keyword)) {
            $keyword = explode(" ", $keyword);
            for ($i = 0; $i <= count($keyword); $i++) {
                if (!empty($keyword[$i])) {
                    $query = $query->where('title', 'LIKE', '%'.$keyword[$i].'%');
                }
            }
        }
        if (!empty($industry_id)) {
            $industryIdWhere = ['category_id' => $industry_id];
            $query = $query->where($industryIdWhere);
        }
        if (!empty($tag)) {
            $query = $query->whereRaw(DB::raw('FIND_IN_SET("'.$tag.'",tags)'));
        }
        $count = $query->count();
        $result = $query->orderBy('sort', 'asc')
                        ->orderBy('upload_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->offset(($page - 1) * $pageSize)
                        ->limit($pageSize)
                        ->get()
                        ->toArray();
        $news = [];
        if (!empty($result) && is_array($result)) {
            foreach ($result as $key => $value) {
                $news[$key]['thumb'] = $value['thumb'];
                $news[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($news[$key]['thumb']);
                $news[$key]['title'] = $value['title'];
                $news[$key]['create_time_format'] = $value['release_at'] ? date('Y-m-d', $value['release_at']) : '';
                $news[$key]['month_day'] = $value['release_at'] ? date('m-d', $value['release_at']) : '';
                $news[$key]['year'] = $value['release_at'] ? date('Y', $value['release_at']) : '';
                $news[$key]['month'] = $value['release_at'] ? date('m', $value['release_at']) : '';
                $news[$key]['month_en'] = $value['release_at'] ? date('M', $value['release_at']) : '';
                $news[$key]['day'] = $value['release_at'] ? date('d', $value['release_at']) : '';
                $news[$key]['category'] = ProductsCategory::select(['id', 'name', 'link'])->where(
                    'id',
                    $value['category_id']
                )->first();
                $news[$key]['tags'] = $value['tags'] ? explode(',', $value['tags']) : [];
                $news[$key]['description'] = $value['description'];
                $news[$key]['id'] = $value['id'];
                $news[$key]['url'] = $value['url'];
                $news[$key]['hits'] = $value['hits'];
                $news[$key]['keywords'] = $value['keywords'];
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
     * 行业新闻详情
     */
    public function View(Request $request) {
        $input = $request->input();
        $id = $input['id'] ?? '';
        $url = $input['url'] ?? '';
        if (!isset($id)) {
            ReturnJson(false, 'id is empty');
        }
        $data = News::query()
                    ->where(['id' => $id, 'status' => 1])
                    ->first();
        if ($data) {
            if (!empty($data->upload_at) && $data->upload_at > time()) {
                ReturnJson(false, '新闻未发布，请稍后查看！');
            }
            if ($data->url != $url) {
                ReturnJson(2, '参数错误2');
            }
            $category = ProductsCategory::select(['id', 'name', 'link'])->where(
                'id',
                $data['category_id']
            )->first();
            if (!empty($category)) {
                $category = $category->toArray();
            }
            $data['category'] = $category;
            // real_hits + 1
            News::where(['id' => $id])->increment('real_hits');
            News::where(['id' => $id])->increment('hits');
            $data['tags'] = $data['tags'] ? explode(',', $data['tags']) : [];
            $data['upload_at_format'] = $data['upload_at'] ? date('Y-m-d', $data['upload_at']) : '';
            $data['year'] = $data['upload_at'] ? date('Y', $data['upload_at']) : '';
            $data['month'] = $data['upload_at'] ? date('m', $data['upload_at']) : '';
            $data['month_en'] = $data['upload_at'] ? date('M', $data['upload_at']) : '';
            $data['day'] = $data['upload_at'] ? date('d', $data['upload_at']) : '';
            // list($prevId, $nextId) = $this->getNextPrevId($request, $id); // 暂时注释看看效果
            list($prevId, $nextId) = $this->getNextPrevId2($request, $id, $data['upload_at'], $data['sort']);
            //查询上一篇
            if (!empty($prevId)) {
                $prev = News::select(['id', 'title', 'url', 'category_id'])
                            ->where("id", $prevId)
                            ->first();
            }
            //查询下一篇
            if (!empty($nextId)) {
                $next = News::select(['id', 'title', 'url', 'category_id'])
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
            //获取最新新闻
            $data['last_news'] = $this->getLastNews($id);
            //获取相关报告
            //$data['relevant_product'] = $this->getRelevantProduct($data['tags']);
            $data['relevant_product'] = $this->getNewRelevantByProduct($data['tags'], 6);
            //获取相关新闻
            if (checkSiteAccessData(['168report', 'yhcn', 'mrrs', 'mmgen'])) {
                //相关新闻
                $data['relevant_news'] = $this->getRelevantNews($data['tags'], $id);
            }
            ReturnJson(true, 'success', $data);
        } else {
            $news_relate = News::select(['id', 'url'])
                               ->where(['url' => $url, 'status' => 1])
                               ->where("id", "<>", $id)
                               ->orderBy('upload_at', 'desc')
                               ->orderBy('id', 'desc')
                               ->first();
            if (!empty($news_relate)) {
                ReturnJson(1, '', $news_relate);
            } else {
                ReturnJson(2, '请求失败');
            }
        }
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
        $data = $this->getLastNews($id);
        ReturnJson(true, 'success', $data);
    }

    /**
     * 相关报告列表
     * 取新闻的关键词去搜报告的关键词，只有完全匹配的并且报告的出版日期是当前年份的才取出来
     * "热点资讯详情"和“行业新闻详情”两个页面通用的【相关报告列表】接口。
     */
    public function RelevantProducts(Request $request) {
        $id = $request->id ? $request->id : null;
        $keyword = News::where('id', $id)->value('tags');
        if (!empty($keyword)) {
            $keyword = explode(',', $keyword);
        }
        //$data = $this->getRelevantProduct($keyword);
        $data = $this->getNewRelevantByProduct($keyword);
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
        //数据列表第一条， 上一篇需要这样处理
        if ($offset > 1) {
            $offset -= 1;
        }
        //避免用户,一直点击下一页,导致没有下一篇
        $pageSize += 200;
        $keyword = $request->keyword;
        $industry_id = $request->industry_id;
        $tag = trim($request->tag);
        $query = News::query()->where('status', 1)
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
                            ->orderBy('id', 'desc')
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

    /**
     * 尝试优化上下一页
     *
     * @param Request $request
     * @param mixed   $id
     *
     * @return int[]
     */
    private function getNextPrevId2(Request $request, $id, $upload_at, $sort = 0) {
        $keyword = $request->keyword;
        $industry_id = $request->industry_id;
        $tag = trim($request->tag);
        $query = News::query()->where('status', 1)
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
        $prevId = (clone $query)->where(function ($query) use ($sort, $upload_at) {
            $query->where('sort', '<', $sort)
                  ->orWhere(function ($subQuery) use ($sort, $upload_at) {
                      $subQuery->where('sort', $sort)
                               ->where('upload_at', '<=', $upload_at);
                  });
        })
                                ->where('id', '<>', $id)
                                ->orderBy('sort', 'desc')
                                ->orderBy('upload_at', 'desc')
                                ->orderBy('id', 'desc')
                                ->limit(1)
                                ->value('id');
        $nextId = (clone $query)->where(function ($query) use ($sort, $upload_at) {
            $query->where('sort', '>', $sort)
                  ->orWhere(function ($subQuery) use ($sort, $upload_at) {
                      $subQuery->where('sort', $sort)
                               ->where('upload_at', '>=', $upload_at);
                  });
        })
                                ->where('id', '<>', $id)
                                ->orderBy('sort', 'asc')
                                ->orderBy('upload_at', 'asc')
                                ->orderBy('id', 'desc')
                                ->limit(1)
                                ->value('id');
        $prevId = !empty($prevId) ? $prevId : 0;
        $nextId = !empty($nextId) ? $nextId : 0;

        return array($prevId, $nextId);
    }

    /**
     *
     * @param mixed $id
     *
     * @return mixed
     */
    private function getLastNews(mixed $id) {
        $data = News::select([
                                 'title',
                                 'keywords',
                                 'id',
                                 'url',
                                 'description',
                                 'upload_at',
                             ])
                    ->where('status', 1)
                    ->where('id', '<>', $id)
                    ->where('upload_at', '<=', time())
            // ->where($category_id)
                    ->orderBy('upload_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->toArray();

        return $data;
    }

    private function getRelevantNews($keyword, $id) {
        $data = News::query()
                    ->where('status', 1)
                    ->where('id', '<>', $id)
                    ->where('upload_at', '<=', time())
            //->whereIn('keywords', $keyword)
                    ->whereIn('tags', $keyword)
                    ->orderBy('upload_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->toArray();

        return $data;
    }

    /**
     *
     * @param mixed $id
     *
     * @return array
     */
    private function getRelevantProduct($keyword): array {
        $data = [];
        if ($keyword) {
            //$begin = strtotime("-2 year", strtotime(date('Y-01-01', time()))); // 前两年
            $result = Products::select([
                                           'id',
                                           'name',
                                           'thumb',
                                           'english_name',
                                           'keywords',
                                           'published_date',
                                           'category_id',
                                           'price',
                                           'url',
                                           'discount_type',
                                           'discount',
                                           'discount_amount as discount_value',
                                           'discount_time_begin',
                                           'discount_time_end',
                                           // 'description_seo',
                                           'publisher_id',
                                       ])
                              ->whereIn('keywords', $keyword)
                              ->where("status", 1)
                //->where('published_date', '>', $begin)
                              ->where("published_date", "<=", time())
                              ->orderBy('published_date', 'desc')
                              ->orderBy('id', 'desc')
                              ->limit(2)
                              ->get()
                              ->toArray();
            if ($result) {
                $time = time();
                // 默认图片
                // 若报告图片为空，则使用系统设置的默认报告高清图
                $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
                // 分类信息
                $categoryIds = array_column($result, 'category_id');
                $categoryData = ProductsCategory::select(['id', 'name', 'thumb', 'link', 'product_tag'])->whereIn(
                    'id', $categoryIds
                )->get()->toArray();
                $categoryData = array_column($categoryData, null, 'id');
                foreach ($result as $key => $value) {
                    //每个报告加上分类信息
                    $tempCategoryId = $value['category_id'];
                    $data[$key]['categoryId'] = isset($tempCategoryId) ? $tempCategoryId : '';
                    $data[$key]['categoryName'] = isset($categoryData[$tempCategoryId])
                                                  && isset($categoryData[$tempCategoryId]['name'])
                        ? $categoryData[$tempCategoryId]['name'] : '';
                    $data[$key]['categoryLink'] = isset($categoryData[$tempCategoryId])
                                                  && isset($categoryData[$tempCategoryId]['link'])
                        ? $categoryData[$tempCategoryId]['link'] : '';
                    $data[$key]['tag_list'] = isset($categoryData[$tempCategoryId])
                                              && isset($categoryData[$tempCategoryId]['product_tag']) ? explode(
                        ",", $categoryData[$tempCategoryId]['product_tag']
                    ) : [];
                    $data[$key]['thumb'] = Products::getThumbImgUrl($value);
                    if (empty($data[$key]['thumb'])) {
                        // 如果报告图片、分类图片为空，使用系统默认图片
                        $data[$key]['thumb'] = !empty($defaultImg) ? $defaultImg : '';
                    }
                    $data[$key]['name'] = $value['name'];
                    $data[$key]['keyword'] = $value['keywords'];
                    $data[$key]['english_name'] = $value['english_name'];
                    // $data[$key]['description'] = $value['description_seo'];
                    $data[$key]['date'] = $value['published_date'] ? $value['published_date'] : '';
                    $data[$key]['discount_type'] = $value['discount_type'];
                    $data[$key]['discount_value'] = $value['discount_value'];
                    $data[$key]['discount_amount'] = $value['discount_value']; // 兼容
                    $data[$key]['discount'] = $value['discount'];
                    $data[$key]['discount_time_begin'] = $value['discount_time_begin'];
                    $data[$key]['discount_time_end'] = $value['discount_time_end'];
                    //判断当前报告是否在优惠时间内
                    if ($data[$key]['discount_time_begin'] <= $time && $data[$key]['discount_time_end'] >= $time) {
                        $data[$key]['discount_status'] = 1;
                    } else {
                        $data[$key]['discount_status'] = 0;
                        // 过期需返回正常的折扣
                        $data[$key]['discount_value'] = 0;
                        $data[$key]['discount_amount'] = 0; // 兼容
                        $data[$key]['discount'] = 100;
                        $data[$key]['discount_time_begin'] = null;
                        $data[$key]['discount_time_end'] = null;
                    }
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
                            // $priceEditions = PriceEditionValues::select(
                            //     ['id', 'name as edition', 'rules as rule', 'is_logistics', 'notice']
                            // )->where(['status' => 1, 'is_deleted'=> 1, 'language_id' => $language['id']])->orderBy("sort", "asc")->get()->toArray();
                            $priceEditions = PriceEditionValues::GetList($language['id'], $value['publisher_id']);
                            if ($priceEditions) {
                                $prices[$index]['language'] = $language['name'];
                                foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                                    $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                                    $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['name'];
                                    $prices[$index]['data'][$keyPriceEdition]['is_logistics']
                                        = $priceEdition['is_logistics'];
                                    $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                                    $prices[$index]['data'][$keyPriceEdition]['price'] = eval(
                                        "return ".sprintf(
                                            $priceEdition['rules'],
                                            $value['price']
                                        ).";"
                                    );
                                    if ($index == 0 && $keyPriceEdition == 0) {
                                        // 以第一个价格版本作为显示的价格版本
                                        $data[$key]['price'] = $prices[$index]['data'][$keyPriceEdition]['price'];
                                        $data[$key]['price_edition'] = $priceEdition['id'];
                                    }
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

        return $data;
    }

    /**
     *
     * @param mixed   $keyword_list
     * @param integer $relevant_products_size
     *
     * @return array
     */
    private function getNewRelevantByProduct($keyword_list, $relevant_products_size = 4) {
        $result = $this->GetRelevantProductResult(
            -1,
            $keyword_list,
            1,
            $relevant_products_size,
            'keywords'
        );
        $data = [];
        if ($result) {
            $time = time();
            // 默认图片
            // 若报告图片为空，则使用系统设置的默认报告高清图
            $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
            // 分类信息
            $categoryIds = array_column($result, 'category_id');
            $categoryData = ProductsCategory::select(['id', 'name', 'thumb', 'link', 'product_tag'])->whereIn(
                'id', $categoryIds
            )->get()->toArray();
            $categoryData = array_column($categoryData, null, 'id');
            // 报告数据
            $ids = array_column($result, 'id');
            $productsDataArray = Products::query()->whereIn('id', $ids)->get()->toArray();
            $productsDataArray = array_column($productsDataArray, null, 'id');
            foreach ($result as $key => $value) {
                if (empty($productsDataArray[$value['id']])) {
                    unset($result[$key]);
                    continue;
                }
                $value = $productsDataArray[$value['id']];
                //每个报告加上分类信息
                $tempCategoryId = $value['category_id'];
                $data[$key]['categoryId'] = isset($tempCategoryId) ? $tempCategoryId : '';
                $data[$key]['categoryName'] = isset($categoryData[$tempCategoryId])
                                              && isset($categoryData[$tempCategoryId]['name'])
                    ? $categoryData[$tempCategoryId]['name'] : '';
                $data[$key]['categoryLink'] = isset($categoryData[$tempCategoryId])
                                              && isset($categoryData[$tempCategoryId]['link'])
                    ? $categoryData[$tempCategoryId]['link'] : '';
                $data[$key]['tag_list'] = isset($categoryData[$tempCategoryId])
                                          && isset($categoryData[$tempCategoryId]['product_tag']) ? explode(
                    ",", $categoryData[$tempCategoryId]['product_tag']
                ) : [];
                $data[$key]['thumb'] = Products::getThumbImgUrl($value);
                if (empty($data[$key]['thumb'])) {
                    // 如果报告图片、分类图片为空，使用系统默认图片
                    $data[$key]['thumb'] = !empty($defaultImg) ? $defaultImg : '';
                }
                $data[$key]['name'] = $value['name'];
                $data[$key]['keyword'] = $value['keywords'];
                $data[$key]['pages'] = $value['pages'];
                $data[$key]['english_name'] = $value['english_name'];
                // $data[$key]['description'] = $value['description_seo'];
                $data[$key]['date'] = $value['published_date'] ? $value['published_date'] : '';
                $data[$key]['year'] = $value['published_date'] ? date('Y', strtotime($value['published_date'])) : '';
                $data[$key]['month'] = $value['published_date'] ? date('m', strtotime($value['published_date'])) : '';
                $data[$key]['month_en'] = $value['published_date'] ? date('M', strtotime($value['published_date']))
                    : '';
                $data[$key]['day'] = $value['published_date'] ? date('d', strtotime($value['published_date'])) : '';
                $data[$key]['discount_type'] = $value['discount_type'];
                $data[$key]['discount_value'] = $value['discount_amount'];
                $data[$key]['discount_amount'] = $value['discount_amount']; // 兼容
                $data[$key]['discount'] = $value['discount'];
                $data[$key]['discount_time_begin'] = $value['discount_time_begin'];
                $data[$key]['discount_time_end'] = $value['discount_time_end'];
                //判断当前报告是否在优惠时间内
                if ($data[$key]['discount_time_begin'] <= $time && $data[$key]['discount_time_end'] >= $time) {
                    $data[$key]['discount_status'] = 1;
                } else {
                    $data[$key]['discount_status'] = 0;
                    // 过期需返回正常的折扣
                    $data[$key]['discount_value'] = 0;
                    $data[$key]['discount_amount'] = 0; // 兼容
                    $data[$key]['discount'] = 100;
                    $data[$key]['discount_time_begin'] = null;
                    $data[$key]['discount_time_end'] = null;
                }
                $data[$key]['description'] = (new ProductDescription(
                    date('Y', strtotime($value['published_date']))
                ))->where('product_id', $value['id'])->value('description');
                //$data[$key]['description'] = mb_substr($data[$key]['description'], 0, 100, 'UTF-8');
                //取描述第一段 ,  如果没有\n换行符就取一整段
                $strIndex = strpos($data[$key]['description'], "\n");
                if ($strIndex !== false) {
                    // 使用 substr() 函数获取第一个段落
                    $data[$key]['description'] = substr($data[$key]['description'], 0, $strIndex);
                } else {
                    $data[$key]['description'] = mb_substr($data[$key]['description'], 0, 100, 'UTF-8');
                }
                // 这里的代码可以复用 开始
                $prices = [];
                // 计算报告价格
                $languages = Languages::select(['id', 'name'])->get()->toArray();
                if ($languages) {
                    foreach ($languages as $index => $language) {
                        // $priceEditions = PriceEditionValues::select(
                        //     ['id', 'name as edition', 'rules as rule', 'is_logistics', 'notice']
                        // )->where(['status' => 1, 'is_deleted'=> 1, 'language_id' => $language['id']])->orderBy("sort", "asc")->get()->toArray();
                        $priceEditions = PriceEditionValues::GetList($language['id'], $value['publisher_id']);
                        if ($priceEditions) {
                            $prices[$index]['language'] = $language['name'];
                            foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                                $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                                $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['name'];
                                $prices[$index]['data'][$keyPriceEdition]['is_logistics']
                                    = $priceEdition['is_logistics'];
                                $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                                $prices[$index]['data'][$keyPriceEdition]['price'] = eval(
                                    "return ".sprintf(
                                        $priceEdition['rules'],
                                        $value['price']
                                    ).";"
                                );
                                if ($index == 0 && $keyPriceEdition == 0) {
                                    // 以第一个价格版本作为显示的价格版本
                                    $data[$key]['price'] = $prices[$index]['data'][$keyPriceEdition]['price'];
                                    $data[$key]['price_edition'] = $priceEdition['id'];
                                }
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

        return $data;
    }

    /**
     * 返回相关产品数据-重定向/相关报告
     */
    public function GetRelevantProductResult(
        $id,
        $keyword,
        $page = 1,
        $pageSize = 1,
        $searchField = 'url',
        $selectField = '*',
        $order = []
    ) {
        try {
            $hidden = SystemValue::where('key', 'sphinx')->value('hidden');
            if ($hidden == 1) {
                return $this->SearchRelevantForSphinx(
                    $id,
                    $keyword,
                    $page,
                    $pageSize,
                    $searchField,
                    $selectField,
                    $order
                );
            } else {
                return $this->SearchRelevantForMysql($id, $keyword, $page, $pageSize, $searchField, $selectField);
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
            \Log::error('应用端查询失败,异常信息为:'.json_encode([$e->getMessage()]));
            ReturnJson(false, '请求失败,请稍后再试');
        }
    }

    public function SearchRelevantForSphinx($id, $keyword, $page, $pageSize, $searchField, $selectField, $order = []) {
        if (empty($id) || empty($keyword)) {
            return [];
        }
        $sphinxSrevice = new SphinxService();
        $conn = $sphinxSrevice->getConnection();
        //报告昵称,英文昵称匹配查询
        $query = (new SphinxQL($conn))->select('id')
                                      ->from('products_rt');
        if (empty($order)) {
            $query = $query->orderBy('sort', 'asc')
                           ->orderBy('published_date', 'desc')
                           ->orderBy('id', 'desc');
        } else {
            foreach ($order as $key => $value) {
                $query = $query->orderBy($key, $value);
            }
        }
        $query = $query->where('status', '=', 1);
        $query = $query->where("published_date", "<=", time());
        // 排除本报告
        $query = $query->where('id', '<>', intval($id));
        // 精确查询
        if (!empty($keyword)) {
            if (is_array($keyword)) {
                //$query->where($searchField, '测试 机器人' , true);
                $query->where($searchField, 'in', $keyword);
            } else {
                $val = addslashes($keyword);
                $query->where($searchField, '=', $val);
            }
        }
        //查询结果分页
        $offset = ($page - 1) * $pageSize;
        $query->limit($offset, $pageSize);
        // $query->option('max_matches', $offset + $pageSize);
        // $query->setSelect($selectField);
        // $result = $query->execute();
        // $products = $result->fetchAllAssoc();
        // 因为有些字段sphinx没有，所以sphinx查出id后再去mysql查询
        $query->setSelect('id');
        $result = $query->execute();
        $productsIds = $result->fetchAllAssoc();
        if (!empty($productsIds) && count($productsIds) > 0) {
            $productsIds = array_column($productsIds, 'id');
            $products = Products::select($selectField)
                                ->whereIn("id", $productsIds)
                                ->get()->toArray();
        }

        //
        return $products ?? [];
    }

    public function SearchRelevantForMysql($id, $keyword, $page, $pageSize, $searchField, $selectField) {
        $products = Products::select($selectField)
                            ->where([$searchField => $keyword, 'status' => 1])
                            ->where("id", "<>", $id)
                            ->limit($pageSize, ($page - 1) * $pageSize)
                            ->orderBy('published_date', 'desc')
                            ->orderBy('id', 'desc')
                            ->get()->toArray();

        return $products;
    }
}
