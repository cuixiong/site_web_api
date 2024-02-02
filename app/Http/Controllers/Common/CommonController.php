<?php

namespace App\Http\Controllers\Common;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\ProductsCategory;
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
        if(empty($request->link)){
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
        $menus = Menu::where('status',1)
        ->whereIn('type',[2,3])
        ->select([
            'id',
            'link',
            'name',
            'banner_title',
            'banner_short_title',
            'parent_id'
        ])
        ->get();
        $menus = $this->MenusTree($menus->toArray());
        ReturnJson(TRUE,'', $menus);
    }

    // public function
}
