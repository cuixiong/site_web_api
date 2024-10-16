<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Helper\XunSearch;
use App\Models\Authority;
use App\Models\City;
use App\Models\Common;
use App\Models\DictionaryValue;
use App\Models\LanguageWebsite;
use App\Models\Link;
use App\Models\Menu;
use App\Models\MessageLanguageVersion;
use App\Models\News;
use App\Models\PlateValue;
use App\Models\ProductsCategory;
use App\Models\QuoteCategory;
use App\Models\SearchRank;
use App\Models\System;
use App\Models\SystemValue;
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
        $data['language_website'] = $this->getWebSiteLan();
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
        $data['product_cagory'] = $this->getProductCagory();
        //权威引用
        $data['quote_list'] = $this->getQuoteList(0, 1 , 4);

        // 总控字典部分
        // 计划购买时间 ,原为联系我们控制器Dictionary函数中代码，现复制至此处
        $data['buy_time'] = DictionaryValue::GetDicOptions('Buy_Time');
        // 获知渠道,原为联系我们控制器Dictionary函数中代码，现复制至此处
        $data['channel'] = DictionaryValue::GetDicOptions('Channel_Type');

        // 语言版本
        $data['language_version'] = MessageLanguageVersion::where('status', 1)
                                                            ->select(['name', 'id'])
                                                            ->orderBy('sort', 'ASC')
                                                            ->get()
                                                            ->toArray();

        ReturnJson(true, '', $data);
    }

    public function getQuoteList($category_id = 0, $page = 1, $pageSize = 4) {
        $category = QuoteCategory::select(['id', 'name'])
                                 ->where("status" , 1)
                                 ->orderBy('sort', 'asc')->get()->toArray() ?? [];
        array_unshift($category, ['id' => '0', 'name' => '全部']);

        // 数据
        $model = Authority::select(['id', 'name as title', 'thumbnail as img', 'category_id'])
                          ->where("status" , 1)
                          ->orderBy('sort', 'asc');
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
    private function getProductCagory() {
        $field = ['id', 'name', 'link','icon'];
        $data = ProductsCategory::select($field)
                                ->where('status', 1)
                                ->get()
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
    public function getNewsList() {
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

    public function getSiteSetInfo() {
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
                $value['value'] = str_replace('%year',date('Y',time()),$value['value']);

                $ext = pathinfo($value['value'], PATHINFO_EXTENSION);
                if (in_array($ext, $imgExtList)) {
                    $value['value'] = Common::cutoffSiteUploadPathPrefix($value['value']);
                }
                $newItem = [
                    'name'  => $value['name'],
                    'value' => $value['value']
                ];
                if(isset($result[$value['key']]) && isset($result[$value['key']]['name'])){
                    $oldItem = $result[$value['key']];
                    $result[$value['key']]= [];
                    $result[$value['key']][] = $oldItem;
                    $result[$value['key']][] = $newItem;
                }elseif(isset($result[$value['key']]) && !isset($result[$value['key']]['name'])){
                    $result[$value['key']][] = $newItem;
                }else{
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
    public function TopMenus(Request $request) {
        $menus = $this->getTopMenus();
        ReturnJson(true, '', $menus);
    }

    /**
     * 顶部导航栏递归方法
     */
    private function MenusTree($list, $parent = 0) {
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
    public function info(Request $request) {
        $result = $this->getSeoInfo($request);
        // 若导航菜单的TKD为空则使用首页的TKD
        // $result['seo_title'] = $result['seo_title'] ? $result['seo_title'] : Setting::find()->select(['value'])->where(['alias' => 'seoTitle'])->scalar();
        // $result['seo_keyword'] = $result['seo_keyword'] ? $result['seo_keyword'] : Setting::find()->select(['value'])->where(['alias' => 'seoKeyword'])->scalar();
        // $result['seo_description'] = $result['seo_description'] ? $result['seo_description'] : Setting::find()->select(['value'])->where(['alias' => 'seoDescription'])->scalar();
        $data = $result ? $result : [];
        ReturnJson(true, '', $data);
    }

    /**
     * 底部导航
     */
    public function BottomMenus(Request $request) {
        $frontMenus = $this->getButtonMenus();
        ReturnJson(true, '', $frontMenus);
    }

    /**
     * 报告设置
     * 打开新标签页、能否用F12键、能否用鼠标右键、复制【报告详情】页内容的控制开关
     */
    public function ControlPage(Request $request) {
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
    public function Link(Request $request) {
        $link = $this->getLinkList();
        ReturnJson(true, '', $link);
    }

    // 购买流程
    public function PurchaseProcess(Request $request) {
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
    public function ProductTag(Request $request) {
        $data = $this->getProductTags($request);
        ReturnJson(true, '', $data);
    }

    /**
     * 测试
     * 讯搜测试搜索
     */
    public function TestXunSearch(Request $request) {
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
    public function ChinaRegions() {
        $data = $this->getChianRegionData();
        ReturnJson(true, '', $data);
    }

    /**
     * 网站设置（包括了seo三要素）
     * 因为由于API端可能会接入多个个站点，所以当前需要前端当前站点的“站点设置”的ID来获取信息
     */
    public function Set(Request $request) {
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
    public function SettingValue(Request $request) {
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
    public function ProductKeyword() {
        $data = $this->getKeyWords();
        ReturnJson(true, '', $data);
    }

    /**
     * 其他语言网站
     */
    public function OtherWebsite() {
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
                'name', 'banner_pc', 'banner_mobile', 'banner_title', 'banner_short_title', 'seo_title', 'seo_keyword',
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

        return $result;
    }

    /**
     *
     *
     * @return mixed
     */
    private function getLinkList() {
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
    private function getChianRegionData() {
        $app_name = env('APP_NAME');
        $cacheKey = $app_name.'_city_cache_key';
        $cityCacheData = Redis::get($cacheKey);
        if(empty($cityCacheData )) {
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
        }else{
            $data = json_decode($cityCacheData, true);
        }

        return $data;
    }

    /**
     *
     *
     * @return mixed
     */
    private function getKeyWords() {
        $data = SearchRank::where('status', 1)->orderBy('hits', 'desc')->pluck('name');

        return $data;
    }

    /**
     *
     * @param Request $request
     *
     * @return array|string[]
     */
    private function getProductTags(Request $request): array {
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
                    $result .= $separator.$tag;
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
    private function getWebSiteLan() {
        $data = LanguageWebsite::where('status', 1)
                               ->select(['name', 'url'])
                               ->orderBy('sort', 'ASC')
                               ->get()
                               ->toArray();

        return $data;
    }

    /**
     *
     *
     * @return array
     */
    private function getTopMenus() {
        $menus = Menu::where('status', 1)
                     ->whereIn('type', [1, 3])
                     ->select(
                         ['id', 'link', 'name', 'banner_title', 'banner_short_title', 'parent_id', 'seo_title',
                          'seo_keyword', 'seo_description']
                     )
                     ->orderBy('sort', 'ASC')
                     ->get()->toArray();

        // 这里只处理两层，等需要多层再用递归
        $result = [];
        foreach ($menus as $key => $value) {
            // 首页返回的link改成空字符串
            if($value['link'] == 'index'){
                $value['link'] = '';
            }
            if($value['parent_id'] == 0 || $value['parent_id'] == null){
                $result[$value['id']] = $value;
            }
        }

        foreach ($menus as $key => $value) {
            if($value['parent_id'] > 0 && isset($result[$value['parent_id']])){
                if(!isset($result[$value['parent_id']]['children'])){
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

        return $frontMenus;
    }
}
