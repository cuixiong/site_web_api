<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Helper\XunSearch;
use Illuminate\Http\Request;

class XunSearchTestController extends Controller
{
    public function clean(Request $request)
    {
        $xs = new XunSearch();
        $xs->clean();
        echo "完成".date('Y-m-d H:i:s',time());
    }
}