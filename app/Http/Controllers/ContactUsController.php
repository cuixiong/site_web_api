<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Models\ContactUs;
use App\Models\Plate;
use App\Models\PlateValue;
use App\Models\PostPlatform;
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

        $productId = $params['product_id']??0;
        $otherProductIds = []; // 新加变量防止影响其它站点
        // 多样本申请,传递的product_id是数组
        if(is_array($productId)){
            if(count($productId) > 0){
                $otherProductIds = $productId;
                unset($otherProductIds[0]);
                $otherProductIds = array_values($otherProductIds);
            }
            $productId = isset($productId[0]) && !empty($productId[0]) ? $productId[0] : 0;
        }else{
            $productId = isset($productId) && !empty($productId) ? $productId : 0;
        }

        $priceEdition = $params['price_edition']??0;
        $otherPriceEdition = [];
        // 多样本申请,传递的 price_edition 是数组
        if(is_array($priceEdition)){
            if(count($priceEdition) > 0){
                $otherPriceEdition = $priceEdition;
                unset($otherPriceEdition[0]);
                $otherPriceEdition = array_values($otherPriceEdition);
            }
            $priceEdition = isset($priceEdition[0]) && !empty($priceEdition[0]) ? $priceEdition[0] : 0;
        }else{
            $priceEdition = isset($priceEdition) && !empty($priceEdition) ? $priceEdition : 0;
        }

        $appName = env('APP_NAME', '');
        currentLimit($request, 60, $appName, $email . $productId);

        $sceneCode = $params['code'] ?? '';
        $category_id = MessageCategory::where('code', $sceneCode)->value('id');


        $model = new ContactUs();
        $model->name = $params['name'] ?? '';
        $model->product_name = !empty($params['product_name']) ? $params['product_name'] : '';
        $model->email = $email;
        $model->company = $params['company'] ?? '';
        if (!empty($params['buy_time'])) {
            $model->buy_time = $params['buy_time'] ?? '';
        } else {
            $model->buy_time = $params['plan_buy_time'] ?? '';
        }

        $model->country_id = $params['country_id'] ?? 0;
        $model->province_id = $params['province_id'] ?? 0;
        $model->city_id = $params['city_id'] ?? 0;
        $model->category_id = $category_id ?? 0;
        $model->phone = $params['phone'] ?? '';
        $model->content = $params['content'] ?? '';
        $model->language_version = $params['language'] ?? 0;
        $model->address = $params['address'] ?? '';
        $model->channel = $params['channel'] ?? 0;
        $model->channel_name = $params['channel_name'] ?? '';
        $model->department = $params['department'] ?? '';
        $model->consult_type = $params['consult_type'] ?? 0;

        $header = request()->header();
        $ua_info = $header['user-agent'];
        $model->ua_info = implode("\n", $ua_info);
        $model->ua_browser_name = $this->getBrowserName($model->ua_info);
        $HTTP_REFERER =  $params['http_referer'] ?? '';
        $model->referer = $HTTP_REFERER;
        $alias_id = $this->getAliasId($HTTP_REFERER, $model);
        $model->referer_alias_id = $alias_id;

        $copyModel = clone $model;
        $model->product_id = $productId;
        $model->price_edition = $priceEdition;

        if (!$model->save()) {
            ReturnJson(false, $model->getModelError());
        }
        //
        $saveIds = [];
        if(count($otherProductIds)>0){
            foreach ($otherProductIds as $key => $otherProductId) {
                $tempModel = clone $copyModel;
                $tempModel->product_id = $otherProductId;
                $tempModel->price_edition = $otherPriceEdition[$key] ?? 0;
                if (!$tempModel->save()) {
                    ReturnJson(false, $tempModel->getModelError());
                }else{
                    $saveIds[] = $tempModel->id;
                }
            }
        }

        // 根据code发送对应场景
        $sceneCode = $params['code'] ?? '';
        if ($sceneCode == 'productSample') {
            (new SendEmailController)->productSample($model->id);
        } elseif ($sceneCode == 'customized') {
            (new SendEmailController)->customized($model->id);
        } elseif ($sceneCode == 'contactUs') {
            (new SendEmailController)->contactUs($model->id);
        } else {
            (new SendEmailController)->sendMessageEmail($model->id, $sceneCode, $saveIds);
        }
        ReturnJson(true, '', $model);
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

    public function companyOverview() {
        try {
            $info = Plate::query()->select(['id', 'pc_image', 'mb_image', 'icon'])
                         ->whereIn('alias', ['company_overview'])
                         ->first();
            if (empty($info)) {
                ReturnJson(false, '暂无数据');
            }
            $list = PlateValue::query()->select([
                                                    'title',
                                                    'short_title',
                                                    'content',
                                                    'image',
                                                    'icon'
                                                ])->where('parent_id', $info['id'])
                              ->get()->toArray();
            $data = [];
            $data['content'] = $info;
            $data['list'] = $list;
            ReturnJson(true, '', $data);
        } catch (\Exception $e) {
            ReturnJson(false, '未知错误', $e->getMessage());
        }
    }

    /**
     *
     * @param mixed     $HTTP_REFERER
     * @param ContactUs $model
     *
     */
    private function getAliasId($HTTP_REFERER, ContactUs $model) {
        $aliasId = 0;
        if (!empty($HTTP_REFERER)) {
            $keywordsList = PostPlatform::query()->where("status", 1)->pluck('keywords', 'id')->toArray();
            foreach ($keywordsList as $forid => $forKeyword) {
                if (strpos($HTTP_REFERER, $forKeyword) !== false) {
                    $aliasId = $forid;
                    break;
                }
            }
        }

        return $aliasId;
    }

    public function getBrowserName($ua_info) {
        // 浏览器特征正则表达式库
        $browserPatterns = [
            'iOS'        => '/(iPhone|iPad|iPod)/i',          // iOS设备检测（优先级最高）
            'safari'     => '/safari\/[\d\.]+/i',            // 标准Safari浏览器
            'edge'       => '/edg\/[\d\.]+/i',
            'chrome'     => '/chrome\/[\d\.]+/i',
            'firefox'    => '/firefox\/[\d\.]+/i',
            'opr'        => '/opr\/[\d\.]+/i',    // Opera 新版标识
            'opr_legacy' => '/opera\/[\d\.]+/i',  // Opera 旧版标识
            'ie'         => '/trident/i',          // IE 11+ 特征
            'qqbrowser'  => '/mqbrowser|qqbrowser/i',// QQ浏览器
            'ucbrowser'  => '/ucbrowser\/[\d\.]+/i',  // UC浏览器
            'baidubox'   => '/baidubox/i',       // 百度浏览器
            'sogoumse'   => '/sogoumse/i',       // 搜狗浏览器
            '360'        => '/(360se|360ee|qihu 360)/i',
        ];
        $browser = 'Unknown';
        // 执行浏览器检测
        foreach ($browserPatterns as $for_browser => $pattern) {
            if (preg_match($pattern, $ua_info)) {
                $browser = ucfirst($for_browser);
                break;
            }
        }
        return $browser;
    }
}
