<?php

namespace App\Http\Controllers;

use App\Models\Common;
use App\Models\News;
use App\Models\Office;
use App\Models\Partner;
use App\Models\Products;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ProductDescription;
use App\Models\ProductsCategory;
use App\Services\SenWordsService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * 首页报告
     * @param Request $request
     *
     */
    public function index(Request $request)
    {
        //最新报告(热门报告)
        $data['hot_product_list'] = $this->getHotProductList($request);

        //获取推荐报告
        $data['recommend_product_list'] = $this->getRecommendProductList($request);

        //合作伙伴接口
        $data['partner_list'] = $this->getPartnerList($request);

        //行业新闻
        $data['industry_news_list'] = $this->getIndustryNews($request);

        // 客户评价
        $data['comment'] = $this->getCustomersComment($request);

        ReturnJson(true, '', $data);
    }



    // 最新报告(热门报告)
    public function NewsProduct(Request $request)
    {
        $list = $this->getHotProductList($request);
        ReturnJson(true, '', $list);
    }

    // 推荐报告
    public function RecommendProduct(Request $request)
    {
        $data = $this->getRecommendProductList($request);
        ReturnJson(true, 'success', $data);
    }

    // 行业新闻
    public function RecommendNews(Request $request)
    {
        $list = $this->getIndustryNews($request);
        ReturnJson(true, '', $list);
    }

    // 合作伙伴
    public function partners(Request $request)
    {
        $list = $this->getPartnerList($request);
        ReturnJson(true, '', $list);
    }

    // 客户评价
    public function customersComments(Request $request)
    {
        $list = $this->getCustomersComment($request);
        ReturnJson(true, '', $list);
    }

    // 办公室
    public function office(Request $request)
    {
        $list = Office::where('status', 1)
            ->select(
                [
                    'id', 'city', 'name', 'language_alias', 'region', 'area', 'image', 'national_flag', 'phone',
                    'address', 'working_language', 'working_time', 'time_zone'
                ]
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
    public function WebpageOrientation(Request $request)
    {
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
    private function handlerNewProductList(Products $value): void
    {
        /**
         * @var $value Products
         */
        $published_date = $value['published_date'];
        $description = (new ProductDescription(date('Y', strtotime($published_date))))->where(
            'product_id',
            $value['id']
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
        $filterCnt,
        $pageNum,
        $pageSize,
        $query,
        &$list,
        &$forCnt
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
    private function getRemProductFirst($productFields, $id, &$firstProduct, &$forCnt)
    {
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
    private function getRemProductOtherList($cate_id, $productId)
    {
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
        $filterCnt,
        $pageNum,
        $pageSize,
        $query,
        &$list,
        &$forCnt
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

    /**
     *
     * @param Request $request
     * @return array
     */
    private function getHotProductList(Request $request)
    {
        /** 
         *  0: 默认只返回报告数据
         *  1: 返回分类数据以及单个分类的报告数据
         *  2: 返回报告分类及每个报告分类的数据
         */
        $dataType = $request->hot_data_type ?? 0;
        // 返回分类数量，在 dataType 为 1|2 时
        $categoryLimit = $request->hot_category_size ?? 4;
        // 返回报告数量/每个分类的报告数量
        $productLimit = $request->hot_product_size ?? 6;
        // dataType 为 1 时，页面根据点击分类选项卡切换报告数据
        $categoryID =  $request->hot_category_id ?? 0;

        $data = [];

        // 报告基本查询
        $productSelect = ['id', 'thumb', 'name', 'keywords', 'category_id', 'published_date', 'price', 'url',];
        $productQuery = Products::where('status', 1)
            ->where("show_hot", 1)
            ->where("published_date", "<=", time())
            ->orderBy('sort', 'asc') // 排序权重：sort > 发布时间 > id
            ->orderBy('published_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($productLimit);

        // 分类基本查询
        $categoryQuery = ProductsCategory::select(['id', 'name', 'link', 'thumb', 'icon'])
            ->where('is_hot', 1)
            ->where('status', 1)
            ->where('pid', 0)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->limit($categoryLimit);

        if ($dataType == 0) {
            // $newProductList = $productQuery->get();
        } elseif ($dataType == 1) {
            //获取热门分类
            $categories  = $categoryQuery->get()->toArray();
            $data['category'] = $categories;
            // 没有传分类ID默认显示第一个分类报告数据
            if (empty($categoryID) && count($categories) > 0) {
                $productQuery = $productQuery->where('category_id', $categories[0]['id']);
            } elseif (!empty($categoryID)) {
                $productQuery = $productQuery->where('category_id', $categoryID);
            }
        } elseif ($dataType == 2) {
            //获取热门分类
            $categories  = $categoryQuery->get()->toArray();
            //遍历分类获取,分类报告
            if (!empty($categories) && is_array($categories)) {
                foreach ($categories as $index => $category) {
                    $data[$index]['category'] = [
                        'id'   => $category['id'],
                        'name' => $category['name'],
                        'url'  => $category['link'],
                    ];
                    $categoryID = $category['id'];
                    // 查询报告数据
                    $productList = (clone $productQuery)->select($productSelect)->where('category_id', $categoryID)->get()->toArray();
                    if (empty($productList)) {
                        $data[$index]['keywords'] = [];
                        $data[$index]['firstProduct'] = [];
                        $data[$index]['otherProducts'] = [];
                        continue;
                    }
                    // 提取报告关键词列表
                    $keywords = array_column($productList, 'keywords');
                    $data[$index]['keywords'] = $keywords;

                    //处理数据
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
        }

        if ($dataType == 0 || $dataType == 1) {

            $newProductList = $productQuery->select($productSelect)->get();

            foreach ($newProductList as $value) {
                $this->handlerNewProductList($value);
            }
            $data['products'] = $newProductList;
        }
        return $data;
    }

    /**
     *
     * @param Request $request
     * @return array
     */
    private function getRecommendProductList(Request $request)
    {
        /** 
         *  0: 默认只返回报告数据
         *  1: 返回分类数据以及单个分类的报告数据
         *  2: 返回报告分类及每个报告分类的数据
         */
        $dataType = $request->recommend_data_type ?? 0;
        // 返回分类数量，在 dataType 为 1|2 时
        $categoryLimit = $request->recommend_category_size ?? 4;
        // 返回报告数量/每个分类的报告数量
        $productLimit = $request->recommend_product_size ?? 6;
        // dataType 为 1 时，页面根据点击分类选项卡切换报告数据
        $categoryID =  $request->recommend_category_id ?? 0;

        $data = [];

        // 报告基本查询
        $productSelect = ['id', 'thumb', 'name', 'keywords', 'category_id', 'published_date', 'price', 'url',];

        $productQuery = Products::where("status", 1)
            ->where('show_recommend', 1)
            ->where("published_date", "<=", time())
            ->orderBy('sort', 'asc') // 排序权重：sort > 发布时间 > id
            ->orderBy('published_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($productLimit);

        // 分类基本查询
        $categoryQuery = ProductsCategory::select(['id', 'name', 'link', 'thumb', 'icon'])
            ->where('status', 1)
            ->where('is_recommend', 1)
            ->where('pid', 0)
            ->orderBy('sort', 'asc')
            ->orderBy('updated_at', 'desc')
            ->limit($categoryLimit);

        if ($dataType == 0) {
            // $newProductList = $productQuery->get();
        } elseif ($dataType == 1) {
            //获取推荐分类
            $categories  = $categoryQuery->get()->toArray();
            $data['category'] = $categories;
            // 没有传分类ID默认显示第一个分类报告数据
            if (empty($categoryID) && count($categories) > 0) {
                $productQuery = $productQuery->where('category_id', $categories[0]['id']);
            } elseif (!empty($categoryID)) {
                $productQuery = $productQuery->where('category_id', $categoryID);
            }
        } elseif ($dataType == 2) {
            //获取推荐分类
            $categories  = $categoryQuery->get()->toArray();
            //遍历分类获取,分类报告
            if (!empty($categories) && is_array($categories)) {
                foreach ($categories as $index => $category) {
                    $data[$index]['category'] = [
                        'id'   => $category['id'],
                        'name' => $category['name'],
                        'url'  => $category['link'],
                    ];
                    $categoryID = $category['id'];
                    // 查询报告数据
                    $productList = (clone $productQuery)->select($productSelect)->where('category_id', $categoryID)->get()->toArray();;
                    if (empty($productList)) {
                        $data[$index]['keywords'] = [];
                        $data[$index]['firstProduct'] = [];
                        $data[$index]['otherProducts'] = [];
                        continue;
                    }
                    // 提取报告关键词列表
                    $keywords = array_column($productList, 'keywords');
                    $data[$index]['keywords'] = $keywords;
                    //处理数据
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
        }

        if ($dataType == 0 || $dataType == 1) {

            $newProductList = $productQuery->select($productSelect)->get();

            foreach ($newProductList as $value) {
                $this->handlerNewProductList($value);
            }
            $data['products'] = $newProductList;
        }

        return $data;
    }

    /**
     *
     *
     * @return array
     */
    private function getPartnerList(Request $request): array
    {
        $limit = $request->partner_size ?? 20;
        $list = Partner::where('status', 1)
            ->select(['id', 'name', 'logo',])
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
        //        foreach ($list as &$item) {
        //            $item['logo'] = Common::cutoffSiteUploadPathPrefix($item['logo']);
        //        }

        return $list;
    }

    /**
     *
     *
     * @return array
     */
    private function getIndustryNews(Request $request): array
    {
        
        $limit = $request->news_size ?? 4;

        $list = News::where('status', 1)
            ->select(['id', 'thumb', 'title', 'description', 'upload_at', 'url'])
            ->where('show_home', 1) // 是否在首页显示
            ->where('upload_at', '<=', time())
            //->orderBy('sort', 'desc')
            ->orderBy('upload_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
        if ($list) {
            foreach ($list as $key => $item) {
                $list[$key]['upload_at_format'] = date('Y-m-d', $item['upload_at']);
                $list[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($item['thumb']);
            }
        }

        return $list;
    }

    
    /**
     *
     *
     * @return array
     */
    private function getCustomersComment(Request $request): array
    {
        
        $limit = $request->comment_size ?? 4;

        $list = Comment::where('status', 1)
            ->select(['id', 'image', 'title', 'notes', 'comment_at'])
            ->where('status', 1) 
            //->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
        if ($list) {
            foreach ($list as $key => $item) {
                $list[$key]['comment_at_format'] = date('Y-m-d', $item['comment_at']);
                $list[$key]['image'] = Common::cutoffSiteUploadPathPrefix($item['image']);
            }
        }

        return $list;
    }

}
