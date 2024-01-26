<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProductDescription;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\SystemValue;

class ProductController extends Controller
{
    // 获取报告列表信息
    public function List(Request $request){
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


    // 报告详情
    public function Description(Request $request)
    {
        $product_id = $request->product_id;
        $url = $request->url;
        if (empty($product_id)) {
            ReturnJson(false,'产品ID不允许为空！',[]);
        }
        
        $product = Products::where(['id' => $product_id, 'status' => 1])->select('id')->first();
        //url重定向 如果该文章已删除则切换到url一致的文章，如果没有url一致的则返回报告列表

        // 未知代码-2024-1-26
        // $validate = self::controllerLimit([
        //     [
        //         'funciton' => Yii::$app->requestedRoute,
        //         'time_limit' => 60,
        //         'try_times' => $this->count()['robotsView'] ?? 200,
        //     ],
        // ], Yii::$app->requestedRoute);

        if (!empty($product)) {
                $product_desc = (new Products)->from('product_routine as p')->select([
                'p.name',
                'p.english_name',
                'cate.thumb',
                'p.id',
                'p.published_date',
                'cate.name as category',
                'cate.keyword_suffix',
                'cate.product_tag',
                'p.pages',
                'p.tables',

                'p.url',
                'p.category_id',
                'p.keywords',
                'p.price',
                'p.discount_type',
                'p.discount_amount',
                'p.discount_time_end',
            ])->leftJoin('product_category as cate','cate.id','=', 'p.category_id')
                ->where(['p.id' => $product_id])
                ->where('p.status',1)
                ->first();

            $suffix = date('Y', strtotime($product_desc['published_date']));
            // $tableName = ProductDescription::getTableName($suffix);// 2024-1-26 去掉
            $description = (new ProductDescription($suffix))::select([
                'description',
                'description_en',
                'table_of_content',
                'table_of_content_en',
                'tables_and_figures',
                'tables_and_figures_en',
                'companies_mentioned',
            ])->where(['product_id' => $product_id])->first();

            if ($description === null) {
                $description = [];
                $description['description'] = '';
                $description['table_of_content'] = '';
                $description['tables_and_figures'] = '';
                $description['companies_mentioned'] = '';
            }

            $desc = [];
            if (!empty($product_desc) && is_array($product_desc)) {

                $description['description'] = str_replace(['<pre>', '</pre>'], '', $description['description']);
                $_description = $this->setDescriptionLinebreak($description['description']);
                $_description_en = $this->setDescriptionLinebreak($description['description_en']);
                $desc['description'] = $_description;
                $desc['description_en'] = $_description_en;
                $desc['table_of_content'] = $this->titleToDeep2($description['table_of_content']);
                $desc['table_of_content_en'] = $this->titleToDeep2($description['table_of_content_en']);
                $desc['table_of_content2'] = $this->titleToDeep($_description, $description['table_of_content']);
                $desc['table_of_catalogue'] = $desc['table_of_content2']['topTitles'];
                $desc['tables_and_figures'] = str_replace(['<pre>', '</pre>'], '', $description['tables_and_figures']);
                $desc['tables_and_figures_en'] = str_replace(['<pre>', '</pre>'], '', $description['tables_and_figures_en']);
                $desc['companies_mentioned'] = str_replace(['<pre>', '</pre>'], '', $description['companies_mentioned']);

                $serviceMethod = SystemValue::select(['name key', 'value'])->where(['key' => 'Service', 'status' => 1])->find();
                $desc['serviceMethod'] = $serviceMethod ?? '';
                $product_desc = array_merge($product_desc, $desc);
            }
            // 这里的代码可以复用 开始
            $prices = [];
            // $languages = PriceLanguage::find()->select(['id', 'language'])->asArray()->all();
            // if (!empty($languages) && is_array($languages)) {
            //     foreach ($languages as $index => $language) {
            //         $priceEditions = PriceEdition::find()->select(['id', 'edition', 'rule', 'notice'])->where(['language_id' => $language['id']])->asArray()->all();
            //         $prices[$index]['language'] = $language['language'];
            //         if (!empty($priceEditions) && is_array($priceEditions)) {
            //             foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
            //                 $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
            //                 $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['edition'];
            //                 $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
            //                 $prices[$index]['data'][$keyPriceEdition]['price'] = eval("return " . sprintf($priceEdition['rule'], $product_desc['price']) . ";");
            //             }
            //         }
            //     }
            // }
            // echo '<pre>';print_r($prices);exit;
            // 这里的代码可以复用 结束
            $product_desc['prices'] = $prices;
            $product_desc['description'] = $product_desc['description'];
            $product_desc['url'] = $product_desc['url'];
            $product_desc['thumb'] = $product_desc['thumb'] ? $request->thumbUrl . $product_desc['thumb'] : '';

            //产品关键词 开始
            if (!empty($product_desc['keyword_suffix'])) {
                $keyword_suffixs = explode(',', $product_desc['keyword_suffix']);
                if (!empty($keyword_suffixs) && is_array($keyword_suffixs)) {
                    $seo_keyword = '';
                    $separator = ''; // 分隔符
                    // echo '<pre>';print_r($keyword_suffixs);exit;
                    foreach ($keyword_suffixs as $keyword_suffix) {
                        $seo_keyword .= $separator . $product_desc['keyword'] . $keyword_suffix;
                        $separator = '，';
                    }
                }
            } else {
                $seo_keyword = $product_desc['keyword'];
            }
            $product_desc['seo_keyword'] = $seo_keyword;
            //产品关键词 结束

            //产品标签 开始
            try {
                // $isSphinx = true; //sphinx是否可用
                // $port = $request->sphinxPort;
                // $conn = mysqli_connect("127.0.0.1:" . $port, "", "", "");
                // if (mysqli_connect_errno()) {
                //     $isSphinx = false;
                // }
                $isSphinx = false;

            } catch (\Throwable $th) {
                $isSphinx = false;
            }
            // header('Content-Type:text/html;charset=utf-8');
            $tagList = [];
            if ($isSphinx) {
                $sql = "SELECT `keyword` FROM `products_rt` WHERE (`status` = 1) ";
                $sql .= " AND match('@keyword \"" . addslashes($product_desc['keyword']) . "\"') group by keyword";

                $tag_res = mysqli_query($conn, $sql);
                if (empty($tag_res)) {
                    $tagList = [];
                } else {
                    while ($row = mysqli_fetch_assoc($tag_res)) {
                        $tagList[] = $row['keyword'];
                    }
                }
                $product_desc['tag'] = $tagList;
            } else {
                $product_desc['tag'] = [$product_desc['keyword']];
            }
            if ((!$product_desc['tag'] || count($product_desc['tag']) <= 1) && $product_desc['product_tag']) {
                $product_desc['tag'] = array_merge($product_desc['tag'], explode(',', $product_desc['product_tag']));
            }
            unset($product_desc['product_tag']);
            $product_desc['isSphinx'] = $isSphinx;
            //产品标签 结束

            ReturnJson(true,'',$product_desc);
        } else {
            $product_desc = Products::find()->select(['id', 'url', 'published_date'])
                ->where(['url' => $url,'status'=>1])
                ->orderBy(['published_date' => SORT_DESC, 'id' => SORT_DESC])
                ->find();
            unset($product_desc->published_date);
            if (!empty($product_desc)) {
                ReturnJson(true,'',$product_desc);
            } else {
                ReturnJson(false,'请求失败');
            }
        }
    }
}