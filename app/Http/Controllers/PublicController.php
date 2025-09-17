<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

class PublicController extends Controller {
    public function getClientIp(Request $request) {
        $ip = get_client_ip();
        $res = [
            'ip' => $ip,
        ];
        ReturnJson(true,'请求成功',$res);
    }

}
