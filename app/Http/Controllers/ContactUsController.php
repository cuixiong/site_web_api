<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DictionaryValue;

class ContactUsController extends Controller
{
    
    // 免费样本/定制报告
    public function Add(Request $request){
        try {
            $name = $request->name;// 姓名
            $email = $request->email;// 邮箱
            $company = $request->company;// 公司名称
            $channel = $request->channel;// 来源
            $buy_time = $request->buy_time;// 购买时间
            $product_id = $request->product_id; // 报告id
            $remarks = $request->remarks; // 备注/内容
            $category_id = $request->category_id ? $request->category_id : 0;// 留言分类ID
            $area_id = $request->area_id ? $request->area_id : 0;// 地区ID
            $model = new \App\Models\ContactUs();
            $model->name = $name;
            $model->email = $email;
            $model->company = $company;
            $model->channel = $channel;
            $model->category_id = $category_id;
            $model->buy_time = $buy_time;
            $model->area_id = $area_id;
            $model->product_id = $product_id;
            $model->remarks = $remarks;
            $model->save();
            // 发送验证邮件
            (new SendEmailController)->productSample($model->id);
            ReturnJson(true);
        } catch (\Exception $e) {
            ReturnJson(false,$e->getMessage());
        }
    }

    // 联系我们模块字典
    public function Dictionary(Request $request)
    {
        $result = [];
        $result['buy_time'] = DictionaryValue::GetDicOptions('Buy_Time'); // 购买时间
        ReturnJson(true, '',$result);
    }
}