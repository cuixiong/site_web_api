<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Helper\XunSearch;
use App\Models\Authority;
use App\Models\Channel;
use App\Models\City;
use App\Models\Comment;
use App\Models\Common;
use App\Models\Country;
use App\Models\CurrencyConfig;
use App\Models\DictionaryValue;
use App\Models\Languages;
use App\Models\LanguageWebsite;
use App\Models\Link;
use App\Models\Menu;
use App\Models\MessageLanguageVersion;
use App\Models\News;
use App\Models\Office;
use App\Models\PlateValue;
use App\Models\Position;
use App\Models\PriceEditions;
use App\Models\PriceEditionValues;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\QuoteCategory;
use App\Models\SearchRank;
use App\Models\System;
use App\Models\SystemValue;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CommonController extends Controller {
    /**
     *  主页接口
     */
    public function index(Request $request) {
        $data = [];
        //seo 信息
        $data['seo_info'] = $this->getSeoInfo($request);
        //友情链接
        $data['link_list'] = $this->getLinkList();
        //中国省市地区
        $data['chian_region'] = $this->getChianRegionData();
        //获取热门关键词
        $data['hot_keywords'] = $this->getKeyWords();
        //产品标签
        $data['product_tags'] = $this->getProductTags($request);
        //语言网站
        $data['language_website'] = $this->getWebSiteLanBySystem();
        //获取顶部菜单
        $data['top_menu'] = $this->getTopMenus();
        //获取网站站点配置
        $data['site_set_info'] = $this->getSiteSetInfo();
        //网站设置
        $data['product_page_set_info'] = $this->getControlPageSet();
        //底部菜单
        $data['buttom_menu'] = $this->getButtonMenus();
        //更多资讯
        $data['news_list'] = $this->getNewsList();
        //报告分类
        $data['product_cagory'] = $this->getProductCagory(true);
        //权威引用
        $data['quote_list'] = $this->getQuoteList($request);
        //货币汇率
        $data['currency_data'] = CurrencyConfig::query()->select(['id', 'code', 'is_first', 'exchange_rate', 'tax_rate'])
                                      ->get()?->toArray() ?? [];

        if(checkSiteAccessData(['mrrs' , 'yhen' ,'qyen', 'mmgen', 'lpien', 'giren'])){
            //国家数据
            $data['country_list'] = $this->getCountryData();
            //来源数据
            $data['channel_list'] = $this->getChannelData();
            //职位下拉
            $data['position_list'] = $this->getPositionList();
        }

        // 总控字典部分
        // 计划购买时间 ,原为联系我们控制器Dictionary函数中代码，现复制至此处
        $data['buy_time'] = DictionaryValue::GetDicOptions('Buy_Time');
        // 获知渠道,原为联系我们控制器Dictionary函数中代码，现复制至此处
        $data['channel'] = DictionaryValue::GetDicOptions('Channel_Type');
        // 语言版本
        // $data['language_version'] = MessageLanguageVersion::where('status', 1)
        //     ->select(['name', 'id'])
        //     ->orderBy('sort', 'ASC')
        //     ->get()
        //     ->toArray();

        // 语言版本查询价格版本中涉及的语言
        if(checkSiteAccessData(['168report'])){
            // 因为168有多个出版商，所以需要添加索引加快查询速度，不过只查询一个报告的出版商也能满足效果
            $data['language_version'] = $this->getLanguageVersion(true);
        }else{
            // 只查询一个报告的出版商
            $data['language_version'] = $this->getLanguageVersion();
        }

        //返回对应的分类数据
        if (checkSiteAccessData(['tycn'])) {
            $cate = ProductsCategory::query()
                ->where("is_recommend", 1)
                ->where("status", 1)
                ->select(['id', 'name', 'seo_title', 'link', 'icon', 'icon_hover'])
                ->orderBy('sort', 'ASC')
                ->limit(20)->get()->toArray();
            $data['cate'] = $cate;
        }

        if (checkSiteAccessData(['yhcn' , 'mmgen', 'qycojp'])) {
            // 客户评价
            $data['comment'] = $this->getCustomersComment($request);
            // 办公室
            $data['offices'] = $this->getofficeRegion($request);
        } elseif (checkSiteAccessData(['lpicn'])) {
            // 办公室
            $data['offices'] = $this->getofficeRegion($request);
        }

        if (checkSiteAccessData(['yhen'])) {
            $data['hot_product_list'] = Products::query()
                ->where('status', 1)
                ->where('show_hot', 1)
                ->select(['id', 'thumb', 'name', 'url'])
                ->orderBy('sort', 'ASC')
                ->orderBy('published_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(10)->get()->toArray();
        }

        if (checkSiteAccessData(['qyen'])) {
            // 显示报告侧栏的分析师
            // 分析师追加是否在营业时间的设定，新增工作时间与工作时区字段
            $data['team_member_list'] = TeamMember::query()->where("status" , 1)->where("show_product" , 1)->get()->toArray();
            if($data['team_member_list']) {
                foreach ($data['team_member_list'] as &$value) {
                    if (!empty($value['time_zone'])) {
                        $tz = new \DateTimeZone($value['time_zone']);
                        // 当前时间的DateTime对象
                        $now = new \DateTime('now', $tz);
                    } else {
                        $now = new \DateTime('now');
                    }
                    // 判断人物是否正处于营业时间
                    $value['is_within_business_hours'] = Office::isWithinBusinessHours($value['working_time'], $now);
                }
            }

        }

        // qycojp 多处需要显示汇率
        if (checkSiteAccessData(['qycojp','yhcojp'])) {
            $data['rate'] = [];
            $currencyData = CurrencyConfig::query()->select(['id', 'code', 'is_first', 'exchange_rate', 'tax_rate'])
                                          ->get()?->toArray() ?? [];
            if ($currencyData && count($currencyData) > 0) {
                // 默认版本的多种货币的价格
                if ($currencyData && count($currencyData)) {
                    foreach ($currencyData as $currencyItem) {
                        $currencyRateKey = strtolower($currencyItem['code']).'_rate';
                        $data['rate'][$currencyRateKey] = $currencyItem['exchange_rate'];
                    }
                }
            }
        }
        // 留言咨询种类
        if (checkSiteAccessData(['qycojp'])) {

            $messageConsultTypeKey = 'message_consult_type';
            $messageConsultTypeSetId = System::select(['id'])
                ->where('status', 1)
                ->where('alias', $messageConsultTypeKey)
                ->get()
                ->value('id');
            $data['message_consult_type'] = SystemValue::where('parent_id', $messageConsultTypeSetId)
                ->where('hidden', 1)
                ->select(['name', 'value'])
                ->get()?->toArray()??[];
        }

        ReturnJson(true, '', $data);
    }

    public function getQuoteList(Request $request)
    {

        $category_id = $request->quote_category_id ?? 0;
        $page = $request->quote_page ?? 1;
        $pageSize = $request->quote_size ?? 4;

        $category = QuoteCategory::select(['id', 'name'])
            ->where("status", 1)
            ->orderBy('sort', 'asc')->get()->toArray() ?? [];
        array_unshift($category, ['id' => '0', 'name' => '全部']);
        // 数据
        $model = Authority::select(['id', 'name as title', 'thumbnail as img', 'category_id'])
            ->where("status", 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc');
        if ($category_id) {
            $model = $model->where('category_id', $category_id);
        }
        $result = $model->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->toArray();

        return $result;
    }

    /**
     *
     *
     * @return mixed
     */
    private function getProductCagory($orderBySort = false)
    {
        $field = ['id', 'name', 'link', 'icon', 'icon_hover' , 'show_home'];
        $query = ProductsCategory::select($field)
            ->where('status', 1);

        if($orderBySort){
            $query = $query->orderBy('sort', 'asc');
        }
        $data = $query->get()
            ->toArray();
        array_unshift($data, [
            'id'   => '0',
            'name' => '全部',
            'link' => '',
            'icon' => '',
        ]);

        return $data;
    }

    // 更多资讯
    public function getNewsList()
    {
        $data = News::select([
            'id',
            'title',
            'url',
            'category_id as type'
        ])
            ->where(['status' => 1])
            ->where('upload_at', '<=', time())
            ->orderBy('upload_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get()
            ->toArray();

        return $data;
    }

    public function getSiteSetInfo()
    {
        $siteInfoKey = 'site_info';
        $setId = System::select(['id'])
            ->where('status', 1)
            ->where('alias', $siteInfoKey)
            ->get()
            ->value('id');
        $result = [];
        if ($setId) {
            $data = SystemValue::where('parent_id', $setId)
                ->where('status', 1)
                ->where('hidden', 1)
                ->select(['name', 'key', 'value'])
                ->get()
                ->toArray();
            //图片后缀, 全部需要转换一遍
            $imgExtList = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg'];
            foreach ($data as $key => $value) {
                // 动态年份替换
                $value['value'] = str_replace('%year', date('Y', time()), $value['value']);
                $ext = pathinfo($value['value'], PATHINFO_EXTENSION);
                if (in_array($ext, $imgExtList)) {
                    $value['value'] = Common::cutoffSiteUploadPathPrefix($value['value']);
                }
                $newItem = [
                    'name'  => $value['name'],
                    'value' => $value['value']
                ];
                if (isset($result[$value['key']]) && isset($result[$value['key']]['name'])) {
                    $oldItem = $result[$value['key']];
                    $result[$value['key']] = [];
                    $result[$value['key']][] = $oldItem;
                    $result[$value['key']][] = $newItem;
                } elseif (isset($result[$value['key']]) && !isset($result[$value['key']]['name'])) {
                    $result[$value['key']][] = $newItem;
                } else {
                    $result[$value['key']] = $newItem;
                }
            }
        }

        return $result;
    }

    public function getControlPageSet()
    {
        $siteInfoKey = 'reports';
        $setId = System::select(['id'])
            ->where('status', 1)
            ->where('alias', $siteInfoKey)
            ->get()
            ->value('id');
        $data = SystemValue::where('parent_id', $setId)
            ->where('hidden', 1)
            ->select(['key', 'value'])
            ->get()
            ->toArray();
        if ($data) {
            $data = array_column($data, 'value', 'key');
        }

        return $data;
    }

    /**
     * 顶部导航栏
     */
    public function TopMenus(Request $request)
    {
        $menus = $this->getTopMenus();
        ReturnJson(true, '', $menus);
    }

    /**
     * 顶部导航栏递归方法
     */
    private function MenusTree($list, $parent = 0)
    {
        $result = [];
        foreach ($list as $key => $value) {
            if ($value['id'] == $parent) {
                $value['children'] = $this->MenusTree($list, $value['parent_id']);
            }
            $result[] = $value;
        }

        return $result;
    }

    /**
     * SEO信息获取
     */
    public function info(Request $request)
    {
        $result = $this->getSeoInfo($request);
        // 若导航菜单的TKD为空则使用首页的TKD
        // $result['seo_title'] = $result['seo_title'] ? $result['seo_title'] : Setting::find()->select(['value'])->where(['alias' => 'seoTitle'])->scalar();
        // $result['seo_keyword'] = $result['seo_keyword'] ? $result['seo_keyword'] : Setting::find()->select(['value'])->where(['alias' => 'seoKeyword'])->scalar();
        // $result['seo_description'] = $result['seo_description'] ? $result['seo_description'] : Setting::find()->select(['value'])->where(['alias' => 'seoDescription'])->scalar();
        $data = $result ? $result : [];
        if(empty($data )){
            ReturnJson(10001, '', []);
        }else{
            ReturnJson(true, '', $data);
        }

    }

    /**
     * 底部导航
     */
    public function BottomMenus(Request $request)
    {
        $frontMenus = $this->getButtonMenus();
        ReturnJson(true, '', $frontMenus);
    }

    /**
     * 报告设置
     * 打开新标签页、能否用F12键、能否用鼠标右键、复制【报告详情】页内容的控制开关
     */
    public function ControlPage(Request $request)
    {
        $id = $request->id;
        if (empty($id)) {
            ReturnJson(false, 'ID不允许为空');
        }
        $data = SystemValue::where('parent_id', $id)
            ->where('status', 1)
            ->select(['key', 'value'])
            ->get()
            ->toArray();
        ReturnJson(true, '', $data);
    }

    /**
     * 友情链接
     */
    public function Link(Request $request)
    {
        $link = $this->getLinkList();
        ReturnJson(true, '', $link);
    }

    // 购买流程
    public function PurchaseProcess(Request $request)
    {
        $id = $request->parentId;
        if (empty($id)) {
            ReturnJson(false, 'ID不允许为空');
        }
        $res = PlateValue::where('parent_id', $id)->where('status', 1)->select(['id', 'title', 'image as link'])->get()
            ->toArray();
        foreach ($res as $key => $value) {
            # code...
        }
        $res = $res ? $res : [];
        ReturnJson(true, '', $res);
    }

    /**
     * 产品标签
     */
    public function ProductTag(Request $request)
    {
        $data = $this->getProductTags($request);
        ReturnJson(true, '', $data);
    }

    /**
     * 测试
     * 讯搜测试搜索
     */
    public function TestXunSearch(Request $request)
    {
        $keyword = $request->keyword;
        if (empty($keyword)) {
            ReturnJson(false, '关键字不允许为空');
        }
        $xunsearch = new XunSearch();
        $res = $xunsearch->search($keyword);
        if ($res) {
            ReturnJson(true, '', $res);
        } else {
            ReturnJson(false, '查询失败');
        }
    }

    /**
     * 中国省份、城市数据
     */
    public function ChinaRegions()
    {
        $data = $this->getChianRegionData();
        ReturnJson(true, '', $data);
    }

    /**
     * 网站设置（包括了seo三要素）
     * 因为由于API端可能会接入多个个站点，所以当前需要前端当前站点的“站点设置”的ID来获取信息
     */
    public function Set(Request $request)
    {
        $name = $request->name;
        if (empty($name)) {
            ReturnJson(false, 'name is empty');
        }
        $setId = System::select(['id'])
            ->where('status', 1)
            ->where('alias', $name)
            ->get()
            ->value('id');
        $result = [];
        if ($setId) {
            $data = SystemValue::where('parent_id', $setId)
                ->where('status', 1)
                ->select(['name', 'key', 'value'])
                ->get()
                ->toArray();
            //图片后缀, 全部需要转换一遍
            $imgExtList = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg'];
            foreach ($data as $key => $value) {
                $ext = pathinfo($value['value'], PATHINFO_EXTENSION);
                if (in_array($ext, $imgExtList)) {
                    $value['value'] = Common::cutoffSiteUploadPathPrefix($value['value']);
                }
                $result[$value['key']] = [
                    'name'  => $value['name'],
                    'value' => $value['value']
                ];
            }
        }
        ReturnJson(true, '', $result);
    }

    // 获取页面板块信息
    public function SettingValue(Request $request)
    {
        $key = $request->key;
        if (empty($key)) {
            ReturnJson(false, 'key is empty');
        }
        $result = [];
        $data = SystemValue::where('key', $key)
            ->where('status', 1)
            ->select(['name', 'key', 'value'])
            ->get()
            ->toArray();
        //图片后缀, 全部需要转换一遍
        $imgExtList = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg'];
        foreach ($data as $key => $value) {
            $ext = pathinfo($value['value'], PATHINFO_EXTENSION);
            if (in_array($ext, $imgExtList)) {
                $value['value'] = Common::cutoffSiteUploadPathPrefix($value['value']);
            }
            $result[] = [
                'name'  => $value['name'],
                'value' => $value['value']
            ];
        }
        ReturnJson(true, '请求成功', $result);
    }

    /**
     * 热搜关键词
     */
    public function ProductKeyword()
    {
        $data = $this->getKeyWords();
        ReturnJson(true, '', $data);
    }

    /**
     * 其他语言网站
     */
    public function OtherWebsite()
    {
        $data = $this->getWebSiteLan();
        ReturnJson(true, '', $data ?? []);
    }

    /**
     *
     * @param Request $request
     *
     * @return mixed
     */
    private function getSeoInfo(Request $request)
    {
        $link = isset($request->link) && !empty($request->link) ? $request->link : 'index';
        if (empty($link)) {
            ReturnJson(false, '参数错误');
        }
        $result = Menu::select(
            [
                'id',
                'name',
                'banner_pc',
                'banner_mobile',
                'banner_title',
                'banner_short_title',
                'banner_content',
                'seo_title',
                'seo_keyword',
                'seo_description'
            ]
        )->where(['link' => $link])->orderBy('sort', 'ASC')->first();
        // 没有则取id第一条记录，
        if (empty($result)) {
            return [];
            // ReturnJson(true, '', []);
        }
        // 若有栏目ID则优先使用栏目的TKD
        if (!empty($params['category_id'])) {
            $category = ProductsCategory::where(['id' => $params['category_id']])->select(
                'seo_title,seo_keyword,seo_description'
            )->first();
            $result['seo_title'] = $category['seo_title'] ? $category['seo_title'] : $result['seo_title'];
            $result['seo_keyword'] = $category['seo_keyword'] ? $category['seo_keyword'] : $result['seo_keyword'];
            $result['seo_description'] = $category['seo_description'] ? $category['seo_description']
                : $result['seo_description'];
        }
        $result['banner_pc'] = Common::cutoffSiteUploadPathPrefix($result['banner_pc']);
        $result['banner_mobile'] = Common::cutoffSiteUploadPathPrefix($result['banner_mobile']);


        // 首页存在轮播图，设定type = 4
        if (checkSiteAccessData(['qycn', 'qycojp',])) {
            $slideshowFirst = [[
                'name' => $result['name'],
                'banner_pc' => $result['banner_pc'],
                'banner_1280' => $result['banner_1280'],
                'banner_mobile' => $result['banner_mobile'],
                'banner_title' => $result['banner_title'],
                'banner_short_title' => $result['banner_short_title'],
                'banner_content' => $result['banner_content'],
            ]];
            // 查询是否有轮播图
            $slideshow = Menu::select(
                [
                    'name',
                    'banner_pc',
                    'banner_1280',
                    'banner_mobile',
                    'banner_title',
                    'banner_short_title',
                    'banner_content',
                    'redirect_url',
                ]
            )
                ->where(['parent_id' => $result['id']])
                ->where(['type' => 4])
                ->where(['status' => 1])
                ->orderBy('sort', 'ASC')
                ->get();
            if ($slideshowFirst && $slideshow) {
                $result['slideshow'] = array_values(array_merge($slideshowFirst, $slideshow->toArray()));
            }
        }
        return $result;
    }

    /**
     *
     *
     * @return mixed
     */
    private function getLinkList()
    {
        $link = Link::where('status', 1)
            ->select(['name', 'link'])
            ->orderBy('sort', 'ASC')
            ->get()
            ->toArray();

        return $link;
    }

    /**
     *
     *
     * @return array
     */
    private function getChianRegionData()
    {
        $app_name = env('APP_NAME');
        $cacheKey = $app_name . '_city_cache_key';
        $cityCacheData = Redis::get($cacheKey);
        if (empty($cityCacheData)) {
            $data = [];
            $provinces = City::select(['id', 'name'])->where(['type' => 1])->get()->toArray();
            foreach ($provinces as $province) {
                $data[] = [
                    "id"   => $province["id"],
                    "name" => $province["name"],
                    'sons' => City::select(['id', 'name'])->where(['type' => 2, 'pid' => $province['id']])->get()
                        ->toArray(),
                ];
            }
            Redis::set($cacheKey, json_encode($data));
        } else {
            $data = json_decode($cityCacheData, true);
        }

        return $data;
    }

    /**
     *
     *
     * @return mixed
     */
    private function getKeyWords()
    {
        $data = SearchRank::where('status', 1)->orderBy('hits', 'desc')->pluck('name');

        return $data;
    }

    /**
     *
     * @param Request $request
     *
     * @return array|string[]
     */
    private function getProductTags(Request $request): array
    {
        $category_id = $request->category_id;
        if (!empty($category_id)) { // 某个行业分类的全部标签
            $tags = ProductsCategory::select('product_tag')->where(['id' => $category_id])->value('product_tag');
            if (!empty($tags)) {
                $data = explode(',', $tags);
            } else {
                $data = [];
            }
        } else { // 全部行业分类的全部标签
            $tags = ProductsCategory::where('status', 1)->pluck('product_tag')->toArray();
            $result = '';
            $separator = ''; // 分隔符
            $tags = Array_filter($tags);
            if (!empty($tags) && is_array($tags)) {
                foreach ($tags as $tag) {
                    $result .= $separator . $tag;
                    $separator = ',';
                }
                $data = explode(',', $result);
            } else {
                $data = [];
            }
        }

        return $data;
    }

    /**
     *
     *
     * @return mixed
     */
    private function getWebSiteLan()
    {
        $data = LanguageWebsite::where('status', 1)
            ->select(['name', 'url'])
            ->orderBy('sort', 'ASC')
            ->get()
            ->toArray();

        return $data;
    }

    /**
     * 其它语言网站根据需求写在系统设置处，不再单独使用LanguageWebsite
     *
     * @return mixed
     */
    private function getWebSiteLanBySystem()
    {

        $siteInfoKey = 'site_lan';
        $setId = System::select(['id'])
            ->where('status', 1)
            ->where('alias', $siteInfoKey)
            ->get()
            ->value('id');
        $result = [];
        if ($setId) {
            $result = SystemValue::where('parent_id', $setId)
                ->where('status', 1)
                ->where('hidden', 1)
                ->select(['name', 'value as url' , 'back_value'])
                ->get()
                ->toArray();
        }

        return $result;
    }

    /**
     *
     *
     * @return array
     */
    private function getTopMenus()
    {
        $menus = Menu::where('status', 1)
            ->whereIn('type', [1, 3])
            ->select(
                [
                    'id',
                    'link',
                    'name',
                    'banner_title',
                    'banner_short_title',
                    'parent_id',
                    'seo_title',
                    'seo_keyword',
                    'seo_description'
                ]
            )
            ->orderBy('sort', 'ASC')
            ->get()->toArray();
        // 这里只处理两层，等需要多层再用递归
        $result = [];
        foreach ($menus as $key => $value) {
            // 首页返回的link改成空字符串
            if ($value['link'] == 'index') {
                $value['link'] = '';
            }
            if ($value['parent_id'] == 0 || $value['parent_id'] == null) {
                $result[$value['id']] = $value;
            }
        }
        foreach ($menus as $key => $value) {
            if ($value['parent_id'] > 0 && isset($result[$value['parent_id']])) {
                if (!isset($result[$value['parent_id']]['children'])) {
                    $result[$value['parent_id']]['children'] = [];
                }
                $result[$value['parent_id']]['children'][] = $value;
            }
        }

        return array_values($result);
        // foreach ($menus as $key => $value) {
        //     if($value['parent_id'] == 0 || $value['parent_id'] == null){
        //         $result[] = $value;
        //     }
        // }
        // $menus = $this->MenusTree($menus);
        // //大部分网站的研究报告菜单栏会有下拉报告分类
        // if($menus){
        //     foreach ($menus as $key => $item) {
        //         if($item['link'] == 'report-categories' ){
        //             $menus[$key]['children'] = ProductsCategory::getProductCategory(false);
        //             break;
        //         }
        //     }
        // }
        // return $menus;
    }

    /**
     *
     *
     * @return array
     */
    private function getButtonMenus()
    {
        $frontMenus = Menu::select([
            'id',
            'name',
            'link',
        ])
            ->where('parent_id', 0)
            ->whereIn('type', [2, 3])
            ->where('status', 1)
            ->orderBy('type', 'DESC')
            ->orderBy('sort', 'ASC')
            ->get()
            ->toArray();

        foreach ($frontMenus as $key => $frontMenu) {
            $sonMenus = Menu::select([
                'id',
                'name',
                'link',
                'seo_title',
                'seo_keyword',
                'seo_description'
            ])
                ->where('parent_id', $frontMenu['id'])
                ->whereIn('type', [2, 3])
                ->where('status', 1)
                ->orderBy('sort', 'ASC')
                ->get()
                ->toArray();
            $frontMenus[$key]['menus'] = $sonMenus;
        }

        if (checkSiteAccessData(['qycojp'])) {
            // qy.co.jp 要求数据分两部分，分有子菜单和无子菜单两个数组
            $newFrontMenus = [
                'has_son' =>[],
                'not_has_son' => [],
            ];
            foreach ($frontMenus as $key => $frontMenu) {
                if($frontMenus[$key]['menus'] && count($frontMenus[$key]['menus'])>0){
                    $newFrontMenus['has_son'][] = $frontMenu;
                }else{
                    $newFrontMenus['not_has_son'][] = $frontMenu;
                }
            }
            return $newFrontMenus;
        }

        return $frontMenus;
    }


    // 办公室 所在地点
    public function getofficeRegion(Request $request)
    {
        $list = Office::where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->get();
        foreach ($list as &$value) {
            $value['image'] = Common::cutoffSiteUploadPathPrefix($value['image']);
            $value['national_flag'] = Common::cutoffSiteUploadPathPrefix($value['national_flag']);
        }
        return $list;
    }

    /**
     *
     * @return array
     */
    private function getCustomersComment(Request $request): array
    {
        $pageSize = $request->comment_size ?? 4;
        $page = $request->comment_page ?? 1;

        $query = Comment::query()->where('status', 1);

        $count = (clone $query)->count();
        $list = $query->orderBy('id', 'desc')
            //->orderBy('sort', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get()
            ->toArray();

        if ($list) {
            foreach ($list as $key => $item) {
                $list[$key]['comment_at_format'] = date('Y-m-d', $item['comment_at']);
                $list[$key]['image'] = Common::cutoffSiteUploadPathPrefix($item['image']);
            }
        }

        $data = [
            'list' => $list,
            'count' => intval($count),
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => ceil($count / $pageSize),
        ];
        return $data;
    }

    /**
     *
     *
     * @return array
     */
    private function getCountryData() {
        $app_name = env('APP_NAME');
        $cacheKey = $app_name.'_country_cache_key';
        $countryCacheData = Redis::get($cacheKey);
        if (empty($countryCacheData)) {
            $countryList = Country::query()->select(['id' , 'name' , 'acronym' , 'code'])->where('status' , 1)->orderBy('sort' , 'asc')->get()->toArray();
            Redis::set($cacheKey, json_encode($countryList));
            return $countryList;
        } else {
            $countryList = json_decode($countryCacheData, true);
            return $countryList;
        }
    }

    private function getChannelData() {
        $channelList = Channel::query()->get()->toArray();
        return $channelList;
    }

    private function getPositionList() {
        $positionList = Position::query()->get()->toArray();
        return $positionList;
    }

    private function getLanguageVersion($isMultitude = false)
    {
        $publisher = [];
        if ($isMultitude) {
            $publisher = Products::select('publisher_id')
                ->distinct()
                ->pluck('publisher_id')?->toArray() ?? [];
        } else {
            $publisherId = Products::select('publisher_id')
                ->where('publisher_id', '>', 0)
                ->value('publisher_id');
            $publisher[] = $publisherId;
        }
        // 根据出版商查询价格版本的所有语言
        $priceEditionData = PriceEditions::query()->select(['id', 'publisher_id'])->where("is_deleted", 1)->where("status", 1)->get()->toArray();
        $editionIdList = [];
        foreach ($publisher as $publisherId) {
            foreach ($priceEditionData as $key => $priceEditionItem) {
                $priceEditionItem['publisher_id'] = explode(',', $priceEditionItem['publisher_id']);
                if (in_array($publisherId, $priceEditionItem['publisher_id'])) {
                    $editionIdList[] = $priceEditionItem['id'];
                }
            }
        }
        $editionIdList = array_unique($editionIdList);

        $languageIds = PriceEditionValues::query()->distinct()->select('language_id')->where("is_deleted", 1)->whereIn('edition_id', $editionIdList)->pluck('language_id')?->toArray() ?? [];

        $data = Languages::query()->select(['id', 'name'])->whereIn('id', $languageIds)->orderBy('sort', 'ASC')->get()?->toArray() ?? [];
        return $data;
    }
}
