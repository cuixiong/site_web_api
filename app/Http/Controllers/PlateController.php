<?php

namespace App\Http\Controllers;

use App\Models\PlateValue;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plate;

class PlateController extends Controller
{
    // 获取页面板块信息
    public function PlateValue(Request $request){
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'ID不允许为空');
        }
        $data = PlateValue::where('status',1)
                ->where('parent_id',$id)
                ->select([
                    'title',
                    'short_title',
                    'link',
                    'alias',
                    'image',
                    'icon',
                    'content',
                ])->get();
        $data = $data ? $data : [];
        ReturnJson(true,'请求成功',$data);
    }

    public function Form(Request $request)
    {
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'ID不允许为空');
        }
        $data['category'] = Plate::where('status',1)->select([
            'title',
            'pc_image',
            'mb_image',
            'content',
            ])->first();
        $data['items'] = PlateValue::where('status',1)
        ->where('parent_id',$id)
        ->select([
            'title',
            'short_title',
            'icon'
        ])->get()->toArray();
        ReturnJson(true,'请求成功',$data);
    }
}