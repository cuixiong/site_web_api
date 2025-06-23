<?php

namespace App\Http\Controllers;

use App\Models\Common;
use App\Models\PlateValue;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Plate;
use App\Models\SystemValue;

class PlateController extends Controller {
    /**
     * 板块信息列表
     */
    public function PlateValueList(Request $request) {
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
                                          'short_title',
                                          'icon',
                                          'content as description'
                                      ])
            // ->whereIn('alias', $nameList)
                             ->get()->toArray();
        $data = [];
        foreach ($categoryList as &$category) {
            if (strpos($category['description'], "%year") !== false) {
                $category['description'] = str_replace('%year', date('Y', time()), $category['description']);
            }
            if (strpos($category['short_title'], "%year") !== false) {
                $category['short_title'] = str_replace('%year', date('Y', time()), $category['short_title']);
            }
            if (strpos($category['title'], "%year") !== false) {
                $category['title'] = str_replace('%year', date('Y', time()), $category['title']);
            }
            $forData = [];
            $forData['category'] = $category;


            $items = PlateValue::where('status', 1)
                               ->where('parent_id', $category['id'])
                               ->select([
                                            'id',
                                            'title',
                                            'short_title',
                                            'link',
                                            'alias',
                                            'image',
                                            'icon',
                                            'content',
                                        ])->get()->toArray();
            foreach ($items as &$foritem) {
                if (strpos($foritem['content'], "{{%c}}") !== false) {
                    $foritem['content'] = str_replace('{{%c}}', intval(date('Y')) - 2007, $foritem['content']);
                }
                if (strpos($foritem['title'], "{{%c}}") !== false) {
                    $foritem['title'] = str_replace('{{%c}}', intval(date('Y')) - 2007, $foritem['title']);
                }
                if (strpos($foritem['short_title'], "{{%c}}") !== false) {
                    $foritem['short_title'] = str_replace('{{%c}}', intval(date('Y')) - 2007, $foritem['short_title']);
                }
            }
            $forData['items'] = $items;
            $alias = $category['alias'];
            $data[$alias] = $forData;
        }
        ReturnJson(true, '请求成功', $data);
    }

    // 获取页面板块信息
    public function PlateValue(Request $request) {
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
    public function PlateValueByLink(Request $request) {
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
                                          'icon',
                                          'title',
                                          'short_title',
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
            // 标题和短标题可能带有年份，需动态每年变化
            if ($forData['items'] && count($forData['items']) > 0) {
                // 网站设置获取品牌创立年份
                $establishYear = SystemValue::where('key', 'establish_year')->value('value');
                $year = date('Y', time());
                if ($establishYear && !empty($establishYear) && intval($establishYear) > 0) {
                    // 至今已有几年
                    $upToNow = date('Y', time()) - $establishYear;
                    foreach ($forData['items'] as $key => $item) {
                        $forData['items'][$key]['title'] = str_replace(
                            '%year', $year, $forData['items'][$key]['title']
                        );
                        $forData['items'][$key]['title'] = str_replace(
                            '%c', $upToNow, $forData['items'][$key]['title']
                        );
                        $forData['items'][$key]['short_title'] = str_replace(
                            '%year', $year, $forData['items'][$key]['short_title']
                        );
                        $forData['items'][$key]['short_title'] = str_replace(
                            '%c', $upToNow, $forData['items'][$key]['short_title']
                        );
                    }
                }
            }
            $alias = $category['alias'];
            //有相同的别名合并成数组
            if (isset($data[$alias]) && isset($data[$alias]['category'])) {
                $temp = $data[$alias];
                $data[$alias] = [];
                $data[$alias][] = $temp;
                $data[$alias][] = $forData;
            } else if (isset($data[$alias]) && !isset($data[$alias]['category'])) {
                $data[$alias][] = $forData;
            } else {
                $data[$alias] = $forData;
            }
        }
        ReturnJson(true, '请求成功', $data);
    }

    public function Form(Request $request) {
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
