<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DictionaryValue;
use App\Models\MessageLanguageVersion;
use App\Models\Country;
use App\Models\City;

class ContactUsController extends Controller {
    // 免费样本/定制报告
    public function Add(Request $request) {
        $params = $request->all();
        $model = new ContactUs();
        $model->name = $params['name'] ?? '';
        $model->email = $params['email'] ?? '';
        $model->company = $params['company'] ?? '';
        if(!empty($params['buy_time'])){
            $model->buy_time = $params['buy_time'] ?? 0;
        }else{
            $model->buy_time = $params['plan_buy_time'] ?? 0;
        }
        $model->province_id = $params['province_id'] ?? 0;
        $model->city_id = $params['city_id'] ?? 0;
        $model->category_id = $params['category_id'] ?? 0;
        $model->phone = $params['phone'] ?? '';
        $model->content = $params['content'] ?? '';
        $model->product_id = $params['product_id'] ?? 0;
        if ($model->save()) {
            (new SendEmailController)->customized($model->id);
            ReturnJson(true, '', $model);
        } else {
            ReturnJson(false, $model->getModelError());
        }
    }

    // 联系我们模块字典
    public function Dictionary(Request $request) {
        $result = [];
        // 计划购买时间
        $result['buy_time'] = DictionaryValue::GetDicOptions('Buy_Time');
        // 获知渠道
        $result['channel'] = DictionaryValue::GetDicOptions('Channel_Type');
        // 国家
        $result['country'] = Country::where('status', 1)->select('id as value', 'data as label', 'code')->orderBy(
            'sort', 'asc'
        )->get()->toArray();;
        $provinces = City::where(['status' => 1, 'type' => 1])->select('id as value', 'name as label')->orderBy(
            'id', 'asc'
        )->get()->toArray();
        foreach ($provinces as $key => $province) {
            $cities = City::where(['status' => 1, 'type' => 2, 'pid' => $province['value']])->select(
                'id as value', 'name as label'
            )->orderBy('id', 'asc')->get()->toArray();
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
