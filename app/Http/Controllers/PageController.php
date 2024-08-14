<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\Comment;
use App\Models\Common;
use App\Models\ContactUs;
use App\Models\DictionaryValue;
use App\Models\Languages;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Partner;
use App\Models\PriceEditionValues;
use App\Models\Problem;
use App\Models\ProductDescription;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\Qualification;
use App\Models\QuoteCategory;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * 获取单页面内容
     */
    public function Get(Request $request)
    {
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

        if ($link == 'about') { // 其中的单页【公司简介】（link值是about）比较特殊：后台此单页富文本编辑器的内容要返回a和b两部分给前端，a和b中间嵌入其它内容。
            $divisionArray = explode('<div id="division"></div>', $content);
            $special = [
                'a' => $divisionArray[0],
                'b' => isset($divisionArray[1]) ? $divisionArray[1] : '',
            ];
            $content = $special;
        }
        ReturnJson(true, '', $content);
    }

    /**
     * 权威引用列表
     */
    public function Quotes(Request $request)
    {
        $page = !empty($request->page) ? $request->page : 1;
        $pageSize = !empty($request->pageSize) ? $request->pageSize : 16;
        $category_id = !empty($request->category_id) ? $request->category_id : 0;

        //权威引用分类
        //$category = DictionaryValue::GetDicOptions('quote_cage');
         $category = QuoteCategory::select(['id', 'name'])
                                  ->where("status" , 1)
                                  ->orderBy('sort', 'asc')->get()->toArray() ?? [];
        array_unshift($category, ['id' => '0', 'name' => '全部']);

        // 数据
        $model = Authority::select(['id', 'name as title', 'thumbnail as img', 'category_id'])->orderBy('sort', 'asc');
        if ($category_id) {
            $model = $model->where('category_id', $category_id);
        }
        //过滤状态
        $model->where("status" , 1);
        $count = $model->count();
        $result = $model->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->toArray();
        foreach ($result as $key => $item) {
            $result[$key]['img'] = Common::cutoffSiteUploadPathPrefix($result[$key]['img']);
        }
        $data = [
            'result' => $result,
            'category' => $category,
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            'count' => intval($count),
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 权威引用单个查询
     */
    public function Quote(Request $request)
    {
        $id = $request->id;
        if (empty($id)) {
            ReturnJson(false, 'id is empty');
        }
        $data = Authority::select(['name as title', 'body as content'])->where('id', $id)->first();
        ReturnJson(true, '', $data);
    }

    public function QuoteRelevantProduct(Request $request): array
    {
        
        $id = $request->id ? $request->id : null;
        $limit = !empty($request->pageSize) ? $request->pageSize : 5;
        $keyword = Authority::where('id', $id)->value('keyword');
        if (!empty($keyword)) {
            $keyword = explode(',', $keyword);
        }

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
                'discount_amount as discount_value',
                // 'description_seo'
            ])
                ->whereIn('keywords', $keyword)
                ->where("status", 1)
                //->where('published_date', '>', $begin)
                ->where("published_date", "<=", time())
                ->orderBy('published_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
            if ($result) {
                foreach ($result as $key => $value) {
                    $data[$key]['thumb'] = Products::getThumbImgUrl($value);
                    $data[$key]['name'] = $value['name'];
                    $data[$key]['keyword'] = $value['keywords'];
                    $data[$key]['english_name'] = $value['english_name'];
                    // $data[$key]['description'] = $value['description_seo'];
                    $data[$key]['date'] = $value['published_date'] ? $value['published_date'] : '';
                    $productsCategory = ProductsCategory::query()->select(['id', 'name', 'link'])->where(
                        'id',
                        $value['category_id']
                    )->first();
                    $data[$key]['categoryName'] = $productsCategory->name ?? '';
                    $data[$key]['categoryId'] = $productsCategory->id ?? 0;
                    $data[$key]['categoryLink'] = $productsCategory->link ?? '';
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
                                ['id', 'name as edition', 'rules as rule', 'is_logistics', 'notice']
                            )->where(['language_id' => $language['id']])->get()->toArray();
                            if ($priceEditions) {
                                $prices[$index]['language'] = $language['name'];
                                foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                                    $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                                    $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['edition'];
                                    $prices[$index]['data'][$keyPriceEdition]['is_logistics'] = $priceEdition['is_logistics'];
                                    $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                                    $prices[$index]['data'][$keyPriceEdition]['price'] = eval("return " . sprintf(
                                            $priceEdition['rule'],
                                            $value['price']
                                        ) . ";");
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
     * @param string $username 姓名
     * @param string $email 邮箱地址
     * @param integer $province_id 省份编号
     * @param integer $city_id 城市编号
     * @param string $phone 电话号码
     * @param string $company 公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string $content 留言反馈
     */
    public function ContactUs(Request $request)
    {
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
        if ($model->save()) {
            (new SendEmailController)->contactUs($model->id); // 发送邮件
            ReturnJson(true, '', $model);
        } else {
            ReturnJson(false, $model->getModelError());
        }
    }

    /**
     * 定制报告（表单）
     * @param string $username 姓名
     * @param string $email 邮箱地址
     * @param integer $province_id 省份编号
     * @param integer $city_id 城市编号
     * @param string $phone 电话号码
     * @param string $company 公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string $content 留言反馈
     */
    public function CustomReports(Request $request)
    {
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
     * @param string $username 姓名
     * @param string $email 邮箱地址
     * @param integer $province_id 省份编号
     * @param integer $city_id 城市编号
     * @param string $phone 电话号码
     * @param string $company 公司名称
     * @param integer $plan_buy_time 计划购买时间
     * @param string $content 留言反馈
     */
    public function ApplicationSample(Request $request)
    {
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
    public function TeamMember(Request $request)
    {
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
        ReturnJson(true, '', $data);
    }

    /**
     * 资质认证
     */
    public function Qualification(Request $request)
    {
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
            'result' => $result,
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            'count' => intval($count),
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 常见问题
     */
    public function Faqs()
    {
        $data = Problem::select(['problem as question', 'reply as answer'])->where('status', 1)->orderBy('sort', 'asc')->get()->toArray();
        ReturnJson(true, '', $data);
    }

    /**
     * 客户评价-列表
     */
    public function CustomerEvaluations(Request $request)
    {
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 16;

        $query = Comment::select([
            'id',
            'title',
            'image as thumb',
        ])
            ->orderBy('sort', 'asc')
            ->where('status', 1);

        $count = $query->count();

        $result = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->toArray();

        foreach ($result as &$item){
            $item['thumb'] = Common::cutoffSiteUploadPathPrefix($item['thumb']);
        }

        $data = [
            'result' => $result,
            "page" => $page,
            "pageSize" => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            "count" => intval($count),
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 客户评价-详情
     */
    public function CustomerEvaluation(Request $request)
    {
        $id = $request->id;
        if (!isset($id)) {
            ReturnJson(false, 'id is empty');
        }
        $data = Comment::select([
            'title',
            'created_at',
            'image as img',
        ])
            ->where('status', 1)
            ->where('id', $id)
            ->first();
        ReturnJson(true, '', $data);
    }
}
