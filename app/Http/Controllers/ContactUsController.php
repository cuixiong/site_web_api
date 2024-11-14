<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use App\Models\DictionaryValue;
use App\Models\Country;
use App\Models\City;
use App\Models\MessageCategory;

class ContactUsController extends Controller {
    // 免费样本/定制报告
    public function Add(Request $request) {
        $params = $request->all();
        $email = $params['email'] ?? '';
        //校验邮箱规则
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ReturnJson(false, '邮箱格式不正确');
        }
        $product_id = $params['product_id'] ?? 0;
        $appName = env('APP_NAME', '');
        currentLimit($request, 60, $appName, $email.$product_id);
        $sceneCode = $params['code'] ?? '';
        $category_id = MessageCategory::where('code', $sceneCode)->value('id');
        $model = new ContactUs();
        $model->name = $params['name'] ?? '';
        $model->email = $email;
        $model->company = $params['company'] ?? '';
        if (!empty($params['buy_time'])) {
            $model->buy_time = $params['buy_time'] ?? 0;
        } else {
            $model->buy_time = $params['plan_buy_time'] ?? 0;
        }
        $model->province_id = $params['province_id'] ?? 0;
        $model->city_id = $params['city_id'] ?? 0;
        $model->category_id = $category_id ?? 0;
        $model->phone = $params['phone'] ?? '';
        $model->content = $params['content'] ?? '';
        $model->product_id = $product_id;
        $model->language_version = $params['language'] ?? 0;
        $model->address = $params['address'] ?? '';
        if ($model->save()) {
            // 根据code发送对应场景
            $sceneCode = $params['code'] ?? '';
            if ($sceneCode == 'productSample') {
                (new SendEmailController)->productSample($model->id);
            } elseif ($sceneCode == 'customized') {
                (new SendEmailController)->customized($model->id);
            } elseif ($sceneCode == 'contactUs') {
                (new SendEmailController)->contactUs($model->id);
            } else {
                (new SendEmailController)->sendMessageEmail($model->id, $sceneCode);
            }
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


        ReturnJson(true, '', $result);
    }
}
