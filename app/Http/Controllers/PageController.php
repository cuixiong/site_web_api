<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\City;
use App\Models\Comment;
use App\Models\Common;
use App\Models\ContactUs;
use App\Models\Country;
use App\Models\DictionaryValue;
use App\Models\FaqCategory;
use App\Models\History;
use App\Models\Languages;
use App\Models\Menu;
use App\Models\News;
use App\Models\Office;
use App\Models\Page;
use App\Models\Partner;
use App\Models\PriceEditionValues;
use App\Models\Problem;
use App\Models\ProductDescription;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\Qualification;
use App\Models\QuoteCategory;
use App\Models\SystemValue;
use App\Models\TeamMember;
use App\Services\ProductService;
use Illuminate\Http\Request;

class PageController extends Controller {
    /**
     * 获取单页面内容
     */
    public function Get(Request $request) {
        $link = $request->link ?? 'index';
        if (empty($link)) {
            ReturnJson(false, 'link is empty');
        }
        $domian = env('APP_URL', '');
        $front_menu_id = Menu::where('link', $link)->value('id');
        $content = Page::where('page_id', $front_menu_id)->value('content');
        // $content = str_replace('src="', 'src="'.$domian, $content);
        // $content = str_replace('srcset="', 'srcset="' . $domian, $content);
        // $content = str_replace('url("', 'url("' . $domian, $content);
        if (strpos($content, '%s') !== false) {
            $year = bcsub(date('Y'), 2022); // 两个任意精度数字的减法
            $content = str_replace('%s', bcadd(15, $year), $content);
        }
        if (strpos($content, "{{%c}}") !== false) {
            $content = str_replace('{{%c}}', intval(date('Y')) - 2007, $content);
        }
        if ($link == 'about') { // 其中的单页【公司简介】（link值是about）比较特殊：后台此单页富文本编辑器的内容要返回a和b两部分给前端，a和b中间嵌入其它内容。
            $divisionArray = explode('<div id="division"></div>', $content);
            $special = [
                'a' => $divisionArray[0],
                'b' => isset($divisionArray[1]) ? $divisionArray[1] : '',
            ];
            $content = $special;
        }

        // 追加图片域名, 因为前端想加在内容上...
        if (checkSiteAccessData(['girjp'])) {
            $imgDomain = env('IMAGE_URL', '');
            if(!empty($imgDomain)){
                $content = str_replace('src="/', 'src="'.$imgDomain.'/', $content);
            }
        }

        ReturnJson(true, '', $content);
    }

