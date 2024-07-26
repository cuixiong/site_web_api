<?php

namespace App\Http\Controllers;

use App\Models\Common;
use App\Models\PlateValue;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Plate;

class PlateController extends Controller
{
    /**
     * 板块信息列表
     */
    public function PlateValueList(Request $request)
    {
        // 维护这个列表不如查整个表
        // $nameList = [
        //     'indexabout', 'contact_us', 'reportbanner2', 'reports_special_statement', 'serviceSales', 'entity',
        //     'customized', 'about_us', 'service', 'information', 'process'
        // ];
        //分类列表
        $categoryList = Plate::select([
            'id',
            'alias',
            'pc_image as img',
            'mb_image as img_mobile',
            'title',
            'content as description'
        ])
        // ->whereIn('alias', $nameList)
        ->get()->toArray();

        $data = [];
        foreach ($categoryList as &$category) {
            $forData = [];
            $forData['category'] = $category;
            $forData['items'] = PlateValue::where('status', 1)
                ->where('parent_id', $category['id'])
                ->select([
                    'title',
                    'short_title',
                    'link',
                    'alias',
                    'image',
                    'icon',
                    'content',
                ])->get()->toArray();
            $alias = $category['alias'];
            $data[$alias] = $forData;
        }


        ReturnJson(true, '请求成功', $data);
    }

    // 获取页面板块信息
    public function PlateValue(Request $request)
    {
        $name = $request->name;
        if (empty($name)) {
            ReturnJson(false, '名称不允许空');
        }
        $ParentData = Plate::select([
            'id',
            'pc_image as img',
            'mb_image as img_mobile',
            'title',
            'content as description'
        ])->where('alias', $name)->first();
        if (empty($ParentData)) {
            ReturnJson(false, 'data is empty');
        }
        //转化图片路径
        $ParentData->img = Common::cutoffSiteUploadPathPrefix($ParentData->img);
        $ParentData->img_mobile = Common::cutoffSiteUploadPathPrefix($ParentData->img_mobile);
        $data = PlateValue::where('status', 1)
            ->where('parent_id', $ParentData->id)
            ->select([
                'title',
                'short_title',
                'link',
                'alias',
                'image',
                'icon',
                'content',
            ])->get();
        foreach ($data as &$value) {
            $value['image'] = Common::cutoffSiteUploadPathPrefix($value['image']);
            $value['icon'] = Common::cutoffSiteUploadPathPrefix($value['icon']);
        }
        $data = $data ? $data : [];
        $res = [
            'category' => $ParentData,
            'items'    => $data
        ];
        ReturnJson(true, '请求成功', $res);
    }

    // 获取页面板块信息-通过前台菜单id
    public function PlateValueByLink(Request $request)
    {
        $link = $request->link;
        if (empty($link)) {
            ReturnJson(false, '名称不允许空');
        }
        $menu_id = Menu::where('link', $link)->value('id');

        $categoryList = Plate::select([
            'id',
            'alias',
            'pc_image as img',
            'mb_image as img_mobile',
            'title',
            'content as description'
        ])->where('page_id', $menu_id)->get()->toArray();
        
        $data = [];
        foreach ($categoryList as &$category) {
            $forData = [];
            $forData['category'] = $category;
            $forData['items'] = PlateValue::where('status', 1)
                ->where('parent_id', $category['id'])
                ->select([
                    'title',
                    'short_title',
                    'link',
                    'alias',
                    'image',
                    'icon',
                    'content',
                ])->get()->toArray();
            $alias = $category['alias'];
            $data[$alias] = $forData;
        }
        ReturnJson(true, '请求成功', $data);
    }

    public function Form(Request $request)
    {
        $id = $request->id;
        if (empty($id)) {
            ReturnJson(false, 'ID不允许为空');
        }
        $data['category'] = Plate::where('status', 1)->select([
            'title',
            'pc_image',
            'mb_image',
            'content',
        ])->first();
        $data['items'] = PlateValue::where('status', 1)
            ->where('parent_id', $id)
            ->select([
                'title',
                'short_title',
                'icon'
            ])->get()->toArray();
        ReturnJson(true, '请求成功', $data);
    }
}
