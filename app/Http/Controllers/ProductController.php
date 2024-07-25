<?php

namespace App\Http\Controllers;

use App\Models\SearchRank;
use App\Models\ViewProductsLog;
use App\Services\IPAddrService;
use App\Services\SenWordsService;
use App\Services\SphinxService;
use Foolz\SphinxQL\Drivers\Mysqli\Connection;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Helper\XunSearch;
use App\Models\Common;
use App\Models\Languages;
use App\Models\News;
use App\Models\PriceEditions;
use App\Models\PriceEditionValues;
use App\Models\ProductDescription;
use App\Models\ProductPdf;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\SystemValue;
use Illuminate\Support\Facades\Redis;
use IP2Location\Database;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller {
    // 获取报告列表信息
    public function List(Request $request) {
        $page = $request->page ? intval($request->page) : 1; // 页码
        $pageSize = $request->pageSize ? intval($request->pageSize) : 10; // 每页显示数量
        $category_id = $request->category_id ?? 0; // 分类ID
        $keyword = trim($request->keyword) ?? null; // 搜索关键词
        if (!empty($keyword)) {
            //点击关键词一次, 需要增加一次点击次数
            SearchRank::query()->where('name', $keyword)->increment('hits');
        }
        $categorySeoInfo = [
            'seo_title'       => '',
            'seo_keyword'     => '',
            'seo_description' => '',
        ];
        if (!empty($category_id)) {
            $categorySeoData = ProductsCategory::query()
                                               ->select(['seo_title', 'seo_keyword', 'seo_description'])
                                               ->where('id', $category_id)->first();
            if (!empty($categorySeoData)) {
                $categorySeoInfo = $categorySeoData->toArray();
            }
        }
        $res = $this->GetProductResult($page, $pageSize, $keyword, $category_id);
        $result = $res['list'];
        $count = $res['count'];
        $searchEngine = $res['type'] ?? '';
        $products = [];
        if ($result) {
            $languages = Languages::GetList();
            foreach ($result as $key => $value) {
                //报告数据
                $time = time();
                $productsData = Products::query()->where('id', $value['id'])->first();
                if (empty($productsData)) {
                    unset($result[$key]);
                    continue;
                }
                //判断当前报告是否在优惠时间内
                if ($productsData['discount_time_begin'] <= $time && $productsData['discount_time_end'] >= $time) {
                    $value['discount_status'] = 1;
                } else {
                    $value['discount_status'] = 0;
                }
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
                $value['thumb'] = Common::cutoffSiteUploadPathPrefix($value['thumb']);
                if (is_numeric($value['published_date'])) {
                    $suffix = date('Y', $value['published_date']);
                    $value['published_date'] = $value['published_date'] ? date(
                        'Y-m-d', $value['published_date']
                    ) : '';
                } else {
                    $suffix = date('Y', strtotime($value['published_date']));
                    $value['published_date'] = $value['published_date'] ? date(
                        'Y-m-d', strtotime($value['published_date'])
                    ) : '';
                }
                $description = (new ProductDescription($suffix))->where('product_id', $value['id'])->value(
                    'description'
                );
                $description = mb_substr($description, 0, 120, 'UTF-8');
                $value['description'] = $description;
                $value['category'] = $category ? [
                    'id'   => $category['id'],
                    'name' => $category['name'],
                    'link' => $category['link'],
                ] : [];
                $publisher_id = $productsData['publisher_id'];
                $value['prices'] = Products::CountPrice(
                    $value['price'], $publisher_id, $languages
                ) ?? [];
                $products[] = $value;
            }
        }
        $productCagory = $this->getProductCagory();
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
        ReturnJson(true, '请求成功', $data);
    }

    /**
     * 搜索产品数据
     */
    private function GetProductResult($page, $pageSize, $keyword = '', $category_id = 0) {
        try {
            $hidden = SystemValue::where('key', 'sphinx')->value('hidden');
            if ($hidden == 1) {
                return $this->SearchForSphinx($category_id, $keyword, $page, $pageSize);
            } else {
                return $this->SearchForMysql($category_id, $keyword, $page, $pageSize);
            }
        } catch (\Exception $e) {
            \Log::error('应用端查询失败,异常信息为:'.json_encode([$e->getMessage()]));
            ReturnJson(false, '请求失败,请稍后再试');
        }
    }

    public function handlerSurplusProductList(
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
            //增加详情的浏览记录
            $this->viewLog($product);
            $fieldList = ['p.name', 'p.english_name', 'cate.thumb', 'cate.home_thumb', 'p.id', 'p.published_date',
                          'cate.name as category',
                          'cate.keyword_suffix', 'cate.product_tag', 'p.pages', 'p.tables', 'p.url', 'p.category_id',
                          'p.keywords', 'p.price', 'p.discount_type', 'p.discount', 'p.discount_amount',
                          'p.discount_time_begin', 'p.discount_time_end', 'p.publisher_id',];
            $product_desc = (new Products)->from('product_routine as p')->select($fieldList)->leftJoin(
                'product_category as cate', 'cate.id', '=', 'p.category_id'
            )
                                          ->where(['p.id' => $product_id])
                                          ->where('p.status', 1)
                                          ->first()->toArray();
            //返回打折信息
            $time = time();
            //判断当前报告是否在优惠时间内
            if ($product_desc['discount_time_begin'] <= $time && $product_desc['discount_time_end'] >= $time) {
                $product_desc['discount_status'] = 1;
            } else {
                $product_desc['discount_status'] = 0;
            }
            //返回相关报告
            if (!empty($product_desc['keywords'])) {
                $relatedProList = Products::query()->select(
                    ["id", "published_date", "name", "thumb", "url", "keywords", "english_name", "category_id",
                     "author"]
                )
                                          ->where("keywords", $product_desc['keywords'])
                                          ->where("id", "<>", $product_id)
                                          ->where("published_date", "<=", time())
                                          ->where("status", 1)
                                          ->orderBy("published_date", "desc")
                                          ->limit(2)
                                          ->get();
                foreach ($relatedProList as $item) {
                    $item->thumb = $item->getThumbImgAttribute();
                    $item->short_desc = $item->getProShortDescAttribute();
                    $item->category_text = $item->getCategotyTextAttribute();
                }
                $product_desc['relatedProList'] = $relatedProList;
            } else {
                $product_desc['relatedProList'] = [];
            }
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
                    $_description, $description['table_of_content']
                );
                $product_desc['table_of_catalogue'] = $product_desc['table_of_content2'];
                $product_desc['tables_and_figures'] = str_replace(['<pre>', '</pre>'], '',
                                                                  $description['tables_and_figures']);
                $product_desc['tables_and_figures_en'] = str_replace(['<pre>', '</pre>'], '',
                                                                     $description['tables_and_figures_en']);
                $product_desc['companies_mentioned'] = str_replace(['<pre>', '</pre>'], '',
                                                                   $description['companies_mentioned']);
                $serviceMethod = SystemValue::select(['name as key', 'value'])->where(
                    ['key' => 'Service', 'status' => 1]
                )->first();
                $product_desc['serviceMethod'] = $serviceMethod ?? '';
            }
            $product_desc['prices'] = Products::CountPrice($product_desc['price'], $product_desc['publisher_id']);
            $product_desc['description'] = $product_desc['description'];
            $product_desc['url'] = $product_desc['url'];
            //$product_desc['thumb'] = Common::cutoffSiteUploadPathPrefix($product->getThumbImgAttribute());
            if (!empty($product->thumb)) {
                $product_desc['thumb'] = $product->thumb;
            } else {
                $product_desc['thumb'] = $product_desc['home_thumb'];
            }
            $product_desc['published_date'] = $product_desc['published_date'] ? date(
                'Y-m-d', strtotime(
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
            $product_desc['relevant_products'] = $this->getRelevantByProduct($product['keywords'], $product_id);

            //产品标签 结束
            ReturnJson(true, '', $product_desc);
        } else {
            $product_desc = Products::select(['id', 'url', 'published_date'])
                                    ->where(['url' => $url, 'status' => 1])
                                    ->where("id", "<>", $product_id)
                                    ->orderBy('published_date', 'desc')
                                    ->orderBy('id', 'desc')
                                    ->first();
            unset($product_desc->published_date);
            if (!empty($product_desc)) {
                ReturnJson(1, '', $product_desc);
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
                if (!empty($row) && strpos($row, ' ') === 0 && ($index + 1) != count($descriptionArray)
                    && strpos(
                           $descriptionArray[$index + 1], ' '
                       ) !== 0) {
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
    private function SearchForMysql(mixed $category_id, mixed $keyword, $page, $pageSize): array {
        $field = ['name', 'english_name', 'thumb', 'published_date', 'keywords', 'id', 'url', 'price', 'discount_type',
                  'discount_time_begin', 'discount_time_end', 'discount', 'discount_amount', 'category_id',
                  'publisher_id'];
        $query = Products::where(['status' => 1])
                         ->where("published_date", "<", time())
                         ->select($field);
        // 分类ID
        if ($category_id) {
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
        // 获取当前复合条件的总数量
        $count = $query->count();
        // 排序 显示发布时间 》 排序 》 id
        $query = $query->orderBy('published_date', 'desc')->orderBy('sort', 'asc');
        // 分页
        $offset = ($page - 1) * $pageSize;
        $list = $query->offset($offset)->limit($pageSize)->get()->toArray();

        return ['list' => $list, 'count' => $count, 'type' => 'mysql'];
    }

    public function SearchForSphinx($category_id, $keyword, $page, $pageSize) {
        $sphinxSrevice = new SphinxService();
        $conn = $sphinxSrevice->getConnection();
        $idProducts = $this->getProductById($conn, $category_id, $keyword);
        //报告昵称,英文昵称匹配查询
        $query = (new SphinxQL($conn))->select('*')
                                      ->from('products_rt')
                                      ->orderBy('sort', 'asc')
                                      ->orderBy('published_date', 'desc')
                                      ->orderBy('id', 'desc');
        $query = $query->where('status', '=', 1);
        $query = $query->where("published_date", "<", time());
        // 分类ID
        if (!empty($category_id)) {
            $query = $query->where('category_id', intval($category_id));
        }
        //精确搜索, 多字段匹配
        if (!empty($keyword)) {
            $val = '"'.$keyword.'"';
            $query->match(['name', 'english_name'], $val, true);
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

    /**
     *
     * @param Connection|bool $conn
     * @param                 $category_id
     * @param                 $keyword
     *
     * @return array
     */
    private function getProductById(Connection|bool $conn, $category_id, $keyword): array {
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
            $ipAddr =   (new IPAddrService($ip))->getAddrStrByIp();
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
    private function getProductCagory() {
        $field = ['id', 'name', 'link'];
        $data = ProductsCategory::select($field)
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
     * @param mixed $keywords
     * @param mixed $product_id
     *
     * @return array
     */
    private function getRelevantByProduct(mixed $keywords, mixed $product_id): array {
        $products = Products::from('product_routine as product')
                            ->select([
                                         'product.name',
                                         'product.english_name',
                                         'product.keywords',
                                         'product.id',
                                         'product.url',
                                         'product.price',
                                         'product.publisher_id',
                                         'product.published_date',
                                         'product.thumb',
                                         'product.category_id',
                                         'category.name as category_name',
                                         'category.thumb as category_thumb',
                                     ])
                            ->leftJoin('product_category as category', 'category.id', '=', 'product.category_id')
                            ->where('product.keywords', $keywords)
                            ->where('product.id', '<>', $product_id)
                            ->where('product.published_date', "<=", time())
                            ->orderBy('product.published_date', 'desc')
            //->where('product.published_date', 'between', [strtotime($start_time), strtotime($end_time)]) // 只取与这份报告同年份的两份报告数据
                            ->limit(2)
                            ->get()->toArray();
        $data = [];
        foreach ($products as $index => $product) {
            $tempThumb = '';
            if (!empty($product['thumb'])) {
                $tempThumb = Common::cutoffSiteUploadPathPrefix($product['thumb']);
            } elseif (!empty($product['category_thumb'])) {
                $tempThumb = Common::cutoffSiteUploadPathPrefix($product['category_thumb']);
            }
            $data[$index]['thumb'] = $tempThumb;
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
                'Y-m-d', strtotime($product['published_date'])
            ) : '';
            $data[$index]['prices'] = Products::CountPrice($product['price'], $product['publisher_id']);
        }

        return $data;
    }
}
