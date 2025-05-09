<?php

namespace App\Http\Controllers;

use App\Models\DateFilter;
use App\Models\Plate;
use App\Models\PlateValue;
use App\Models\Publishers;
use App\Models\SearchRank;
use App\Models\System;
use App\Models\ViewProductsLog;
use App\Services\IPAddrService;
use App\Services\SenWordsService;
use App\Services\SphinxService;
use Foolz\SphinxQL\Drivers\Mysqli\Connection;
use Foolz\SphinxQL\Expression;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Helper\XunSearch;
use App\Models\Common;
use App\Models\CurrencyConfig;
use App\Models\Languages;
use App\Models\News;
use App\Models\PriceEditions;
use App\Models\PriceEditionValues;
use App\Models\ProductDescription;
use App\Models\ProductPdf;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\SystemValue;
use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Support\Facades\Redis;
use IP2Location\Database;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller {
    // 获取报告列表信息
    public function List(Request $request) {
        try {
            $page = $request->page ? intval($request->page) : 1; // 页码
            $pageSize = $request->pageSize ? intval($request->pageSize) : 10; // 每页显示数量
            $category_id = $request->category_id ?? 0; // 分类ID
            $keyword = trim($request->keyword) ?? null; // 搜索关键词
            if (!empty($keyword)) {
                $keyword = phpDecodeURIComponent($keyword);
                //点击关键词一次, 需要增加一次点击次数
                SearchRank::query()->where('name', $keyword)->increment('hits');
            }
            $categorySeoInfo = [
                'category_name'   => '',
                'seo_title'       => '',
                'seo_keyword'     => '',
                'seo_description' => '',
            ];
            $input_params = $request->input();
            if (!empty($input_params['date_id'])) {
                $date_info = DateFilter::find($input_params['date_id']);
                if (!empty($date_info)) {
                    if (empty($date_info['date_end'])) {
                        $input_params['published_date'][] = 0;
                    } else {
                        $input_params['published_date'][] = time() - 86400 * $date_info['date_end'];
                    }
                    if (empty($date_info['date_begin'])) {
                        $input_params['published_date'][] = time();
                    } else {
                        $input_params['published_date'][] = time() - 86400 * $date_info['date_begin'];
                    }
                }
            }
            if (!empty($category_id)) {
                $categorySeoData = ProductsCategory::query()
                                                   ->select(
                                                       [
                                                           'name as category_name',
                                                           'seo_title',
                                                           'seo_keyword',
                                                           'seo_description'
                                                       ]
                                                   )
                                                   ->where('id', $category_id)->first();
                if (!empty($categorySeoData)) {
                    $categorySeoInfo = $categorySeoData->toArray();
                    // 统一格式，null改成空串
                    $categorySeoInfo['category_name'] = !empty($categorySeoInfo['category_name'])
                        ? $categorySeoInfo['category_name'] : '';
                    $categorySeoInfo['seo_title'] = !empty($categorySeoInfo['seo_title'])
                        ? $categorySeoInfo['seo_title']
                        : '';
                    $categorySeoInfo['seo_keyword'] = !empty($categorySeoInfo['seo_keyword'])
                        ? $categorySeoInfo['seo_keyword'] : '';
                    $categorySeoInfo['seo_description'] = !empty($categorySeoInfo['seo_description'])
                        ? $categorySeoInfo['seo_description'] : '';
                }
            }
            $res = $this->GetProductResult($page, $pageSize, $keyword, $category_id, $input_params);
            $result = $res['list'];
            $count = $res['count'];
            $searchEngine = $res['type'] ?? '';
            $products = [];
            if ($result) {
                $languages = Languages::GetList();
                $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
                // 报告数据
                $ids = array_column($result, 'id');
                $productsDataArray = Products::query()->whereIn('id', $ids)->get()->toArray();
                $productsDataArray = array_column($productsDataArray, null, 'id');
                // 提取这些报告的出版商id，统一查出涉及的价格版本
                $publisherIdArray = array_unique(array_column($productsDataArray, 'publisher_id'));
                $priceEdition = Products::getPriceEdition($publisherIdArray, $languages);
                // 需要额外查询多种货币的价格（日文）
                $currencyData =CurrencyConfig::query()->select(['id','code','is_first','exchange_rate','tax_rate'])->get()?->toArray()??[];
                
                // 价格版本缓存
                foreach ($result as $key => $value) {
                    //报告数据
                    $time = time();
                    if (empty($productsDataArray[$value['id']])) {
                        unset($result[$key]);
                        continue;
                    }
                    $productsData = $productsDataArray[$value['id']];
                    //判断当前报告是否在优惠时间内
                    if ($productsData['discount_time_begin'] <= $time && $productsData['discount_time_end'] >= $time) {
                        $value['discount_status'] = 1;
                    } else {
                        $value['discount_status'] = 0;
                        // 过期需返回正常的折扣
                        $productsData['discount_amount'] = 0;
                        $productsData['discount'] = 100;
                        $productsData['discount_time_begin'] = null;
                        $productsData['discount_time_end'] = null;
                    }
                    $value['pages'] = $productsData['pages'];
                    $value['thumb'] = $productsData['thumb'];
                    $value['discount'] = $productsData['discount'];
                    $value['discount_amount'] = $productsData['discount_amount'];
                    $value['discount_type'] = $productsData['discount_type'];
                    $value['discount_time_begin'] = $productsData['discount_time_begin'];
                    $value['discount_time_end'] = $productsData['discount_time_end'];
                    //分类
                    $category = ProductsCategory::select(['id', 'name', 'link', 'thumb'])->find($value['category_id']);
                    if (empty($value['thumb']) && !empty($category)) {
                        $value['thumb'] = $category['thumb'];
                    }
                    if (empty($value['thumb'])) {
                        // 若报告图片为空，则使用系统设置的默认报告高清图
                        $value['thumb'] = !empty($defaultImg) ? $defaultImg : '';
                    }
                    $value['thumb'] = Common::cutoffSiteUploadPathPrefix($value['thumb']);
                    if (is_numeric($value['published_date'])) {
                        $suffix = date('Y', $value['published_date']);
                        $value['published_date'] = $value['published_date'] ? date(
                            'Y-m-d',
                            $value['published_date']
                        ) : '';
                    } else {
                        $suffix = date('Y', strtotime($value['published_date']));
                        $value['published_date'] = $value['published_date'] ? date(
                            'Y-m-d',
                            strtotime($value['published_date'])
                        ) : '';
                    }
                    $description = (new ProductDescription($suffix))->where('product_id', $value['id'])->value(
                        'description'
                    );
                    if (checkSiteAccessData(['mrrs', 'yhen' , 'qyen'])) {
                        $strIndex = strpos($description, "\n");
                        if ($strIndex !== false) {
                            // 使用 substr() 函数获取第一个段落
                            $description = substr($description, 0, $strIndex);
                        }
                    } else {
                        $description = mb_substr($description, 0, 120, 'UTF-8');
                    }
                    $value['description'] = $description;
                    $value['category'] = $category ? [
                        'id'   => $category['id'],
                        'name' => $category['name'],
                        'link' => $category['link'],
                    ] : [];
                    $publisher_id = $productsData['publisher_id'];
                    $value['prices'] = Products::countPriceEditionPrice($priceEdition[$publisher_id], $value['price'],$currencyData)??[];
                    
                    if ($currencyData && count($currencyData) > 0) {

                        // 默认版本的多种货币的价格
                        if ($currencyData && count($currencyData)) {
                            foreach ($currencyData as $currencyItem) {
                                $currencyKey = strtolower($currencyItem['code']) . '_price';
                                $value[$currencyKey] = $value['price'] * $currencyItem['exchange_rate'];
                            }
                        }
                    }
                    $products[] = $value;
                }
            }
            if (checkSiteAccessData(['tycn', 'qyen'])) {
                $productCagoryId = $this->GetProductCateList($keyword, 0);
                $productCagory = $this->getProductCagory($productCagoryId);
            } else {
                $productCagory = $this->getProductCagory([]);
            }
            $data = [
                'productCagory'   => $productCagory,
                'products'        => $products,
                "page"            => intVal($page),
                "pageSize"        => intVal($pageSize),
                "count"           => intVal($count),
                'pageCount'       => ceil($count / $pageSize),
                'categorySeoInfo' => $categorySeoInfo,
                'searchEngine'    => $searchEngine //搜索引擎
            ];
            if (checkSiteAccessData(['mrrs'])) {
                //获取日期下拉列表
                $data['date_conditin_list'] = $this->getDateConditinList($input_params);
                //获取出版商下拉列表
                $data['published_list'] = $this->getPublishedList($input_params);
            }
            ReturnJson(true, '请求成功', $data);
        } catch (\Exception $e) {
            ReturnJson(false, '未知错误'.$e->getMessage(), []);
        }
    }

    /**
     * 搜索产品数据
     */
    private function GetProductResult($page, $pageSize, $keyword = '', $category_id = 0, $input_params = []) {
        try {
            $hidden = SystemValue::where('key', 'sphinx')->value('hidden');
            if ($hidden == 1) {
                return $this->SearchForSphinx($category_id, $keyword, $page, $pageSize, $input_params);
            } else {
                return $this->SearchForMysql($category_id, $keyword, $page, $pageSize, $input_params);
            }
        } catch (\Exception $e) {
            \Log::error('应用端查询失败,异常信息为:'.json_encode([$e->getMessage()]));
            ReturnJson(false, '请求失败,请稍后再试');
        }
    }

    /**
     * 搜索产品分类数据
     */
    private function GetProductCateList($keyword = '', $category_id = 0) {
        try {
            $hidden = SystemValue::where('key', 'sphinx')->value('hidden');
            if ($hidden == 1) {
                return $this->getCateIdListBySphinx($category_id, $keyword);
            } else {
                return $this->getCateIdByCondition($category_id, $keyword);
            }
        } catch (\Exception $e) {
            \Log::error('应用端查询失败,异常信息为:'.json_encode([$e->getMessage()]));
            ReturnJson(false, '请求失败,请稍后再试');
        }
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

    public function handlerSurplusProductList(
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
            $surplusList = $query->offset($currentOffset)->limit($pageSize)->get()->toArray();
            foreach ($surplusList as $key => $value) {
                if ($filterCnt <= 0) {
                    break;
                }
                // 过滤敏感词
                $checkRes = SenWordsService::checkFitter($value['name']);
                if (!$checkRes) {
                    //正常报告
                    $list[] = $value;
                    $filterCnt--;
                }
            }
        }
        if ($filterCnt > 0) {
            $forCnt -= 1;

            return $this->handlerSurplusProductList($filterCnt, $pageNum, $pageSize, $query, $list, $forCnt);
        }

        return $list;
    }

    // 报告详情
    public function Description(Request $request) {
        $product_id = $request->product_id;
        $url = $request->url;
        if (empty($product_id)) {
            ReturnJson(false, '产品ID不允许为空！', []);
        }
        $product = Products::where(['id' => $product_id, 'status' => 1])->select(
            ['id', 'thumb', 'name', 'keywords', 'category_id', 'published_date']
        )->first();
        //url重定向 如果该文章已删除则切换到url一致的文章，如果没有url一致的则返回报告列表
        if (!empty($product) && $product->published_date->timestamp < time()) {
            // 浏览数+1
            Products::where(['id' => $product_id])->increment('hits');
            //增加详情的浏览记录
            $this->viewLog($product);
            $fieldList = [
                'p.name',
                'p.english_name',
                'cate.thumb',
                'cate.home_thumb',
                'p.id',
                'p.published_date',
                'cate.name as category',
                'cate.name as category_name',
                'cate.keyword_suffix',
                'cate.product_tag',
                'p.pages',
                'p.tables',
                'p.year',
                'p.future_scale',
                'p.current_scale',
                'p.last_scale',
                'p.cagr',
                'p.classification',
                'p.application',
                'p.url',
                'p.category_id',
                'p.keywords',
                'p.price',
                'p.discount_type',
                'p.discount',
                'p.discount_amount',
                'p.discount_time_begin',
                'p.discount_time_end',
                'p.publisher_id',
                'p.hits',
                'p.downloads',
            ];
            $product_desc = (new Products)->from('product_routine as p')->select($fieldList)->leftJoin(
                'product_category as cate',
                'cate.id',
                '=',
                'p.category_id'
            )
                                          ->where(['p.id' => $product_id])
                                          ->where('p.status', 1)
                                          ->first()->toArray();
            if (checkSiteAccessData(['mrrs'])) {
                $product_desc['publisher'] = Publishers::query()->where("id", $product_desc['publisher_id'])->value(
                    "name"
                );
            }
            //返回打折信息
            $time = time();
            //判断当前报告是否在优惠时间内
            if ($product_desc['discount_time_begin'] <= $time && $product_desc['discount_time_end'] >= $time) {
                $product_desc['discount_status'] = 1;
            } else {
                $product_desc['discount_status'] = 0;
                // 过期需返回正常的折扣
                $product_desc['discount_amount'] = 0;
                $product_desc['discount'] = 100;
                $product_desc['discount_time_begin'] = null;
                $product_desc['discount_time_end'] = null;
            }
            // //返回相关报告
            // if (!empty($product_desc['keywords'])) {
            //     $relatedProList = Products::query()->select(
            //         [
            //             "id", "published_date", "name", "thumb", "url", "keywords", "english_name", "category_id",
            //             "author"
            //         ]
            //     )
            //     ->where("keywords", $product_desc['keywords'])
            //         ->where("id", "<>", $product_id)
            //         ->where("published_date", "<=", time())
            //         ->where("status", 1)
            //         ->orderBy("published_date", "desc")
            //         ->limit(2)
            //         ->get();
            //     foreach ($relatedProList as $item) {
            //         $item->thumb = $item->getThumbImgAttribute();
            //         $item->short_desc = $item->getProShortDescAttribute();
            //         $item->category_text = $item->getCategotyTextAttribute();
            //     }
            //     $product_desc['relatedProList'] = $relatedProList;
            // } else {
            //     $product_desc['relatedProList'] = [];
            // }
            //报告详情数据处理
            $suffix = date('Y', strtotime($product_desc['published_date']));
            $description = (new ProductDescription($suffix))->select([
                'description',
                'description_en',
                'table_of_content',
                'table_of_content_en',
                'tables_and_figures',
                'tables_and_figures_en',
                'companies_mentioned',
            ])->where('product_id', $product_id)->first();
            if ($description === null) {
                $description = [];
                $description['description'] = '';
                $description['table_of_content'] = '';
                $description['tables_and_figures'] = '';
                $description['companies_mentioned'] = '';
                $description['description_en'] = '';
                $description['tables_and_figures_en'] = '';
                $description['table_of_content_en'] = '';
            }

            // lpijp网站动态生成日文详情
            if(checkSiteAccessData(['lpijp'])){
                $description['description'] = $this->getDescriptionByTemplate($product_desc,$description);
            }
            

            
            $desc = [];
            if (!empty($product_desc) && !empty($description)) {
                $description['description'] = str_replace(['<pre>', '</pre>'], '', $description['description']);
                $_description = $this->setDescriptionLinebreak($description['description']);
                $_description_en = $this->setDescriptionLinebreak($description['description_en']);
                $product_desc['description'] = $_description;
                $product_desc['description_en'] = $_description_en;
                $product_desc['table_of_content'] = $this->titleToDeep($description['table_of_content']);
                $product_desc['table_of_content_en'] = $this->titleToDeep($description['table_of_content_en']);
                $product_desc['table_of_content2'] = $this->titleToDeep(
                    $_description,
                    $description['table_of_content']
                );
                $product_desc['table_of_catalogue'] = $product_desc['table_of_content2'];
                $product_desc['tables_and_figures'] = str_replace(
                    ['<pre>', '</pre>'],
                    '',
                    $description['tables_and_figures']
                );
                $product_desc['tables_and_figures_en'] = str_replace(
                    ['<pre>', '</pre>'],
                    '',
                    $description['tables_and_figures_en']
                );
                $product_desc['companies_mentioned'] = str_replace(
                    ['<pre>', '</pre>'],
                    '',
                    $description['companies_mentioned']
                );
                // 服务方式文本
                $serviceMethod = SystemValue::select(['name as key', 'value'])->where(
                    ['key' => 'Service', 'status' => 1]
                )->first();
                $product_desc['serviceMethod'] = $serviceMethod ?? '';
                // 支付方式文本
                $payMethod = SystemValue::select(['name as key', 'value'])->where(
                    ['key' => 'PayMethod', 'status' => 1]
                )->first();
                $product_desc['payMethod'] = $payMethod ?? '';
                // 报告语言文本
                $reportLanguage = SystemValue::select(['name as key', 'value'])->where(
                    ['key' => 'ReportLanguage', 'status' => 1]
                )->first();
                $product_desc['reportLanguage'] = $reportLanguage ?? '';
            }

            // 需要额外查询多种货币的价格（日文）
            $currencyData = CurrencyConfig::query()->select(['id', 'code', 'is_first', 'exchange_rate', 'tax_rate'])->get()?->toArray() ?? [];
            $product_desc['prices'] = Products::CountPrice($product_desc['price'], $product_desc['publisher_id'], null, null, null, $currencyData);
            if ($currencyData && count($currencyData) > 0) {

                // 默认版本的多种货币的价格
                if ($currencyData && count($currencyData)) {
                    foreach ($currencyData as $currencyItem) {
                        $currencyKey = strtolower($currencyItem['code']) . '_price';
                        $product_desc[$currencyKey] = $product_desc['price'] * $currencyItem['exchange_rate'];
                        $currencyRateKey = strtolower($currencyItem['code']) . '_rate';
                        $product_desc[$currencyRateKey] = $currencyItem['exchange_rate'];
                    }
                }
            }

            $product_desc['description'] = $product_desc['description'];
            $product_desc['seo_description'] = is_array($product_desc['description'])
                                               && count(
                                                      $product_desc['description']
                                                  ) > 0 ? $product_desc['description'][0] : '';
            $product_desc['url'] = $product_desc['url'];
            //$product_desc['thumb'] = Common::cutoffSiteUploadPathPrefix($product->getThumbImgAttribute());
            $product_desc['thumb'] = $product->getThumbImgAttribute();
            if (empty($product_desc['thumb'])) {
                // 若报告图片为空，则使用系统设置的默认报告高清图
                $defaultImg = SystemValue::where('key', 'default_report_high_img')->value('value');
                $product_desc['thumb'] = !empty($defaultImg) ? $defaultImg : '';
            }
            $product_desc['published_date'] = $product_desc['published_date'] ? date(
                'Y-m-d',
                strtotime(
                    $product_desc['published_date']
                )
            ) : '';
            //产品关键词 开始
            if (!empty($product_desc['keyword_suffix'])) {
                $keyword_suffixs = explode(',', $product_desc['keyword_suffix']);
                if (!empty($keyword_suffixs) && is_array($keyword_suffixs)) {
                    $seo_keyword = '';
                    $separator = ''; // 分隔符
                    // echo '<pre>';print_r($keyword_suffixs);exit;
                    foreach ($keyword_suffixs as $keyword_suffix) {
                        $seo_keyword .= $separator.$product_desc['keywords'].$keyword_suffix;
                        $separator = '，';
                    }
                }
            } else {
                $seo_keyword = $product_desc['keywords'];
            }
            $product_desc['seo_keyword'] = $seo_keyword;
            //产品关键词 结束
            //产品标签 开始
            $product_desc['tag'] = explode(',', $product_desc['keywords']);
            if ((!$product_desc['tag'] || count($product_desc['tag']) <= 1) && $product_desc['product_tag']) {
                $product_desc['tag'] = array_merge($product_desc['tag'], explode(',', $product_desc['product_tag']));
            }
            unset($product_desc['product_tag']);
            $product_desc['isSphinx'] = false;
            //相关报告
            $relevant_products_size = $request->input('relevant_products_size', 2);
            $product_desc['relevant_products'] = $this->getRelevantByProduct(
                $product['keywords'],
                $product_id,
                $relevant_products_size
            );
            if (checkSiteAccessData(['qyen'])) {
                $product_desc['future_year'] = $product_desc['year'] + 6;
                $product_desc['current_year'] = $product_desc['year'] + 0;
                $product_desc['old_year'] = $product_desc['year'] - 1;
                $product_desc['line_description'] = 'CAGR ' . $product_desc['current_year'] . '-' . $product_desc['future_year'] . ':' . $product_desc['cagr'];   //箭头描述

                $product_desc_other_set_list = SystemValue::query()->where("alias", 'product_desc_other_set')->get()
                                                          ->keyBy('key')->toArray();
                $descriptionSetting = $product_desc_other_set_list['region']['value'];
                $product_desc['region'] = $descriptionSetting ? explode(
                    "\n", str_replace("\r\n", "\n", $descriptionSetting)
                ) : [];
                $product_desc['units'] = $product_desc_other_set_list['units']['value'];
                $product_desc['coverage'] = $product_desc_other_set_list['coverage']['value'];
                $product_desc['charts_title'] = $product_desc['keywords'] . ' ' . ($product_desc_other_set_list['charts_title']['value'] ?? '').'(US$)';
                $product_desc['charts_logo'] = $product_desc_other_set_list['bottomLogo']['value'] ?? '';

                $product_desc['classification'] = $product_desc['classification'] ? explode(
                    "\n", str_replace(
                            "", '',
                            str_replace("\t", '', trim(str_replace("\r\n", "\n", $product_desc['classification']), "\n"))
                        )
                ) : [];
                $product_desc['classification'] = array_filter($product_desc['classification'], function ($value) {
                    return $value !== "";
                });
                $product_desc['application'] = $product_desc['application'] ? explode(
                    "\n", str_replace(
                            "", '', str_replace("\t", '', trim(str_replace("\r\n", "\n", $product_desc['application']), "\n"))
                        )
                ) : [];
                $product_desc['application'] = array_filter($product_desc['application'], function ($value) {
                    return $value !== "";
                });

                //详情
                $product_desc['seo_description'] = $this->strDescription($description['description']);
                // 文本、样式替换
                $descriptionText = $description['description'];
                $descriptionText = str_replace('Highlights', '', $descriptionText);
                $descriptionText = str_replace('Market Segmentation', "<span style=\"font-weight: bold;font-size:16px;\">Market Segmentation</span>", $descriptionText);
                $descriptionText = str_replace('Chapter Outline', "<span style=\"font-weight: bold;padding-bottom: 10px;padding-top: 10px;display: inline-block;font-size: 16px;\">Chapter Outline</span>", $descriptionText);

                $descriptionText = str_replace('Why This Report?', "<span style=\"font-weight: 700;padding-bottom: 20px;padding-top: 20px;display: inline-block;font-size: 16px;\">Why This Report?</span>", $descriptionText);

                $descriptionText = trim($descriptionText);
                $product_desc['description'] = $this->spiltDescription($descriptionText);
                $product_desc['table_of_content'] = $this->titleToDeep($description['table_of_content']);

                // 文本、样式替换
                $tablesAndFiguresText = $description['tables_and_figures'];
                $tablesAndFiguresText = str_replace('List of Figures', "<span style=\"font-weight: bold;font-size:16px;\">List of Figures</span>", $tablesAndFiguresText);
                $tablesAndFiguresText = str_replace('鈥', "", $tablesAndFiguresText);
                $tablesAndFiguresText = trim($tablesAndFiguresText);
                $product_desc['tables_and_figures'] = $tablesAndFiguresText;

                $description['companies_mentioned'] = str_replace("\t", '', str_replace("", '', str_replace("\r\n", "\n", $description['companies_mentioned'])));
                if ($description['companies_mentioned']) {
                    $description['companies_mentioned'] = explode("\n", $description['companies_mentioned']);
                    $description['companies_mentioned'] = array_filter($description['companies_mentioned'], function ($value) {
                        return $value !== "";
                    });
                    $description['companies_mentioned'] = array_map(function ($item) {
                        return ' ' . trim($item);
                    }, $description['companies_mentioned']);
                    $product_desc['companies_mentioned'] = implode(',@,', $description['companies_mentioned']);
                    $product_desc['companies_mentioned'] = explode(',@,', $product_desc['companies_mentioned']);
                }

            }
            //产品标签 结束
            ReturnJson(true, '', $product_desc);
        } else {
            // 重定向能走sphinx优先执行，减轻数据库压力
            $product_desc = $this->GetRelevantProductResult(
                $product_id,
                $url,
                1,
                1,
                'url',
                ['id', 'url', 'published_date'],
                ['published_date' => 'desc', 'id' => 'desc']
            );
            if (!empty($product_desc) && is_array($product_desc) && count($product_desc) > 0) {
                $data = $product_desc[0];
                unset($data['published_date']);
                ReturnJson(1, '', $data);
            } else {
                ReturnJson(2, '请求失败');
            }
        }
    }

    // 相关报告
    public function Relevant(Request $request) {
        $product_id = $request->product_id ?? '';
        if (empty($product_id)) {
            ReturnJson(false, '产品ID不允许为空！', []);
        }
        //$product = Products::select(['keywords', 'published_date'])->where('id', $product_id)->first()->toArray(); //根据详情页这份报告的关键词匹配到其它报告（同一个关键词的一些报告，除了自己，其它报告就是相关报告）
        //        $start_time = date('Y-01-01 00:00:00', strtotime($product['published_date']));
        //        $end_time = date('Y-12-31 23:59:59', strtotime($product['published_date']));
        $keywords = Products::query()->where('id', $product_id)->value('keywords');
        $data = $this->getRelevantByProduct($keywords, $product_id);
        ReturnJson(true, '获取成功', $data);
    }

    // 更多资讯
    public function News(Request $request) {
        $data = [];
        $data = News::select([
                                 'id',
                                 'title',
                                 'url',
                                 'category_id as type'
                             ])
                    ->where(['status' => 1])
                    ->where('upload_at', '<=', time())
                    ->orderBy('created_at', 'desc')
                    ->limit(8)
                    ->get()
                    ->toArray();
        ReturnJson(true, '获取成功', $data);
    }


    /**
     * 商品摘要添加换行符
     * @param  description 摘要
     * @return  result 处理后的表格目录(含标题、摘要),以及一级目录数组
     */
    public function setNewDescriptionLinebreak($description)
    {
        $result = [];
        if (!empty($description)) {
            $description = trim($description, "\r\n");
            $description = trim($description, "\n");
            $description = trim($description, "\r");
            $descriptionArray = explode("\n", $description);
            foreach ($descriptionArray as $index => $row) {
                //清除多余换行
                $row = trim($row, "\n");
                $row = trim($row, "\r");
                $row = trim($row, "\r\n");
                //判断是否换行
                if (!empty($row) && strpos($row, ' ') === 0 && ($index + 1) != count($descriptionArray) && strpos($descriptionArray[$index + 1], ' ') !== 0) {
                    $result[$index] = $row . "\n";
                } elseif (!empty($row) && strrpos($row, '.') === (strlen($row) - 1) && strpos($row, ' ') !== 0 && strpos($row, 'Chapter') !== 0 && ($index + 1) != count($descriptionArray) && strpos($descriptionArray[$index + 1], 'Chapter') !== 0) {
                    $result[$index] = $row . "\n";
                } elseif ($row == "\n" || $row == "\r" || $row == "\r\n") {
                    // $descriptionArray[$index] = ""; //清除多余换行
                } elseif (!empty($row)) {
                    $result[$index] = $row;
                }
            }
        }
        return implode("\n", $result);
    }


    /**
     * 商品摘要添加换行符
     *
     * @param description 摘要
     *
     * @return  result 处理后的表格目录(含标题、摘要),以及一级目录数组
     */
    public function setDescriptionLinebreak($description) {
        $result = [];
        if (!empty($description)) {
            $descriptionArray = explode("\n", $description);
            foreach ($descriptionArray as $index => $row) {
                //清除多余换行
                $row = trim($row, "\n");
                $row = trim($row, "\r");
                $row = trim($row, "\r\n");
                //判断是否换行
                if (!empty($row) && strpos($row, ' ') === 0) {
                    $row = "&nbsp;&nbsp;&nbsp;&nbsp;".trim($row);
                }
                if (
                    !empty($row) && strpos($row, ' ') === 0 && ($index + 1) != count($descriptionArray)
                    && strpos(
                           $descriptionArray[$index + 1],
                           ' '
                       ) !== 0
                ) {
                    // $row = "&nbsp;&nbsp;".trim($row);
                    $result[] = $row;
                    $result[] = "<br />";
                } elseif (!empty($row) && strrpos($row, '。') && strpos($row, '（') !== 0) {
                    $result[] = $row;
                    $result[] = "<br />";
                } elseif ($row == "\n" || $row == "\r" || $row == "\r\n") {
                    // $descriptionArray[$index] = ""; //清除多余换行
                } elseif (!empty($row)) {
                    $result[] = $row;
                }
            }
        }

        return $result;
    }

    /**
     * 分割表格目录
     *
     * @param name 报告名称
     * @param description 摘要
     * @param toc 表格目录
     *
     * @return  result 处理后的表格目录(含标题、摘要),以及一级目录数组
     */
    public function titleToDeep($toc) {
        $pattern
            = '/(( {0,}(?<!\.)\d{1,2}(\.\d{1,2}){0,3})|(第(.{0,6}|\d{1,2})章)|( {0,}(?<!\.).{3,6}))( |\t).{0,}\n/u';
        $result = [];
        $match = [];
        try {
            preg_match_all($pattern, $toc, $match);
        } catch (\Throwable $th) {
            //throw $th;
            return '';
        }
        // $numPattern = '/ {0,}(?<!\.)\d{1,2}\.{0,1}\d{0,2}\.{0,1}\d{0,2} /';
        $numPattern = '/ {0,}(?<!\.)\d{1,2}(\.\d{1,2}){0,3}( |\t)/';
        if (is_array($match) && count($match) > 0 && count($match[0]) > 0) {
            $count = 0;
            foreach ($match[0] as $key => $value) {
                try {
                    preg_match_all($numPattern, $value, $numMatch);
                } catch (\Throwable $th) {
                    continue;
                    //throw $th;
                }
                if (!isset($numMatch[0][0])) {
                    //continue;
                    return '';
                }
                $num = str_replace(' ', '', $numMatch[0][0]);
                $value = trim($value, "\r\n");
                $value = trim($value, "\r");
                $value = trim($value, " ");
                if (!empty($value) && strpos($num, ".") === false) {
                    $count = $count + 1;
                    $result[$count] = ['id' => $count, 'title' => trim($value, "\n"), 'content' => ''];
                    // preg_match('/(?<!.)\d{1,2} /', trim($value, "\n"), $matchTitle);
                    preg_match('/(?<!.)\d{1,2}( |\t)/', trim($value, "\n"), $matchTitle);
                    $value = trim($value, "\r\n");
                    $value = trim($value, "\n");
                    $result[$count]['content'] .= '<span style="line-height:28px;font-size:16px;color:#333;font-weight:600;">'
                                                  .trim($value, "\n").'</span><br />';
                } else {
                    if (!isset($result[$count])) {
                        continue;
                    }
                    $value = trim($value, "\r\n");
                    $value = trim($value, "\n");
                    $space = '';
                    $str_count = substr_count($num, '.') ?? 0;
                    for ($i = 0; $i < $str_count; $i++) {
                        $space .= '    ';
                    }
                    $result[$count]['content'] .= $space.trim(str_replace("\n", "<br />", $value), "\n")."<br />";
                }
            }
        }

        return $result;
    }

    /**
     * 筛选条件
     */
    public function Filters(Request $request) {
        $industry_id = $request->industry_id;
        $model = new ProductsCategory();
        if (!empty($industry_id)) {
            $model = $model->where('industry_id', $industry_id);
        }
        $data = ProductsCategory::select([
                                             'id',
                                             'name',
                                             'link',
                                         ])
                                ->where('status', 1)
                                ->get()
                                ->toArray();
        array_unshift($data, [
            'id'   => '0',
            'name' => '全部',
            'link' => '',
        ]);
        ReturnJson(true, '', $data);
    }

    /**
     * 下载PDF
     */
    public function OutputPdf(Request $request) {
        $productId = $request->product_id;
        if (empty($productId)) {
            ReturnJson(false, 'product_id is empty');
        }
        $productsPdf = new ProductPdf();
        $productsPdf->setProductId($productId);
        $model = Products::find($productId);
        $model->downloads = $model->downloads ? $model->downloads + 1 : 1;
        $model->save();

        return $productsPdf->frontBuild();
    }

    /**
     *
     * @param mixed $category_id
     * @param mixed $keyword
     * @param       $page
     * @param       $pageSize
     *
     * @return array
     */
    private function SearchForMysql(mixed $category_id, mixed $keyword, $page, $pageSize, $input_params = []): array {
        $field = [
            'name',
            'english_name',
            'thumb',
            'published_date',
            'keywords',
            'id',
            'url',
            'price',
            'discount_type',
            'discount_time_begin',
            'discount_time_end',
            'discount',
            'discount_amount',
            'category_id',
            'publisher_id'
        ];
        $query = Products::where(['status' => 1])
                         ->where("published_date", "<", time())
                         ->select($field);
        // 分类ID
        if ($category_id) {
            $query = $query->where('category_id', $category_id);
        }
        // 关键词
        if ($keyword) {
            $keyWordArraySphinx = explode(" ", $keyword);
            $query = $query->where(function ($query) use ($keyWordArraySphinx) {
                foreach ($keyWordArraySphinx as $keyword) {
                    if (is_numeric($keyword)) {
                        $query->where('id', $keyword);
                    } else {
                        $query->where('name', 'like', '%'.$keyword.'%');
                    }
                }
            });
        }
        //出版商
        if (!empty($input_params['publisher_id'])) {
            $query = $query->where('publisher_id', intval($input_params['publisher_id']));
        }
        //出版时间
        if (!empty($input_params['published_date']) && is_array($input_params['published_date'])) {
            $published_date_list = array_map('intval', $input_params['published_date']);
            $query = $query->whereBetween('published_date', $published_date_list);
        }
        // 获取当前复合条件的总数量
        $count = $query->count();
        // 排序 显示发布时间 》 排序 》 id
        //$query = $query->orderBy('published_date', 'desc')->orderBy('sort', 'asc');
        $query = $query->orderBy('id', 'desc');
        // 分页
        $offset = ($page - 1) * $pageSize;
        $list = $query->offset($offset)->limit($pageSize)->get()->toArray();

        return ['list' => $list, 'count' => $count, 'type' => 'mysql'];
    }

    public function SearchForSphinx($category_id, $keyword, $page, $pageSize, $input_params = []) {
        $sphinxSrevice = new SphinxService();
        $conn = $sphinxSrevice->getConnection();
        $idProducts = $this->getProductById($conn, $category_id, $keyword, $input_params);
        //报告昵称,英文昵称匹配查询
        $query = (new SphinxQL($conn))->select('*')
                                      ->from('products_rt');
        if (!empty($input_params['orderBy'])) {
            // $query = $query->orderBy($input_params['orderBy'], 'asc');
            if (checkSiteAccessData(['mrrs'])) {
                if ($input_params['orderBy'] == 'price') {
                    $query = $query->orderBy($input_params['orderBy'], 'asc');
                } else {
                    $query = $query->orderBy($input_params['orderBy'], 'desc');
                }
            } else {
                if ($input_params['orderBy'] == 'time') {
                    $query = $query->orderBy('sort', 'asc')
                                   ->orderBy('published_date', 'asc')
                                   ->orderBy('id', 'desc');
                } elseif ($input_params['orderBy'] == 'price') {
                    $query = $query->orderBy('sort', 'asc')
                                   ->orderBy('price', 'asc')
                                   ->orderBy('id', 'desc');
                } else {
                    $query = $query->orderBy($input_params['orderBy'], 'asc');
                }
            }
        } else {
            if (!empty($keyword)) {
                $query = $query->orderBy('sort', 'asc')
                               ->orderBy('year', 'desc')
                               ->orderBy('degree_keyword', 'asc')
                               ->orderBy('published_date', 'desc')
                               ->orderBy('id', 'desc');
            } else {
                $query = $query->orderBy('sort', 'asc')
                               ->orderBy('published_date', 'desc')
                               ->orderBy('id', 'desc');
            }
        }
        $query = $query->where('status', '=', 1);
        $query = $query->where("published_date", "<", time());
        // 分类ID
        if (!empty($category_id)) {
            $query = $query->where('category_id', intval($category_id));
        }
        //精确搜索, 多字段匹配
        if (!empty($keyword)) {
//            $val = '"'.$keyword.'"';
//            $query->match(['name', 'english_name'], $val, true);
            $keyWordArraySphinx = explode(" ", $keyword);
            if (count($keyWordArraySphinx) > 0) {
                foreach ($keyWordArraySphinx as $val) {
                    $query->match(['name', 'english_name'], '"'.$val.'"', true);
                }
            }
        }
        //出版商
        if (!empty($input_params['publisher_id'])) {
            $query = $query->where('publisher_id', intval($input_params['publisher_id']));
        }
        //出版时间
        if (!empty($input_params['published_date']) && is_array($input_params['published_date'])) {
            $published_date_list = array_map('intval', $input_params['published_date']);
            $query = $query->where('published_date', 'BETWEEN', $published_date_list);
        }
        //查询总数
        $countQuery = $query->setSelect('COUNT(*) as cnt');
        $fetchNum = $countQuery->execute()->fetchNum();
        $count = $fetchNum[0] ?? 0;
        //查询结果分页
        $offset = ($page - 1) * $pageSize;
        $query->limit($offset, $pageSize);
        $query->option('max_matches', $offset + $pageSize);
        $query->setSelect('*');
        $result = $query->execute();
        $products = $result->fetchAllAssoc();
        if (!empty($idProducts)) {
            foreach ($idProducts as $forIdProducts) {
                array_unshift($products, $forIdProducts);
            }
        }
        $data = [
            'list'  => $products,
            'count' => intval($count) + count($idProducts),
            'type'  => 'sphinx'
        ];

        return $data;
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

    /**
     *
     * @param Connection|bool $conn
     * @param                 $category_id
     * @param                 $keyword
     *
     * @return array
     */
    private function getProductById(Connection|bool $conn, $category_id, $keyword, $input_params = []): array {
        //无关键词 或者关键词不是数字时，不返回数据
        if (empty($keyword) || !is_numeric($keyword)) {
            return [];
        }
        $query = (new SphinxQL($conn))->select('*')
                                      ->from('products_rt')
                                      ->orderBy('sort', 'asc')
                                      ->orderBy('published_date', 'desc');
        $query = $query->where('status', '=', 1);
        $query = $query->where("published_date", "<", time());
        // 分类ID
        if (!empty($category_id)) {
            $query = $query->where('category_id', intval($category_id));
        }
        //出版商
        if (!empty($input_params['publisher_id'])) {
            $query = $query->where('publisher_id', intval($input_params['publisher_id']));
        }
        //出版时间
        if (!empty($input_params['published_date']) && is_array($input_params['published_date'])) {
            $published_date_list = array_map('intval', $input_params['published_date']);
            $query = $query->where('published_date', 'BETWEEN', $published_date_list);
        }
        //id查询
        $idProducts = [];
        if (is_numeric($keyword)) {
            $idQuery = $query;
            $idQuery->where('id', intval($keyword));
            $idResult = $idQuery->execute();
            $idProducts = $idResult->fetchAllAssoc();
        }

        return $idProducts;
    }

    public function viewProductLog(Request $request) {
        ReturnJson(false, '该接口已废弃');
        $product_id = $request->product_id;
        if (empty($product_id)) {
            ReturnJson(false, 'product_id is empty');
        }
        $productInfo = Products::find($product_id);
        if (empty($productInfo)) {
            ReturnJson(false, '数据不存在');
        }
        $productInfo = $productInfo->toArray();
        $published_date = strtotime($productInfo['published_date']);
        if ($productInfo['status'] != 1 || $published_date > time()) {
            ReturnJson(false, '当前报告不被记录');
        }
        $this->viewLog($productInfo);
        ReturnJson(true, 'success');
    }

    public function viewLog($productInfo) {
        if (empty($productInfo['id'])) {
            return false;
        }
        $view_date_str = date("Y-m-d");
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (empty($user)) {
                $userId = 0;
            } else {
                $userId = $user->id;
            }
        } catch (\Exception $e) {
            $userId = 0;
        }
        $request = request();
        if (!empty($request->header('x-forwarded-for'))) {
            $ip = $request->header('x-forwarded-for');
        } elseif (!empty($request->header('client-ip'))) {
            $ip = $request->header('client-ip');
        } elseif (!empty($request->ip)) {
            $ip = $request->ip;
        } else {
            $ip = request()->ip();
        }
        $model = ViewProductsLog::query()->where("product_id", $productInfo['id'])
                                ->where("view_date_str", $view_date_str);
        if (!empty($userId)) {
            //同一个用户, 同一天, 同一个报告 , 只需要记录一次ip , 剩下的增加次数
            $model = $model->where('user_id', $userId);
        } else {
            $model = $model->where('user_id', 0)->where('ip', $ip);
        }
        $logId = $model->value("id");
        if ($logId > 0) {
            //增加次数
            ViewProductsLog::where(['id' => $logId])->increment('view_cnt');
        } else {
            //调用ip地址库的接口
            $ipAddr = (new IPAddrService($ip))->getAddrStrByIp();
            //增加日志
            $addData = [
                'user_id'       => $userId,
                'product_id'    => $productInfo['id'],
                'ip'            => $ip,
                'ip_addr'       => $ipAddr,
                'product_name'  => $productInfo['name'],
                'keyword'       => $productInfo['keywords'],
                'view_cnt'      => 1,
                'view_date_str' => $view_date_str,
            ];
            ViewProductsLog::create($addData);
        }
    }

    /**
     *
     *
     * @return mixed
     */
    private function getProductCagory($idList) {
        $field = ['id', 'name', 'link'];
        $data = ProductsCategory::select($field)
                                ->when(!empty($idList), function ($query) use ($idList) {
                                    $query->whereIn('id', $idList);
                                })
                                ->where('status', 1)
                                ->get()
                                ->toArray();
        array_unshift($data, [
            'id'   => '0',
            'name' => '全部',
            'link' => '',
        ]);

        return $data;
    }

    /**
     *
     * @param mixed   $keywords
     * @param mixed   $product_id
     * @param integer $relevant_products_size
     *
     * @return array
     */
    private function getRelevantByProduct(mixed $keywords, mixed $product_id, $relevant_products_size = 2): array {
        $select = [
            'id',
            'name',
            'english_name',
            'keywords',
            'url',
            'price',
            'pages',
            'discount_type',
            'discount',
            'discount_amount',
            'discount_time_begin',
            'discount_time_end',
            'publisher_id',
            'published_date',
            'thumb',
            'category_id',
        ];
        $products = $this->GetRelevantProductResult(
            $product_id,
            $keywords,
            1,
            $relevant_products_size,
            'keywords',
            $select
        );
        $data = [];
        if ($products) {
            // 分类信息
            $categoryIds = array_column($products, 'category_id');
            $categoryData = ProductsCategory::select(['id', 'name', 'thumb'])->whereIn('id', $categoryIds)->get()
                                            ->toArray();
            $categoryData = array_column($categoryData, null, 'id');
            // 默认图片
            // 若报告图片为空，则使用系统设置的默认报告高清图
            $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
            foreach ($products as $index => $product) {
                //每个报告加上分类信息
                $tempCategoryId = $product['category_id'];
                $product['category_name'] = isset($categoryData[$tempCategoryId])
                                            && isset($categoryData[$tempCategoryId]['name'])
                    ? $categoryData[$tempCategoryId]['name'] : '';
                $product['category_thumb'] = isset($categoryData[$tempCategoryId])
                                             && isset($categoryData[$tempCategoryId]['thumb'])
                    ? $categoryData[$tempCategoryId]['thumb'] : '';
                // 图片获取
                $tempThumb = '';
                if (!empty($product['thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($product['thumb']);
                } elseif (!empty($product['category_thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($product['category_thumb']);
                } else {
                    // 如果报告图片、分类图片为空，使用系统默认图片
                    $tempThumb = !empty($defaultImg) ? $defaultImg : '';
                }
                $data[$index]['thumb'] = $tempThumb;
                $data[$index]['price'] = $product['price'];
                $data[$index]['pages'] = $product['pages'];
                $data[$index]['discount_type'] = $product['discount_type'];
                $data[$index]['discount'] = $product['discount'];
                $data[$index]['discount_amount'] = $product['discount_amount'];
                $data[$index]['discount_time_begin'] = $product['discount_time_begin'];
                $data[$index]['discount_time_end'] = $product['discount_time_end'];
                $data[$index]['name'] = $product['name'];
                $data[$index]['keywords'] = $product['keywords'];
                $data[$index]['english_name'] = $product['english_name'];
                $suffix = date('Y', strtotime($product['published_date']));
                $data[$index]['description'] = (new ProductDescription($suffix))->where('product_id', $product['id'])
                                                                                ->value('description');
                $data[$index]['description'] = $data[$index]['description'] ? $data[$index]['description'] : '';
                $data[$index]['description'] = mb_substr($data[$index]['description'], 0, 100, 'UTF-8');
                $data[$index]['id'] = $product['id'];
                $data[$index]['url'] = $product['url'];
                $data[$index]['category_name'] = $product['category_name'];
                $data[$index]['published_date'] = $product['published_date'] ? date(
                    'Y-m-d',
                    strtotime($product['published_date'])
                ) : '';
                $data[$index]['prices'] = Products::CountPrice($product['price'], $product['publisher_id']);
            }
        }

        return $data;
    }

    public function customizedInfo() {
        try {
            $data = Plate::query()->select(['id', 'title', 'alias'])
                         ->whereIn('alias', ['customized_remark1', 'customized_remark2', 'customized_remark3'])
                         ->get()->toArray();
            if (empty($data)) {
                ReturnJson(false, '数据错误', []);
            }
            foreach ($data as $key => $section_item) {
                $data[$key]['content'] = [];
                if ($key == 'customized_remark2') {
                    $select = ['img_pc', 'title', 'content', 'img_icon'];
                } else {
                    $select = ['title', 'content'];
                }
                $content = PlateValue::query()
                                     ->select($select)
                                     ->where('parent_id', $section_item['id'])
                                     ->orderBy('sort', 'asc')
                                     ->get()->toArray();
                if ($content) {
                    if ($key == 'customized_remark2') {
                        $content = array_map(function ($item) {
                            $item['content'] = str_replace("\r\n", "\n", $item['content']);
                            $item['content'] = explode("\n", $item['content']);

                            return $item;
                        }, $content);;
                    }
                    $data[$key]['content'] = $content;
                }
                unset($data[$key]['alias']);
            }
            ReturnJson(true, '', $data);
        } catch (\Exception $e) {
            ReturnJson(false, '未知错误', $e->getMessage());
        }
    }

    /**
     *
     * @param mixed $category_id
     * @param mixed $keyword
     *
     * @return mixed
     */
    private function getCateIdByCondition(mixed $category_id, mixed $keyword) {
        $query = Products::where(['status' => 1])
                         ->where("published_date", "<", time());
        // 分类ID
        if (!empty($category_id)) {
            $query = $query->where('category_id', $category_id);
        }
        // 关键词
        if ($keyword) {
            $query = $query->where(function ($query) use ($keyword) {
                $query->where('name', 'like', '%'.$keyword.'%');
                if (is_numeric($keyword)) {
                    $query->orWhere('id', $keyword);
                }
            });
        }
        $cateIdList = $query->groupBy('category_id')->pluck('category_id')->toArray();

        return $cateIdList;
    }

    public function getCateIdListBySphinx($category_id, $keyword) {
        $sphinxSrevice = new SphinxService();
        $conn = $sphinxSrevice->getConnection();
        //报告昵称,英文昵称匹配查询
        $query = (new SphinxQL($conn))->select('*')
                                      ->from('products_rt');
        $query = $query->where('status', '=', 1);
        $query = $query->where("published_date", "<", time());
        // 分类ID
        if (!empty($category_id)) {
            $query = $query->where('category_id', intval($category_id));
        }
        //精确搜索, 多字段匹配
        if (!empty($keyword)) {
            if (is_numeric($keyword)) {
                $query->where('id', intval($keyword));
            } else {
                $keyWordArraySphinx = explode(" ", $keyword);
                if (count($keyWordArraySphinx) > 0) {
                    foreach ($keyWordArraySphinx as $val) {
                        $query->match(['name', 'english_name'], '"'.$val.'"', true);
                    }
                }
            }
        }
        $query->groupBy('category_id')->setSelect('category_id');
        $result = $query->execute();
        $cateIdList = $result->fetchAllAssoc();
        $data = array_column($cateIdList, 'category_id');

        return $data;
    }

    public function getDateConditinList($input_params) {
        $date_filter = DateFilter::query()->where('status', 1)->get()->toArray();
        $after_date_fiter_list = [];
        $db_date_list = $this->getDateFitterByCondition($input_params);
        if (!empty($db_date_list)) {
            $min_published_date = $db_date_list['min_published_date'];
            $max_published_date = $db_date_list['max_published_date'];
            $timestamp = time();
            foreach ($date_filter as $key => $value) {
                //多少天前时间戳
                $begin_day_time = $timestamp - $value['date_begin'] * 86400;
                $end_day_time = $timestamp - $value['date_end'] * 86400;
                if (empty($value['date_end'])) {
                    $end_day_time = 0;
                }
                if ($begin_day_time >= $min_published_date && $end_day_time <= $max_published_date) {
                    $new_data = [];
                    $new_data['date_id'] = $value['id'];
                    $new_data['date'] = $value['date_begin'].'~'.$value['date_end'].' Days';
                    $after_date_fiter_list[] = $new_data;
                }
            }
        }
        array_unshift($after_date_fiter_list, [
            'date_id' => '0',
            'date'    => 'All',
        ]);

        return $after_date_fiter_list;
    }

    public function getDateFitterByCondition($input_params) {
        try {
            $hidden = SystemValue::where('key', 'sphinx')->value('hidden');
            if ($hidden == 1) {
                //sphinx
                $query = $this->getSphinxQueryParms($input_params);
                $query->setSelect(
                    ['min(published_date) as min_published_date , max(published_date) as max_published_date']
                );
                $result = $query->execute();

                return $result->fetchAssoc();
            } else {
                $query = Products::where(['status' => 1])
                                 ->where("published_date", "<", time())
                                 ->selectRaw(
                                     'min(published_date) as min_published_date , max(published_date) as max_published_date'
                                 );
                // 分类ID
                if (!empty($input_params['category_id'])) {
                    $query = $query->where('category_id', intval($input_params['category_id']));
                }
                // 关键词
                if (!empty($input_params['keyword'])) {
                    $keyword = $input_params['keyword'];
                    $query = $query->where(function ($query) use ($keyword) {
                        $query->where('name', 'like', '%'.$keyword.'%');
                        if (is_numeric($keyword)) {
                            $query->orWhere('id', $keyword);
                        }
                    });
                }
                //出版商
                if (!empty($input_params['publisher_id'])) {
                    $query = $query->where('publisher_id', intval($input_params['publisher_id']));
                }
                //出版时间
                if (!empty($input_params['published_date']) && is_array($input_params['published_date'])) {
                    $published_date_list = array_map('intval', $input_params['published_date']);
                    $query = $query->whereBetween('published_date', $published_date_list);
                }

                return $query->first();
            }
        } catch (\Exception $e) {
            \Log::error('应用端查询失败,异常信息为:'.json_encode([$e->getMessage()]));
            ReturnJson(false, '请求失败,请稍后再试');
        }
    }

    public function getPublishedList($input_params) {
        try {
            $hidden = SystemValue::where('key', 'sphinx')->value('hidden');
            if ($hidden == 1) {
                $query = $this->getSphinxQueryParms($input_params);
                $query->groupBy('publisher_id')->setSelect(['publisher_id']);
                $result = $query->execute();
                $publisher_id_list = $result->fetchAllAssoc();
                $publisher_id_list = array_column($publisher_id_list, 'publisher_id');
            } else {
                $field = ['publisher_id'];
                $query = Products::where(['status' => 1])
                                 ->where("published_date", "<", time())
                                 ->select($field);
                // 分类ID
                if (!empty($input_params['category_id'])) {
                    $query = $query->where('category_id', intval($input_params['category_id']));
                }
                // 关键词
                if (!empty($input_params['keyword'])) {
                    $keyword = $input_params['keyword'];
                    $query = $query->where(function ($query) use ($keyword) {
                        $query->where('name', 'like', '%'.$keyword.'%');
                        if (is_numeric($keyword)) {
                            $query->orWhere('id', $keyword);
                        }
                    });
                }
                //出版商
                if (!empty($input_params['publisher_id'])) {
                    $query = $query->where('publisher_id', intval($input_params['publisher_id']));
                }
                //出版时间
                if (!empty($input_params['published_date']) && is_array($input_params['published_date'])) {
                    $published_date_list = array_map('intval', $input_params['published_date']);
                    $query = $query->whereBetween('published_date', $published_date_list);
                }
                $publisher_id_list = $query->groupBy('publisher_id')->pluck('publisher_id')->toArray();
            }
            $publishers_data = [];
            if (!empty($publisher_id_list)) {
                $publishers_data = Publishers::query()->where('status', 1)
                                             ->whereIn('id', $publisher_id_list)
                                             ->select(['name', 'id'])->get()->toArray();
            }
            array_unshift($publishers_data, [
                'id'   => '0',
                'name' => 'All',
            ]);

            return $publishers_data;
        } catch (\Exception $e) {
            \Log::error('应用端查询失败,异常信息为:'.json_encode([$e->getMessage()]));
            ReturnJson(false, '请求失败,请稍后再试');
        }
    }

    /**
     *
     * @param          $input_params
     *
     * @return SphinxQL
     */
    private function getSphinxQueryParms($input_params): SphinxQL {
        $sphinxSrevice = new SphinxService();
        $conn = $sphinxSrevice->getConnection();
        $query = (new SphinxQL($conn))->select('*')
                                      ->from('products_rt');
        $query = $query->where('status', '=', 1);
        $query = $query->where("published_date", "<", time());
        if (!empty($input_params['category_id'])) {
            $query = $query->where('category_id', intval($input_params['category_id']));
        }
        //精确搜索, 多字段匹配
        if (!empty($input_params['keyword'])) {
            $val = '"'.$input_params['keyword'].'"';
            $query->match(['name', 'english_name'], $val, true);
        }
        //出版商
        if (!empty($input_params['publisher_id'])) {
            $query = $query->where('publisher_id', intval($input_params['publisher_id']));
        }
        //出版时间
        if (!empty($input_params['published_date']) && is_array($input_params['published_date'])) {
            $published_date_list = array_map('intval', $input_params['published_date']);
            $query = $query->where('published_date', 'BETWEEN', $published_date_list);
        }

        return $query;
    }


    /**
     *seo_description 截取
     */
    public function strDescription($description)
    {
        $spilt_array = explode("\n", $description);
        if (empty($spilt_array) || count($spilt_array) <= 0) {
            return $description;
        }
        $i = 0;
        if (stripos($spilt_array[$i], 'Highlights') !== false) {
            $i = $i + 1;
        }
        $result = $spilt_array[$i];
        if (strlen($result) < 20 && !empty($spilt_array[$i + 1])) {
            $i = $i + 1;
            $result .= $spilt_array[$i];
        }
        return $result;
    }

    /**
     * 将摘要描述拆成多部份,并添加换行符
     * @param  description 摘要
     */
    /**
     * 将摘要描述拆成多部份,并添加换行符
     * @param  description 摘要
     */
    public function spiltDescription($description)
    {
        $result = [];
        if (!empty($description)) {
            $description = trim($description, "\r\n");
            $description = trim($description, "\n");
            $description = trim($description, "\r");
            $descriptionArray = explode("\n", $description);

            // // 使用正则表达式去除"Market Segmentation"和"Chapter Outline"之间的内容
            // $pattern = '/Market Segmentation(.*?)Chapter Outline/s';
            // preg_match($pattern, $description, $matches);
            //分割三部分，需求是第二段下面要插入年度趋势图，然后“Market Segmentation”之后和“Chapter Outline”之前要替换成表格
            $descriptionArrayPart['top'] = [];
            $descriptionArrayPart['bottom'] = [];
            $descriptionArrayPart['part1'] = [];
            $descriptionArrayPart['part2'] = [];
            $startIndex = true;
            $endIndex = false;
            foreach ($descriptionArray as $index => $row) {
                $row = trim($row, "\n");
                $row = trim($row, "\r");
                $row = trim($row, "\r\n");
                if ($index <= 1) {
                    $descriptionArrayPart['top'][] = str_replace(chr(194) . chr(160), ' ', $row);
                } else {
                    $descriptionArrayPart['bottom'][] = $row;
                    if (strpos($row, 'Company Profiles') !== false || strpos($row, 'By Company') !== false) {
                        $startIndex = false;
                        continue; //这一句不记录
                    }
                    if (strpos($row, 'Chapter Outline') !== false || strpos($row, 'Core Chapters') !== false) {
                        $endIndex = true;
                    }
                    if ($startIndex) {
                        $descriptionArrayPart['part1'][] = $row;
                    }
                    if ($endIndex) {
                        $descriptionArrayPart['part2'][] = $row;
                    }
                }
            }
            // return !$startIndex && $endIndex;
            if (!$startIndex || $endIndex) {
                if (($startIndex) || count($descriptionArrayPart['part1']) == 0 || count($descriptionArrayPart['part2']) == 0) {
                    unset($descriptionArrayPart['part1']);
                    unset($descriptionArrayPart['part2']);
                } else {
                    unset($descriptionArrayPart['bottom']);
                }
                foreach ($descriptionArrayPart as $key => $part) {
                    $result[] = $this->setNewDescriptionLinebreak(implode("\n", $part));
                }
            } else {
                $result[] = $this->setNewDescriptionLinebreak($description);
            }
        }
        return $result;
    }

    // 通过模板获取报告描述
    private function getDescriptionByTemplate($product, $desc)
    {
        $description_en = $desc['description_en'];
        $templateCategory = TemplateCategory::query()->select(['id', 'keywords'])->where(['status' => 1])->orderBy('sort', 'desc')->orderBy('id', 'desc')->get()->toArray();
        $defaultTemplateCategory = 0;
        // 获取该条数据所属模板分类
        foreach ($templateCategory as $templateCategoryItem) {
            if (empty($templateCategoryItem['keywords'])) {
                if ($defaultTemplateCategory == 0) {
                    $defaultTemplateCategory = $templateCategoryItem['id'];
                }
                continue;
            }
            $templateCategorykeywords = explode(',', $templateCategoryItem['keywords']);
            //只需满足任意关键词
            $flag = false;
            foreach ($templateCategorykeywords as $categorykeyword) {
                if (strpos($description_en, $categorykeyword) !== false) {
                    $flag = true;
                    break;
                }
            }
            if ($flag) {
                $defaultTemplateCategory = $templateCategoryItem['id'];
                break;
            }
        }

        $description = '';
        $template = Template::query()->select(['content'])
            ->where(['status' => 0])
            ->where(['type' => 1])
            ->whereRaw("FIND_IN_SET(?, category_id) > 0", [$defaultTemplateCategory])
            ->orderBy(['order' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(1)
            ->get()
            ->toArray();

        $description = Template::templateWirteData($template, $product, $desc);

        return $description;
    }

}
