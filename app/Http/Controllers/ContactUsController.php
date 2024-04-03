<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DictionaryValue;
use App\Models\MessageLanguageVersion;

class ContactUsController extends Controller
{

    // 免费样本/定制报告
    public function Add(Request $request)
    {
        try {
            $category_id = $request->category_id ? $request->category_id : 0; // 留言分类ID
            $product_id = $request->product_id; // 报告id

            $name = $request->name; // 姓名
            $email = $request->email; // 邮箱
            $company = $request->company; // 公司名称
            $channel = $request->channel; // 来源
            $buy_time = $request->buy_time; // 购买时间
            $content = $request->content; // 备注/内容
            $country_id = $request->country_id ? $request->country_id : 0; // 国家ID
            $province_id = $request->province_id ? $request->province_id : 0; // 省份ID
            $city_id = $request->city_id ? $request->city_id : 0; // 城市ID
            $phone = $request->phone; // 联系电话
            $model = new \App\Models\ContactUs();
            $model->category_id = $category_id;
            $model->product_id = $product_id;
            $model->name = $name;
            $model->email = $email;
            $model->company = $company;
            $model->channel = $channel;
            $model->buy_time = $buy_time;
            $model->content = $content;
            $model->country_id = $country_id;
            $model->province_id = $province_id;
            $model->city_id = $city_id;
            $model->phone = $phone;
            $model->save();
            // 发送验证邮件
            (new SendEmailController)->productSample($model->id);
            ReturnJson(true);
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    // 联系我们模块字典
    public function Dictionary(Request $request)
    {
        $result = [];
        // 计划购买时间
        $result['buy_time'] = DictionaryValue::GetDicOptions('Buy_Time');

        // 获知渠道 
        $result['channel'] = DictionaryValue::GetDicOptions('Channel_Type');

        // 语言版本
        $result['language_version'] = MessageLanguageVersion::where('status', 1)
            ->select(['name', 'id'])
            ->orderBy('sort', 'ASC')
            ->get()
            ->toArray();

        ReturnJson(true, '', $result);
    }
}
