<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * 获取单页面内容
     */
    public function Content(Request $request){
        $link = $request->link;
        if(empty($link)){
            ReturnJson(false,'link is empty');
        }
        $domian = env('APP_URL','');
        $front_menu_id = Menu::where('link',$link)->value('id');
        $content = Page::where('page_id',$front_menu_id)->value('content');
        $content = str_replace('src="', 'src="'.$domian, $content);
        $content = str_replace('srcset="', 'srcset="'.$domian, $content);
        $content = str_replace('url("', 'url("'.$domian, $content);
        $content = str_replace('url("', 'url("'.$domian, $content);
        if(strpos($content,'%s')!==false){
            $year = bcsub(date('Y'), 2022); // 两个任意精度数字的减法
            $content = str_replace('%s', bcadd(15, $year), $content);
        }

        if($link=='about'){ // 其中的单页【公司简介】（link值是about）比较特殊：后台此单页富文本编辑器的内容要返回a和b两部分给前端，a和b中间嵌入其它内容。
            $divisionArray = explode('<div id="division"></div>',$content);
            $special = [
                'a' => $divisionArray[0],
                'b' => isset($divisionArray[1]) ? $divisionArray[1] : '',
            ];
            $content = $special;
        }
        ReturnJson(true,'',$content);
    }
}