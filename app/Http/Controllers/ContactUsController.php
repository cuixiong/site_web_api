<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DictionaryValue;
use App\Models\MessageLanguageVersion;
use App\Models\Country;
use App\Models\City;

class ContactUsController extends Controller
{

    // 免费样本/定制报告
    public function Add(Request $request)
    {
        try {
            $input = $request->all();
            $model = new \App\Models\ContactUs();
            $model = $model->create($input);
            // 发送验证邮件
            (new SendEmailController)->customized($model->id);
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

        // 国家
        $result['country'] = Country::where('status', 1)->select('id as value', 'data as label', 'code')->orderBy('sort', 'asc')->get()->toArray();;

        $provinces = City::where(['status' => 1, 'type' => 1])->select('id as value', 'name as label')->orderBy('id', 'asc')->get()->toArray();
        foreach ($provinces as $key => $province) {
            $cities = City::where(['status' => 1, 'type' => 2, 'pid' => $province['value']])->select('id as value', 'name as label')->orderBy('id', 'asc')->get()->toArray();
            $provinces[$key]['children'] = $cities;
        }
        $result['city'] = $provinces;

        // 语言版本
        $result['language_version'] = MessageLanguageVersion::where('status', 1)
            ->select(['name', 'id'])
            ->orderBy('sort', 'ASC')
            ->get()
            ->toArray();

        ReturnJson(true, '', $result);
    }
}
