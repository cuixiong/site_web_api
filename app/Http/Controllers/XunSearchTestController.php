<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Common\SendEmailController;
use App\Http\Controllers\Controller;
use App\Http\Helper\XunSearch;
use Illuminate\Http\Request;
use XS;

class XunSearchTestController extends Controller
{
    public function clean(Request $request)
    {
        $a = date("Y-m-d H:i:s" , 1714295009);
        dd($a);

//        $xs = new XunSearch();
//        $xs->clean();
//        echo "完成".date('Y-m-d H:i:s',time());
    }

    public function test(Request $request) {
        $input = $request->all();
        $keyword = $input['keyword'];

        $RootPath = base_path();
        $xs = new XS($RootPath.'/config/xunsearch/MMG_CN.ini');
        $search = $xs->search;
        $queryWords = "name:{$keyword}";
        $search->setQuery($queryWords);
        $docs = $search->search();
        $count = $search->count();
        dd([$docs , $count]);

        //测试 付款完成, 邮件
//        $smController = new SendEmailController();
//        $a = $smController->payment(1384);
//        dd($a);
    }

}
