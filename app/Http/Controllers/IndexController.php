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
use App\Services\SenWordsService;
use Illuminate\Http\Request;

class IndexController extends Controller {
    // 最新报告(热门报告)
    public function NewsProduct(Request $request) {
        $query = Products::where('status', 1)
                         ->where("show_hot", 1)
                         ->select(['id', 'thumb', 'name', 'category_id', 'published_date', 'price', 'url'])
                         ->orderBy('sort', 'asc') // 排序权重：sort > 发布时间 > id
                         ->orderBy('published_date', 'desc')
                         ->orderBy('id', 'desc');
        $pageSize = 6;
        $list = $query->limit($pageSize)->get();
        foreach ($list as $key => $value) {
            $this->handlerNewProductList($value);
        }
        ReturnJson(true, '', $list);
    }

    // 推荐报告
    public function RecommendProduct(Request $request) {
        $categories = ProductsCategory::select(['id', 'name', 'link', 'thumb'])
                                      ->orderBy('sort', 'asc')
                                      ->orderBy('updated_at', 'desc')
                                      ->where('is_recommend', 1)
                                      ->where('pid', 0)
                                      ->limit(4)
                                      ->get()
                                      ->toArray();
        $productFields = ['name', 'keywords', 'price', 'id', 'url', 'category_id', 'published_date', 'thumb'];
        $data = [];
        if (!empty($categories) && is_array($categories)) {
            foreach ($categories as $index => $category) {
                $data[$index]['category'] = [
                    'id'   => $category['id'],
                    'name' => $category['name'],
                    'url'  => $category['link'],
                ];
                $pageSize = 5;
                $productList = Products::select($productFields)
                                       ->where('category_id', $category['id'])
                                       ->where('show_recommend', 1)
                                       ->where("status", 1)
                                       ->orderBy('sort', 'asc')
                                       ->orderBy('published_date', 'desc')
                                       ->orderBy('id', 'desc')
                                       ->limit($pageSize)
                                       ->get()
                                       ->toArray();
                if (empty($productList)) {
                    continue;
                }
                $keywords = array_column($productList, 'keywords');
                $data[$index]['keywords'] = $keywords;
                $firstProduct = array_shift($productList);
                if (!empty($firstProduct)) {
                    $thumb = '';
                    if (!empty($firstProduct['thumb'])) {
                        $thumb = Common::cutoffSiteUploadPathPrefix($firstProduct['thumb']);
                    } elseif ($firstProduct['category_id']) {
                        $thumb = $category['thumb'];
                    }
                    $firstProduct['thumb'] = $thumb;

                    $year = date('Y', strtotime($firstProduct['published_date']));
                    $description = (new ProductDescription($year))
                        ->where('product_id', $firstProduct['id'])
                        ->value('description');
                    $description = mb_substr($description, 0, 300, 'UTF-8');
                    $firstProduct['description'] = $description;
                }
                $data[$index]['firstProduct'] = $firstProduct;
                $data[$index]['otherProducts'] = $productList;
            }
        }
        ReturnJson(true, 'success', $data);
    }

