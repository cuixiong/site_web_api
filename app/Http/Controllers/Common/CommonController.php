<?php

namespace App\Http\Controllers\Common;
use App\Http\Controllers\Controller;
use App\Http\Helper\XunSearch;
use App\Models\City;
use App\Models\Link;
use App\Models\Menu;
use App\Models\PlateValue;
use App\Models\ProductsCategory;
use App\Models\SystemValue;
use Illuminate\Http\Request;
class CommonController extends Controller
{
    /**
     * 顶部导航栏
     */
    public function TopMenus(Request $request)
    {
        $menus = Menu::where('status',1)
                    ->whereIn('type',[1,3])
                    ->select(['id','link','name','banner_title','banner_short_title','parent_id'])
                    ->get();
        $menus = $this->MenusTree($menus->toArray());
        ReturnJson(TRUE,'', $menus);
    }

    /**
     * 顶部导航栏递归方法
     */
    private function MenusTree($list,$parent = 0)
    {
        $result = [];
        foreach ($list as $key => $value) {
            if($value['id'] == $parent){
                $value['children'] = $this->MenusTree($list,$value['parent_id']);
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
        $link = $request->link ?? 'index';
        if(empty($link)){
            ReturnJson(false,'参数错误');
        }
        $result = Menu::select(['name','banner_pc','banner_mobile','banner_title','banner_short_title','seo_title','seo_keyword','seo_description'])->where(['link' => $link])->orderBy('sort','ASC')->first();
        // 若有栏目ID则优先使用栏目的TKD
        if(!empty($params['category_id'])){
            $category = ProductsCategory::where(['id' => $params['category_id']])->select('seo_title,seo_keyword,seo_description')->first();
            $result['seo_title'] = $category['seo_title'] ? $category['seo_title'] : $result['seo_title'];
            $result['seo_keyword'] = $category['seo_keyword'] ? $category['seo_keyword'] : $result['seo_keyword'];
            $result['seo_description'] = $category['seo_description'] ? $category['seo_description'] : $result['seo_description'];
        }
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
    public function BottomMenus(Request $request)
    {
        $frontMenus = Menu::select([
            'id',
            'name'
        ])
        ->where('parent_id',0)
        ->whereIn('type',[2,3])
        ->where('status',1)
        ->orderBy('type','DESC')
        ->orderBy('sort','ASC')
        ->get()
        ->toArray();
        foreach ($frontMenus as $key => $frontMenu) {
            $sonMenus = Menu::select([
                    'id',
                    'name',
                    'link',
                ])
                ->where('parent_id',$frontMenu['id'])
                ->whereIn('type',[2,3])
                ->where('status',1)
                ->orderBy('sort','ASC')
                ->get()
                ->toArray();
            $frontMenus[$key]['menus'] = $sonMenus;
        }
        ReturnJson(TRUE,'', $frontMenus);
    }

    /**
     * 报告设置
     * 打开新标签页、能否用F12键、能否用鼠标右键、复制【报告详情】页内容的控制开关
     */
    public function ControlPage(Request $request)
    {
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'ID不允许为空');
        }
        $data = SystemValue::where('parent_id',$id)
                ->where('status',1)
                ->select(['key','value'])
                ->get()
                ->toArray();
        $result = [];
        foreach ($data as $key => &$value) {
            $result[$value['key']] = intval($value['value']);
        }
        ReturnJson(true,'',$result);
    }

    /**
     * 友情链接
     */
    public function Link(Request $request){
        $link = Link::where('status',1)
                ->select(['name','link'])
                ->orderBy('sort','ASC')
                ->get()
                ->toArray();
        ReturnJson(true,'',$link);
    }

    // 购买流程
    public function PurchaseProcess(Request $request)
    {
        $id = $request->parentId;
        if(empty($id)){
            ReturnJson(false,'ID不允许为空');
        }
        $res = PlateValue::where('parent_id',$id)->where('status',1)->select(['id','title','link'])->get()->toArray();
        $res = $res ? $res : [];
        ReturnJson(true,'',$res);
    }

    /**
     * 产品标签
     */
    public function ProductTag(Request $request)
    {
        $category_id = $request->category_id;
        if(!empty($category_id)){ // 某个行业分类的全部标签
            $tags = ProductsCategory::select('product_tag')->where(['id'=>$category_id])->value('product_tag');
            if(!empty($tags)){
                $data = explode(',', $tags);
            }else{
                $data = [];
            }
        }else{ // 全部行业分类的全部标签
            $tags = ProductsCategory::where('status',1)->pluck('product_tag')->toArray();
            $result = '';
            $separator = ''; // 分隔符
            $tags = Array_filter($tags);
            if(!empty($tags) && is_array($tags)){
                foreach($tags as $tag){
                    $result .= $separator . $tag;
                    $separator = ',';
                }
                $data = explode(',', $result);
            }else{
                $data = [];
            }
        }
        ReturnJson(true,'',$data);
    }

    /**
     * 测试
     * 讯搜测试搜索
     */
    public function TestXunSearch(Request $request)
    {
        $keyword = $request->keyword;
        if(empty($keyword)){
            ReturnJson(false,'关键字不允许为空');
        }
        $xunsearch = new XunSearch();
        $res = $xunsearch->search($keyword);
        if($res){
            ReturnJson(true,'',$res);
        }else{
            ReturnJson(false,'查询失败');
        }
    }

    /** 
     * 中国省份、城市数据
     */
    public function ChinaRegions()
    {
        $provinces = City::select(['id','name'])->where(['type'=>1])->get()->toArray();
        foreach($provinces as $province){
            $data[] = [
                "id" => $province["id"],
                "name" => $province["name"],
                'sons' => City::select(['id','name'])->where(['type'=>2,'pid'=>$province['id']])->get()->toArray(),
            ];
        }
        ReturnJson(true,'',$data);
    }


    /**
     * 网站设置（包括了seo三要素）
     * 因为由于API端可能会接入多个个站点，所以当前需要前端当前站点的“站点设置”的ID来获取信息
     */
    public function Set(Request $request)
    {
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'id is empty');
        }
        $data = SystemValue::where('parent_id',$id)
                ->where('status',1)
                ->select(['name','key','value'])
                ->get()
                ->toArray();
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['key']] = [
                'name' => $value['name'],
                'value' => $value['value']
            ];
        }
        ReturnJson(true,'',$result);
    }

}
