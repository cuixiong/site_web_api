<?php

/**
 * ThirdRespController.php UTF-8
 * 第三方接收方接口
 *
 * @date    : 2024/6/11 14:51 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers\Third;

use App\Http\Controllers\Common\SendEmailController;
use App\Models\ContactUs;
use App\Models\Order;
use App\Models\Products;
use App\Models\ProductsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ThirdRespController extends BaseThirdController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function sendEmail()
    {
        $inputParams = request()->input();
        \Log::error('返回结果数据:' . json_encode([$inputParams]) . '  文件路径:' . __CLASS__ . '  行号:' . __LINE__);
        $code = $inputParams['code'];
        $res = false;
        $id = $inputParams['id'];
        if ($code == 'placeOrder') {
            $orderId = $inputParams['id'];
            $res = (new SendEmailController())->placeOrder($orderId);
        } elseif ($code == 'paySuccess') {
            $orderId = $inputParams['id'];
            $res = (new SendEmailController())->payment($orderId);
        } elseif ($code == 'contactUs') {
            //联系我们
            $res = (new SendEmailController())->contactUs($id);
        } elseif ($code == 'productSample') {
            //留言
            $res = (new SendEmailController())->productSample($id);
        } elseif ($code == 'sampleRequest') {
            //申请样本
            $res = (new SendEmailController())->productSample($id);
        } elseif ($code == 'customized') {
            //定制报告
            $res = (new SendEmailController())->customized($id);
        } else {
            //其它
            $res = (new SendEmailController)->sendMessageEmail($id);
        }
        ReturnJson($res);
    }

    public function testSendEmail()
    {
        $inputParams = request()->input();
        $code = $inputParams['action'];
        $testEmail = $inputParams['testEmail'];
        if (empty($code) || empty($testEmail)) {
            ReturnJson(false, '参数错误');
        }
        $AppName = env('APP_NAME');
        request()->headers->set('Site', $AppName); // 设置请求头
        $sendEmailController = new SendEmailController();
        $sendEmailController->testEmail = $testEmail;
        $res = true;
        if ($code == 'placeOrder') {
            //未下单
            $orderId = Order::query()->orderBy('id', 'asc')->value('id');
            $res = ($sendEmailController)->placeOrder($orderId);
        } elseif ($code == 'password') {
            //重置密码
            $res = ($sendEmailController)->ResetPassword($testEmail);
        } elseif ($code == 'contactUs') {
            //联系我们
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->contactUs($id);
        } elseif ($code == 'productSample') {
            //留言
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->Message($id);
        } elseif ($code == 'sampleRequest') {
            //申请样本
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->productSample($id);
        } elseif ($code == 'customized') {
            //定制报告
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->customized($id);
        } elseif ($code == 'payment') {
            //已下单
            $orderId = Order::query()->orderBy('id', 'asc')->value('id');
            $res = ($sendEmailController)->payment($orderId);
        } elseif ($code == 'register') {
            //已下单
            $res = ($sendEmailController)->register($testEmail);
        } elseif ($code == 'RegisterSuccess') {
            //已下单
            $res = ($sendEmailController)->RegisterSuccess($testEmail);
        } else {
            // 其它
            $id = ContactUs::query()->orderBy('id', 'asc')->value("id");
            $res = ($sendEmailController)->Message($id, $code);
        }
        ReturnJson($res);
    }

    public function syncRedisVal()
    {
        $inputParams = request()->input();
        $key = $inputParams['key'];
        $val = $inputParams['val'];
        $type = $inputParams['type'];
        if (empty($key)) {
            ReturnJson(false, '参数错误');
        }
        if ($type == 'delete') {
            Redis::del($key);
        } else {
            Redis::set($key, $val);
        }
        ReturnJson(true, '请求成功');
    }

    public function clearBan()
    {
        $inputParams = request()->input();
        $type = $inputParams['type'];
        $key = $inputParams['key'];
        if ($type == 1) {
            //清除IP封禁
            $cache_prefix_key = env('APP_NAME') . '_rate_limit:';
        } elseif ($type == 2) {
            //清除UA封禁
            $cache_prefix_key = env('APP_NAME') . '_rate_ua_limit:';
        }
        if (empty($cache_prefix_key)) {
            ReturnJson(false, '请求失败');
        }
        $reqKey = $cache_prefix_key . $key;
        //清除缓存
        // 删除多个键
        $keysToDelete = Redis::keys($reqKey . "*");
        //\Log::error('$keysToDelete:'.json_encode([$keysToDelete]).'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
        if (!empty($keysToDelete)) {
            Redis::del($keysToDelete);
        }
        ReturnJson(true, '请求成功');
    }

    public function getProductData(Request $request)
    {
        ini_set('max_execution_time', '0'); // no time limit，不设置超时时间（根据实际情况使用）
        ini_set("memory_limit", -1);
        try {
            $params = $request->all();
            $startTimestamp = $params['startTimestamp'] ?? 0;
            $num = $params['num'] ?? 100;
            $startProductId = $params['startProductId'] ?? null;

            // 行业数据
            $categoryData = ProductsCategory::select(['id', 'link'])->get()?->toArray() ?? [];
            $categoryData = array_column($categoryData, 'link', 'id');


            $baseQuery = \App\Models\Products::query()->orderBy('updated_at', 'asc')->orderBy('id', 'asc');
            // $baseQuery->where(function ($query) {
            //     $query->whereNotNull('keywords_jp')->where('keywords_jp', '<>', '');
            // });

            // 使用时间戳并且使用固定数量可能导致获取不完整，因此加入标记的报告id多查询一次
            $lastproductData = [];
            $isLast = true;
            if (!empty($startProductId)) {
                $lastbaseQuery = clone ($baseQuery);
                $lastbaseQuery->where(function ($query) use ($startTimestamp) {
                    $query->Where('updated_at', $startTimestamp); // 等于
                })
                    ->where('id', '>', $startProductId);

                $lastCount = (clone $lastbaseQuery)->count();
                if($lastCount && $lastCount > 100){
                    $lastbaseQuery = $lastbaseQuery->limit(100);
                    // 同一修改时间的报告数据未取尽，需等下一轮请求获取，并将此标志返回请求方，下次依旧以该修改时间请求
                    $isLast = false; 
                }
                $lastproductData = $lastbaseQuery->get()?->toArray() ?? [];
                if (!$lastproductData) {
                    $lastproductData = [];
                }
            }

            $productData = [];
            if($isLast){
                $baseQuery->where(function ($query) use ($startTimestamp) {
                    $query->Where('updated_at', '>', $startTimestamp); // 大于
                });
                $productData = $baseQuery->limit($num)->get()?->toArray() ?? [];
            }
            $productData = array_merge($lastproductData, $productData);

            $productNameArray = [];

            if ($productData) {
                foreach ($productData as $key => $product) {

                    $suffix = date('Y', strtotime($product['published_date']));
                    $productDescription = (new \App\Models\ProductDescription($suffix))
                        ->select([
                            'description',
                            'table_of_content',
                            'tables_and_figures',
                            'companies_mentioned',
                            'definition',
                            'overview'
                        ])
                        ->where('product_id', $product['id'])
                        ->first()?->toArray() ?? [];
                    $productData[$key] = array_merge($product, $productDescription ?? []);
                    $productNameArray[] = $product['name'];
                    $productData[$key]['category_link'] = $categoryData[$product['category_id']] ?? 0;
                }
            }
            return ['code' => 200, 'data' => $productData, 'isLast'=> $isLast];

            // ReturnJson(true, '', $productData);
            //code...
        } catch (\Throwable $th) {
            return ['code' => 500, 'msg' => $th->getMessage()];
        }
    }

    public function getProductKeywords(Request $request)
    {
        $params = $request->all();
        $urls = $params['url_data'] ?? [];
        if ($urls && !empty($urls)) {
            // $urls = json_decode($urls, true);
        } else {
            return ['code' => 500, 'msg' => '缺少参数'];
        }

        $data = Products::query()->distinct()
            ->select(['url', 'keywords'])
            ->whereIn('url', $urls)
            ->get();
        if ($data) {
            $data = $data->toArray();
            $data = array_column($data, 'keywords', 'url');
        }
        return ['code' => 200, 'data' => $data ?? []];
    }
}
