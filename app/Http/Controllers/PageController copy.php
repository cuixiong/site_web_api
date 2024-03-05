<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * 获取单页面内容
     */
    public function Get(Request $request){
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'ID is empty');
        }
        $data = Page::select('content')->find($id);
        $body = $data->content ? $data->content : "";
        ReturnJson(true,'',$body);
    }
}