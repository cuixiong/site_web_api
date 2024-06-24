<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WxAuthController extends Controller {

    //获取微信授权码
    public function getWxAuthCode(Request $request) {
        $input = $request->all();
        if (isset($input['business_url']) && !empty($input['business_url'])) {
            $code = $input['code'] ?? '';
            $url = $input['business_url'];
            $state = $input['state'] ?? '';
            $referer = $input['referer'] ?? '';
            $jumpUrl = $url."?1=1&code=".$code.'&state='.$state.'&referer='.$referer;
            header("location:".$jumpUrl);
        } else {
            echo "no";
        }
    }
}
