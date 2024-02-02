<?php

namespace App\Http\Controllers;

use App\Models\Plate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
class PlateController extends Controller
{
    // 获取页面板块信息
    public function PlateValue(Request $request){
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'ID不允许为空');
        }
        $data = Plate::where('status',1)
                ->select([
                    'title',
                    'short_title',
                    'link',
                    'alias',
                    'image',
                    'icon',
                    'content',
                ])->find($id);
        $data = $data ? $data : [];
        ReturnJson(true,'请求成功',$data);
    }
}