    // 行业新闻
    public function RecommendNews(Request $request) {
        $list = News::where('status', 1)
                    ->select(['id', 'thumb', 'title', 'description', 'upload_at', 'url'])
                    ->where('show_home', 1) // 是否在首页显示
            //->orderBy('sort', 'desc')
                    ->orderBy('upload_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->limit(4)->get()->toArray();
        if ($list) {
            foreach ($list as $key => $item) {
                $list[$key]['upload_at_format'] = date('Y-m-d', $item['upload_at']);
                $list[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($item['thumb']);
            }
        }
        ReturnJson(true, '', $list);
    }

    // 合作伙伴
    public function partners(Request $request) {
        $list = Partner::where('status', 1)
                       ->select(['id', 'name', 'logo',])
                       ->orderBy('sort', 'desc')
                       ->orderBy('id', 'desc')
                       ->get();
        foreach ($list as &$item) {
            $item['logo'] = Common::cutoffSiteUploadPathPrefix($item['logo']);
        }
        ReturnJson(true, '', $list);
    }

    // 办公室
    public function office(Request $request) {
        $list = Office::where('status', 1)
                      ->select(
                          ['id', 'city', 'name', 'language_alias', 'region', 'area', 'image', 'national_flag', 'phone',
                           'address', 'working_language', 'working_time', 'time_zone']
                      )
                      ->orderBy('sort', 'desc')
                      ->orderBy('id', 'desc')
                      ->get();
        foreach ($list as &$value) {
            $value['image'] = Common::cutoffSiteUploadPathPrefix($value['image']);
            $value['national_flag'] = Common::cutoffSiteUploadPathPrefix($value['national_flag']);
        }
        ReturnJson(true, '', $list);
    }

    /**
     * 首页底部提示语
     */
    public function WebpageOrientation(Request $request) {
        $id = $request->id;
        if (empty($id)) {
            ReturnJson(false, 'ID不能为空！');
        }
    }

    /**
     *
     * @param Products $value
     *
     */
    private function handlerNewProductList(Products $value): void {
        /**
         * @var $value Products
         */
        $published_date = $value['published_date'];
        $description = (new ProductDescription(date('Y', strtotime($published_date))))->where(
            'product_id', $value['id']
        )->value('description');
        $value->published_date = date('Y-m-d', strtotime($published_date));
        $value->description = mb_substr($description, 0, 100, 'UTF-8');
        $value->thumb = $value->getThumbImgAttribute();
    }

    /**
     * 处理最新剩余报告数据
     *
     * @param int   $filterCnt
     * @param       $pageNum
     * @param int   $pageSize
     * @param       $query
     * @param       $list
     * @param       $forCnt
     *
     * @return mixed
     */
    private function handlerSurplusNewsList(
        $filterCnt, $pageNum, $pageSize, $query,
        &$list, &$forCnt
    ) {
        if ($forCnt <= 0 || (count($list) == $pageSize)) {
            return $list;
        }
        if ($filterCnt > 0) {
            //因为是下一页,所以不需要-1
            $pageNum++;
            $currentOffset = $pageNum * $pageSize;
            $surplusList = $query->offset($currentOffset)->limit($pageSize)->get();
            foreach ($surplusList as $key => $value) {
                if ($filterCnt <= 0) {
                    break;
                }
                // 过滤敏感词
                $checkRes = SenWordsService::checkFitter($value->name);
                if (!$checkRes) {
                    //正常报告
                    $this->handlerNewProductList($value);
                    $list[] = $value;
                    $filterCnt--;
                }
            }
        }
        if ($filterCnt > 0) {
            $forCnt -= 1;

            return $this->handlerSurplusNewsList($filterCnt, $pageNum, $pageSize, $query, $list, $forCnt);
        }

        return $list;
    }

    /**
     *
     * @param array $productFields
     * @param       $id
     * @param array $firstProduct
     * @param int   $forCnt
     *
     * @return mixed
     */
    private function getRemProductFirst($productFields, $id, &$firstProduct, &$forCnt) {
        if ($forCnt >= 6) {
            return $firstProduct;
        }
        $tempFirstProduct = Products::select($productFields)
                                    ->where('category_id', $id)
                                    ->where('show_recommend', 1)
                                    ->orderBy('published_date', 'desc')
                                    ->offset($forCnt)
                                    ->first();
        $forCnt += 1;
        if (!empty($tempFirstProduct)) {
            $checkRes = SenWordsService::checkFitter($tempFirstProduct->name);
            if ($checkRes) {
                return $this->getRemProductFirst($productFields, $id, $firstProduct, $forCnt);
            } else {
                $firstProduct = $tempFirstProduct;

                return $firstProduct;
            }
        }

        return [];
    }

    /**
     *
     * @param $id
     * @param $id
     *
     * @return mixed
     */
    private function getRemProductOtherList($cate_id, $productId) {
        $query = Products::select(['name', 'keywords', 'id', 'url'])
                         ->where('category_id', $cate_id)
                         ->where('show_recommend', 1)
                         ->where("status", 1)
                         ->where('id', '<>', $productId)
                         ->orderBy('published_date', 'desc');
        $pageSize = 4;
        $otherProducts = $query->limit($pageSize)
                               ->get()
                               ->toArray();

        return $otherProducts;
//        $filterCnt = 0;
//        foreach ($otherProducts as $key => $value) {
//            $checkRes = SenWordsService::checkFitter($value['name']);
//            if ($checkRes) {
//                $filterCnt++;
//                unset($otherProducts[$key]);
//            }
//        }
//        $forCnt = 4;
//        $this->handlerSurplusRemList($filterCnt, 0, $pageSize, $query, $otherProducts, $forCnt);
    }

    /**
     * 处理推荐剩余报告数据
     *
     * @param int   $filterCnt
     * @param       $pageNum
     * @param int   $pageSize
     * @param       $query
     * @param       $list
     * @param       $forCnt
     *
     * @return mixed
     */
    private function handlerSurplusRemList(
        $filterCnt, $pageNum, $pageSize, $query,
        &$list, &$forCnt
    ) {
        //循环次数超过直接返回
        if ($forCnt <= 0 || (count($list) == $pageSize)) {
            return $list;
        }
        if ($filterCnt > 0) {
            //因为是下一页,所以不需要-1
            $pageNum++;
            $currentOffset = $pageNum * $pageSize;
            $surplusList = $query->offset($currentOffset)->limit($pageSize)->get();
            foreach ($surplusList as $key => $value) {
                if ($filterCnt <= 0) {
                    break;
                }
                $checkRes = SenWordsService::checkFitter($value['name']);
                if (!$checkRes) {
                    $filterCnt--;
                    $list[] = $value;
                }
            }
        }
        if ($filterCnt > 0) {
            $forCnt -= 1;

            return $this->handlerSurplusRemList($filterCnt, $pageNum, $pageSize, $query, $list, $forCnt);
        }

        return $list;
    }
}
