<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

class PublicController extends Controller {
    public function getClientIp(Request $request) {
        $res = [
            'ip' => $request->ip(),
        ];
        ReturnJson(true,'请求成功',$res);
    }

}
