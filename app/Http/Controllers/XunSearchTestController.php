<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\SystemValue;
use Illuminate\Http\Request;
use XS;

class XunSearchTestController extends Controller
{
    public function clean(Request $request)
    {
        $xs = new XS('/www/wwwroot/www.marketmonitorglobal.com.cn/api/config/xunsearch/MMG_CN.ini');
        $index = $xs->index;
        $index->clean();
    }
}