    /**
     * 权威引用列表
     */
    public function Quotes(Request $request) {
        $page = !empty($request->page) ? $request->page : 1;
        $pageSize = !empty($request->pageSize) ? $request->pageSize : 16;
        $isALL = !isset($request->is_all) || !empty($request->is_all) ? true : false;
        $category_id = !empty($request->category_id) ? $request->category_id : 0;
        //权威引用分类
        //$category = DictionaryValue::GetDicOptions('quote_cage');
        $category = QuoteCategory::select(['id', 'name'])
                                 ->where("status", 1)
                                 ->orderBy('sort', 'asc')->get()->toArray() ?? [];
        if ($isALL) {
            array_unshift($category, ['id' => '0', 'name' => '全部']);
        } elseif (!$isALL && empty($category_id) && $category && is_array($category) && count($category) > 0) {
            $category_id = $category[0];
        }
        // 数据
        $model = Authority::select(['id', 'name as title', 'thumbnail as img', 'category_id', 'type'])->orderBy(
            'sort', 'asc'
        )
                          ->orderBy('id', 'desc');
        if ($category_id) {
            $model = $model->where('category_id', $category_id);
        }
        //过滤状态
        $model->where("status", 1);
        $count = $model->count();
        $result = $model->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->toArray();
        foreach ($result as $key => $item) {
            $result[$key]['img'] = Common::cutoffSiteUploadPathPrefix($result[$key]['img']);
        }
        $data = [
            'result'    => $result,
            'category'  => $category,
            'page'      => $page,
            'pageSize'  => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            'count'     => intval($count),
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 权威引用单个查询
     */
    public function Quote(Request $request) {
        $id = $request->id;
        if (empty($id)) {
            // 无数据重定向回列表
            if (checkSiteAccessData(['yhcojp' ])) {
                ReturnJson(2, '');
            }
            ReturnJson(false, 'id is empty');
        }
        $data = Authority::select(
            ['name as title', 'body as content', 'description', 'id', 'keyword', 'name', 'created_at as time',
             'big_image as img', 'hits', 'real_hits', 'category_id', 'type', 'status','sort']
        )->where('id', $id)->first();
        if (empty($data) || $data->status == 0) {
            // 无数据重定向回列表
            if (checkSiteAccessData(['yhcojp' ])) {
                ReturnJson(2, '');
            }
            ReturnJson(true, 'data is empty');
        }
        //增加点击次数
        Authority::where(['id' => $id])->increment('real_hits');
        Authority::where(['id' => $id])->increment('hits');
        $data['time'] = date("Y-m-d", $data['time']);
        // 新闻/权威引用等如果内容带有链接，则该链接在移动端无法正常换行，因此后端在此协助处理
        $data['content'] = str_replace(
            'href="', 'style="word-wrap:break-word;word-break:break-all;" href="', $data['content']
        );

        list($prevId, $nextId) = $this->getNextPrevId($id, $data['sort']);
        //查询上一篇
        if (!empty($prevId)) {
            $prev = Authority::select(['id', 'name as title', 'category_id'])
                        ->where("id", $prevId)
                        ->first();
        }
        //查询下一篇
        if (!empty($nextId)) {
            $next = Authority::select(['id', 'name as title', 'category_id'])
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

        ReturnJson(true, '', $data);
    }

    /**
     * 查询权威引用上下一篇
     *
     * @param Request $request
     * @param mixed   $id
     *
     * @return int[]
     */
    private function getNextPrevId($id, $sort = 0) {
        $baseQuery = Authority::query()->where('status', 1);
        $prevId = (clone $baseQuery)->where(function ($query) use ($sort, $id) {
            $query->where('sort', '>', $sort)
                ->orWhere(function ($subQuery) use ($sort, $id) {
                    $subQuery->where('sort', $sort)
                        ->where('id', '<', $id);
                });
        })
            ->where('id', '<>', $id)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->limit(1)
            ->value('id');

        $nextId = (clone $baseQuery)->where(function ($query) use ($sort, $id) {
            $query->where('sort', '<', $sort)
                ->orWhere(function ($subQuery) use ($sort, $id) {
                    $subQuery->where('sort', $sort)
                        ->where('id', '>', $id);
                });
        })
            ->where('id', '<>', $id)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'asc')
            ->limit(1)
            ->value('id');
        $prevId = !empty($prevId) ? $prevId : 0;
        $nextId = !empty($nextId) ? $nextId : 0;

        return array($prevId, $nextId);
    }


    public function QuoteRelevantProduct(Request $request): array {
        $id = $request->id ? $request->id : null;
        $limit = !empty($request->pageSize) ? $request->pageSize : 5;
        $keyword = Authority::where('id', $id)->value('keyword');
        if (!empty($keyword)) {
            $keyword = explode(',', $keyword);
            $keyword = $keyword[0];
        } else {
            ReturnJson(true, 'success', []);
        }
        $data = [];
        if ($keyword) {
            $select = [
                'id',
                'name',
                'thumb',
                'english_name',
                'keywords',
                'published_date',
                'category_id',
                'price',
                'url',
                'discount',
                'discount_amount',
                'discount_type',
                'discount_time_begin',
                'discount_time_end'
            ];
            $data = Products::GetRelevantProductResult(-1, $keyword, 1, $limit, 'keywords', $select);
            if ($data) {
                // 分类信息
                $categoryIds = array_column($data, 'category_id');
                $categoryData = ProductsCategory::select(['id', 'name', 'link', 'thumb'])->whereIn('id', $categoryIds)
                                                ->get()->toArray();
                $categoryData = array_column($categoryData, null, 'id');
                // 默认图片
                // 若报告图片为空，则使用系统设置的默认报告高清图
                $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
                foreach ($data as $key => $value) {
                    $product_info = Products::find($value['id']);
                    //每个报告加上分类信息
                    $tempCategoryId = $value['category_id'];
                    $value['category_name'] = isset($categoryData[$tempCategoryId])
                                              && isset($categoryData[$tempCategoryId]['name'])
                        ? $categoryData[$tempCategoryId]['name'] : '';
                    $value['category_thumb'] = isset($categoryData[$tempCategoryId])
                                               && isset($categoryData[$tempCategoryId]['thumb'])
                        ? $categoryData[$tempCategoryId]['thumb'] : '';
                    $value['category_link'] = isset($categoryData[$tempCategoryId])
                                              && isset($categoryData[$tempCategoryId]['link'])
                        ? $categoryData[$tempCategoryId]['link'] : '';
                    // 图片获取
                    $tempThumb = '';
                    if (!empty($value['thumb'])) {
                        $tempThumb = Common::cutoffSiteUploadPathPrefix($value['thumb']);
                    } elseif (!empty($value['category_thumb'])) {
                        $tempThumb = Common::cutoffSiteUploadPathPrefix($value['category_thumb']);
                    } else {
                        // 如果报告图片、分类图片为空，使用系统默认图片
                        $tempThumb = !empty($defaultImg) ? $defaultImg : '';
                    }
                    $data[$key]['thumb'] = $tempThumb;
                    $data[$key]['category_name'] = $value['category_name'];
                    $data[$key]['category_link'] = $value['category_link'];
                    // $data[$key]['thumb'] = Products::getThumbImgUrl($value);
                    $data[$key]['name'] = $value['name'];
                    $data[$key]['keyword'] = $value['keywords'];
                    $data[$key]['english_name'] = $value['english_name'];
                    // $data[$key]['description'] = $value['description_seo'];
                    $data[$key]['date'] = $value['published_date'] ? $value['published_date'] : '';
                    //判断当前报告是否在优惠时间内
                    $time = time();
                    if ($data[$key]['discount_time_begin'] <= $time && $data[$key]['discount_time_end'] >= $time) {
                        $data[$key]['discount_status'] = 1;
                    } else {
                        $data[$key]['discount_status'] = 0;
                        // 过期需返回正常的折扣
                        $data[$key]['discount_amount'] = 0;
                        $data[$key]['discount'] = 100;
                        $data[$key]['discount_time_begin'] = null;
                        $data[$key]['discount_time_end'] = null;
                    }
                    $data[$key]['price_values'] = $product_info['price_values'] ?? '';
                    if(empty($data[$key]['price_values'] )){
                        $data[$key]['price_values'] = ProductService::getAllPriceValuesIds();
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
                            $priceEditions = PriceEditionValues::select(
                                ['id', 'name as edition', 'rules as rule', 'is_logistics', 'notice']
                            )->where(['status' => 1, 'is_deleted' => 1, 'language_id' => $language['id']])->orderBy(
                                "sort", "asc"
                            )->get()->toArray();
                            if ($priceEditions) {
                                $prices[$index]['language'] = $language['name'];
                                foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                                    $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                                    $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['edition'];
                                    $prices[$index]['data'][$keyPriceEdition]['is_logistics']
                                        = $priceEdition['is_logistics'];
                                    $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                                    $prices[$index]['data'][$keyPriceEdition]['price'] = eval(
                                        "return ".sprintf(
                                            $priceEdition['rule'],
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
        ReturnJson(true, 'success', $data);
    }

    /**
     * 联系我们（表单）
     *
     * @param string  $username      姓名
     * @param string  $email         邮箱地址
     * @param integer $province_id   省份编号
     * @param integer $city_id       城市编号
     * @param string  $phone         电话号码
     * @param string  $company       公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string  $content       留言反馈
     */
    public function ContactUs(Request $request) {
        $params = $request->all();
        $name = $params['name'];
        $email = $params['email'];
        //校验邮箱规则
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ReturnJson(false, '邮箱格式不正确');
        }
        $appName = env('APP_NAME', '');
        currentLimit($request, 60, $appName, $email);
        $province_id = $params['province_id'];
        $city_id = $params['city_id'];
        $phone = $params['phone'];
        $company = $params['company'];
        $plan_buy_time = $params['plan_buy_time'];
        $content = $params['content'];
        $model = new ContactUs();
        $model->category_id = $params['category_id'] ?? 0;
        $model->name = $name;
        $model->email = $email;
        //$model->area_id = $province_id;  数据库字段对应省份字段
        $model->province_id = $province_id;
        $model->city_id = $city_id;
        $model->phone = $phone;
        $model->company = $company;
        $model->buy_time = $plan_buy_time;
        //$model->remarks = $content;
        $model->content = $content;
        $model->status = 0;
        $languageId = $params['language'] ?? '';
        $model->language_version = $languageId;
        if ($model->save()) {
            (new SendEmailController)->contactUs($model->id); // 发送邮件
            ReturnJson(true, '', $model);
        } else {
            ReturnJson(false, $model->getModelError());
        }
    }

    /**
     * 定制报告（表单）
     *
     * @param string  $username      姓名
     * @param string  $email         邮箱地址
     * @param integer $province_id   省份编号
     * @param integer $city_id       城市编号
     * @param string  $phone         电话号码
     * @param string  $company       公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string  $content       留言反馈
     */
    public function CustomReports(Request $request) {
        $params = $request->all();
        $name = $params['name'];
        $email = $params['email'];
        $province_id = $params['province_id'];
        $city_id = $params['city_id'];
        $phone = $params['phone'];
        $company = $params['company'];
        $plan_buy_time = $params['plan_buy_time'];
        $content = $params['content'];
        $model = new ContactUs();
        $model->name = $name;
        $model->email = $email;
        $model->area_id = $province_id;
        $model->city_id = $city_id;
        $model->phone = $phone;
        $model->company = $company;
        $model->buy_time = $plan_buy_time;
        $model->remarks = $content;
        $model->status = 0;
        if ($model->save()) {
            // $user = new User();
            // Contact::sendContactEmail($params, $user);// 发送邮件
            (new SendEmailController)->customized($model->id);
            ReturnJson(true, '', $model);
        } else {
            ReturnJson(false, $model->getModelError());
        }
    }

    /**
     * 联系我们（表单）
     *
     * @param string  $username      姓名
     * @param string  $email         邮箱地址
     * @param integer $province_id   省份编号
     * @param integer $city_id       城市编号
     * @param string  $phone         电话号码
     * @param string  $company       公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string  $content       留言反馈
     */
    public function ApplicationSample(Request $request) {
        $params = $request->all();
        $name = $params['name'];
        $email = $params['email'];
        $province_id = $params['province_id'];
        $city_id = $params['city_id'];
        $phone = $params['phone'];
        $company = $params['company'];
        $plan_buy_time = $params['plan_buy_time'];
        $content = $params['content'];
        $model = new ContactUs();
        $model->name = $name;
        $model->email = $email;
        $model->area_id = $province_id;
        $model->city_id = $city_id;
        $model->phone = $phone;
        $model->company = $company;
        $model->buy_time = $plan_buy_time;
        $model->remarks = $content;
        $model->status = 0;
        if ($model->save()) {
            (new SendEmailController)->productSample($model->id); // 发送邮件
            ReturnJson(true, '', $model);
        } else {
            ReturnJson(false, $model->getModelError());
        }
    }

    /**
     * 团队成员
     */
    public function TeamMember(Request $request) {
        if (checkSiteAccessData(['qyen' , 'qykr'])) {
            $leaders = TeamMember::query()
                                 ->where('status', 1)
                                 ->where('region_name', '<>', '')
                                 ->orderBy('sort', 'asc')
                                 ->get()
                                 ->toArray();
            ###############################################
            $have_region_id_list = array_column($leaders, 'id');
            $admins = TeamMember::query()
                                ->where('status', 1)
                //->where('region_name', '')
                                ->whereNotIn('id', $have_region_id_list)
                                ->orderBy('sort', 'asc')
                                ->get()
                                ->toArray();
            $data['leaders'] = $leaders;
            $data['admins'] = $admins;
            ReturnJson(true, '', $data);
        } elseif (checkSiteAccessData(['giren'])) {
            $data['top'] = TeamMember::query()
                                     ->where('status', 1)
                                     ->where("show_product", 1)
                                     ->orderBy('sort', 'asc')
                                     ->get()->toArray();
            $data['other'] = TeamMember::query()
                                       ->where('status', 1)
                                       ->where("show_product", 0)
                                       ->orderBy('sort', 'asc')
                                       ->get()->toArray();
            $data['list'] = TeamMember::query()
                                      ->where('status', 1)
                                      ->orderBy('sort', 'asc')
                                      ->get()->toArray();
        } else {
            $data = TeamMember::select([
                                           'name',
                                           'position as post',
                                           'image as img',
                                           'custom as sketch',
                                           'describe'
                                       ])
                              ->where('status', 1)
                              ->orderBy('sort', 'asc')
                              ->get()
                              ->toArray();
        }
        ReturnJson(true, '', $data);
    }

    /**
     * 团队成员-分析师
     */
    public function AnalystGroup(Request $request) {
        $analystList = TeamMember::select([
                                              'name',
                                              'industry_id',
                                              'area', // 研究领域
                                              'experience', // 参与项目/经验
                                              'custom', // 合作客户
                                          ])
                                 ->where('status', 1)
                                 ->where('is_analyst', 1)
                                 ->orderBy('sort', 'asc')
                                 ->get()
                                 ->toArray();
        $data = [];
        if ($analystList) {
            $industryIds = array_unique(array_column($analystList, 'industry_id'));
            $industryArray = ProductsCategory::query()->select(['id', 'name'])->whereIn('id', $industryIds)->pluck(
                'name', 'id'
            )->toArray();
            foreach ($analystList as $member) {
                if (empty($member['industry_id'])) {
                    continue;
                }
                if (!isset($data[$member['industry_id']])) {
                    $data[$member['industry_id']] = [
                        'name' => $industryArray[$member['industry_id']],
                        'item' => []
                    ];
                }
                $data[$member['industry_id']]['item'][] = $member;
            }
            $data = array_values($data);
        }
        ReturnJson(true, '', $data);
    }

    /**
     * 资质认证
     */
    public function Qualification(Request $request) {
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 12;
        $result = Qualification::select([
                                            'id',
                                            'name as title',
                                            'thumbnail as thumb',
                                            'image as img'
                                        ])
                               ->orderBy('sort', 'asc')
                               ->where('status', 1)
                               ->offset(($page - 1) * $pageSize)
                               ->limit($pageSize)
                               ->get()
                               ->toArray();
        $count = Qualification::where('status', 1)->count();
        foreach ($result as $key => $item) {
            $result[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($result[$key]['thumb']);
            $result[$key]['img'] = Common::cutoffSiteUploadPathPrefix($result[$key]['img']);
        }
        $data = [
            'result'    => $result,
            'page'      => $page,
            'pageSize'  => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            'count'     => intval($count),
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 常见问题
     */
    public function Faqs() {
        try {
            if (checkSiteAccessData(['mrrs', 'lpijp', 'giren'])) {
                $facateGoryList = FaqCategory::query()->where('status', 1)->orderBy('sort', 'asc')->select(
                    ['id', 'name']
                )
                                             ->get()->toArray();
                $data = [];
                if (!empty($facateGoryList)) {
                    $category_id_list = array_column($facateGoryList, 'id');
                    $problemList = Problem::query()->whereIn("category_id", $category_id_list)->selectRaw(
                        'id , category_id , img,  problem as question , reply as answer'
                    )->get()->toArray();
                    $problemList = array_reduce($problemList, function ($carry, $item) {
                        $carry[$item['category_id']][] = $item;

                        return $carry;
                    },                          []);
                    foreach ($facateGoryList as &$facateGory) {
                        $facateGory['faqs'] = $problemList[$facateGory['id']];
                    }
                    $data = $facateGoryList;
                }
            } else {
                $data = Problem::select(['problem as question', 'reply as answer' , 'img'])->where('status', 1)->orderBy(
                    'sort', 'asc'
                )->get()->toArray();
            }
            ReturnJson(true, '', $data);
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage(), []);
        }
    }

    /**
     * 客户评价-列表
     */
    public function CustomerEvaluations(Request $request) {
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 16;
        $query = Comment::where('status', 1)
                        ->select(
                            ['id', 'image', 'title', 'company', 'post as author', 'content', 'comment_at', 'country']
                        )
                        ->where('status', 1);
        $count = $query->count();
        $list = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->orderBy('id', 'desc')->get()->toArray();
        foreach ($list as $key => $item) {
            $list[$key]['comment_at_format'] = date('Y-m-d', $item['comment_at']);
            $list[$key]['image'] = Common::cutoffSiteUploadPathPrefix($item['image']);
        }
        $data = [
            'result'    => $list,
            "page"      => $page,
            "pageSize"  => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            "count"     => intval($count),
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 客户评价-详情
     */
    public function CustomerEvaluation(Request $request) {
        $id = $request->id;
        if (!isset($id)) {
            ReturnJson(false, 'id is empty');
        }
        $data = Comment::select(
            ['id', 'image', 'title', 'company', 'post as author', 'content', 'comment_at', 'country']
        )
                       ->where('status', 1)
                       ->where('id', $id)
                       ->first();
        if ($data) {
            $data['comment_at_format'] = date('Y-m-d', $data['comment_at']);
        }
        ReturnJson(true, '', $data);
    }

    /**
     * 公司发展历程
     */
    public function CompanyHistory(Request $request)
    {
        $params = $request->all();
        $type = isset($params['type']) ? $params['type'] : 0;
        $result = History::select([
            'year',
            'body as content',
        ])
            ->orderBy('sort', 'asc')
            ->where('status', 1)
            ->get()
            ->toArray();
        if ($type == 1 && $result) {
            $historyData = [
                'up'   => [],
                'down' => [],
            ];
            foreach ($result as $key => $item) {
                if ($key % 2 == 0) {
                    $historyData['up'][] = $item;
                } else {
                    $historyData['down'][] = $item;
                }
            }
            $result = $historyData;
        } elseif ($type == 2 && $result) {
            $data = [];
            $tempArray = array_chunk($result, 2);
            if ($tempArray) {
                foreach ($tempArray as $key => $group) {
                    $title = array_column($group, 'year');
                    $title = implode('-', $title);
                    $data[] = [
                        'title' => $title,
                        'content' => $group,
                    ];
                }
            }
            $result = $data;
        }
        $data = [
            'result' => $result,
        ];
        ReturnJson(true, '', $data);
    }


    // 办公室
    public function officeSplitByCountry(Request $request)
    {
        $list = Office::where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->get()->toArray();
        foreach ($list as &$value) {
            $value['country_name'] = Country::query()->where('id', $value['country_id'])->value('name');
            $value['image'] = Common::cutoffSiteUploadPathPrefix($value['image']);
            $value['national_flag'] = Common::cutoffSiteUploadPathPrefix($value['national_flag']);
            //time_zone_copy
            if (!empty($value['time_zone'])) {
                $tz = new \DateTimeZone($value['time_zone']);
                // 当前时间的DateTime对象
                $now = new \DateTime('now', $tz);
                $value['time_zone_copy'] = $now->format('h:i a');
            } else {
                $now = new \DateTime('now');
                $value['time_zone_copy'] = $now->format('h:i a');
            }
        }
        // 通过时区分割国内外
        $data = [
            'home' => [],
            'abroad' => []
        ];
        foreach ($list as $key => $item) {
            if ($item['time_zone'] == 'Asia/Shanghai') {
                $data['home'][] = $item;
            } else {
                $data['abroad'][] = $item;
            }
        }

        ReturnJson(true, '', $data);
    }
}
