<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Languages;
use App\Models\PriceEditions;
use App\Models\PriceEditionValues;
use App\Models\ProductDescription;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\ShopCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{
    /**
     * 购物车列表
     */
    public function List(Request $request)
    {
        $shopCart = ShopCart::from('shop_carts as cart')
                    ->select([
                        'cart.id', 
                        'cart.goods_id', 
                        'cart.number', 
                        'cart.price_edition',
                        'edition.name as price_name',
                        // 'language.language',
                        'products.url',
                        'products.name',
                        'products.price',
                        'products.discount_type', 
                        'products.discount_amount', 
                        'products.discount_time_begin', 
                        'products.discount_time_end',
                        'products.published_date',
                        'edition.rules',
                        'products.category_id',
                    ])
                    ->leftJoin('product_routine as products','products.id','=', 'cart.goods_id')
                    ->leftJoin('product_category as category', 'category.id','=','products.category_id')
                    ->leftJoin('price_edition_values as edition', 'cart.price_edition','=','edition.id')
                    // ->leftJoin('languages as language', 'edition.language_id','=','language.id')
                    
                    ->where([
                        // 'cart.user_id' => $request->user->id, 
                        'products.status' => 1 // product的status值如果是0，相当于删除这份报告
                    ])->get()->toArray();

        if (count($shopCart) < 1) { // 这个用户的购物车为空
            $data = [
                'result' => [],
                'goodsCount' => 0,
                'totalPrice' => 0,
            ];
            return ['code' => 0,'message' => '购物车为空', 'data' => $data];
        }
        $goodsCount = 0;
        $totalPrice = 0;
        $shopCartData = [];
        foreach($shopCart as $key=>$value){
            $shopCartData[$key]['thumb'] = ProductsCategory::where('id',$value['category_id'])->value('thumb');
            $shopCartData[$key]['name'] = $value['name'];
            $shopCartData[$key]['goods_id'] = $value['goods_id'];
            $shopCartData[$key]['url'] = $value['url'];
            $shopCartData[$key]['published_date'] = $value['published_date'] ? $value['published_date'] : '';
            // $shopCartData[$key]['languageId'] = $value['language']; // 原本是language，改为迁就前端的languageId
            $shopCartData[$key]['price_edition_cent'] = $value['price_name']; // 原本是edition，改为迁就前端的price_edition_cent
            $shopCartData[$key]['price_edition'] = $value['price_edition'];
            $shopCartData[$key]['price'] = eval("return " . sprintf($value['rules'], $value['price']) . ";");
            $shopCartData[$key]['number'] = intval($value['number']); // 把返回的number值由原来的字符类型变成整数类型
            
            $shopCartData[$key]['id'] = $value['id'];
            $shopCartData[$key]['discount_type'] = $value['discount_type'];
            $shopCartData[$key]['discount_amount'] = $value['discount_amount'];
            $shopCartData[$key]['discount_time_begin'] = $value['discount_time_begin'] ? date('Y-m-d', $value['discount_time_begin']) : '';
            $shopCartData[$key]['discount_time_end'] = $value['discount_time_end'] ? date('Y-m-d', $value['discount_time_end']) : '';
            
            // 这里的代码可以复用 开始
            $prices = [];
                // 计算报告价格
                $languages = Languages::select(['id', 'name'])->get()->toArray();
                if ($languages) {
                    foreach ($languages as $index => $language) {
                        $priceEditions = PriceEditionValues::select(['id', 'name as edition', 'rules as rule', 'notice'])->where(['language_id' => $language['id']])->get()->toArray();
                        $prices[$index]['language'] = $language['name'];
                        if ($priceEditions) {
                            foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                                $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                                $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['edition'];
                                $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                                $prices[$index]['data'][$keyPriceEdition]['price'] = eval("return " . sprintf($priceEdition['rule'], $value['price']) . ";");
                            }
                        }
                    }
                }
                $shopCartData[$key]['prices'] = $prices ?? [];
            // 这里的代码可以复用 结束
            
            $goodsCount += $value['number'];
            $totalPrice += bcmul(eval("return " . sprintf($value['rules'], $value['price']) . ";"), $value['number']);
        }
        // var_dump(1);die;

        $data = [
            'result' => $shopCartData,
            'goodsCount' => $goodsCount,
            'totalPrice' => $totalPrice,
        ];
        ReturnJson(true,'',$data);
    }
    /**
     * 购物车添加
     */
    public function Add(Request $request)
    {
        $goods_id = $request->goods_id; // id 改为 
        $number = $request->number ?? 0; // num 改为 
        $price_edition = $request->price_edition;

        $data = ShopCart::where([
            // 'user_id' => $request->user->id, 
            'user_id' => 0, 
            'goods_id' => $goods_id, 
            'price_edition' => $price_edition
        ])->first();
        if (!empty($data)) {
            $data->number += $number; // 添加数量
            if (!$data->save()) {
                ReturnJson(false,'',$data->getModelError());
            }
        } else { // 新增
            $model = new ShopCart();
            // $model->user_id = $request->user->id;
            $model->user_id = 0;
            $model->goods_id = $goods_id;
            $model->number = $number;
            $model->price_edition = $price_edition;
            if (!$model->save()) {
                ReturnJson(false,'',$model->getModelError());
            }
        }
        ReturnJson(false,'success');
    }

    /**
     * 购物车删除
     */
    public function Delete(Request $request)
    {
        $CartIds = $request->ids;
        if (!is_array($CartIds) && empty($CartIds)) {
            ReturnJson(false,'请选择需要删除的商品ID');
        }
        DB::beginTransaction();
        try {
            ShopCart::whereIn('id', $CartIds)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            ReturnJson(false);
        }
        ReturnJson(true);
    }

    /**
     * 购物车添加或减少商品数量
     */
    public function UpdataGoodsNumber(Request $request)
    {
        $id = $request->id;
        $number = $request->number;

        if (!is_numeric($id) || $id < 1 || !is_numeric($number) || $number < 1) {
            ReturnJson(false, '参数错误');
        }
        $res = ShopCart::where('id', $id)->where([
            // 'user_id' => $request->user->id,
            'status' => 1])->update(['number' => $number]);
        if(!$res){
            ReturnJson(false, '修改失败');
        }
        ReturnJson(true);
    }

    /**
     * 改变购物车里的某个产品的价格版本
     * @param interger cart_id  购物车编号
     * @param interger price_edition 价格版本号
     */
    public function ChangeEdition(Request $request)
    {
        
        $id = $request->id;
        $price_edition = $request->price_edition;
        if(empty($id)){
            ReturnJson(false,'购物车编号不能为空');
        }
        $data = ShopCart::find($id);
        if(empty($data)){
            ReturnJson(false,'购物车编号有误');
        }
        if(empty($price_edition)){
            ReturnJson(false,'价格版本号不能为空');
        }
        $data = ShopCart::where('id',$id)->first();
        if(!empty($data)){
            $data->price_edition = $price_edition;
            $data->save();
            ReturnJson(true);
        }
    }

    /**
     * 购物车同步接口
     * @param goods_id      商品编号 int
     * @param number        购买数量 int
     * @param price_edition 价格版本 int
     */
    public function Sync(Request $request)
    {
        $user = $request->user;
        if (is_null($user)) {
            ReturnJson(false, '用户未登录');
        }
        $frontData = $request->all();
        if (!is_array($frontData)) {
            ReturnJson(false, '参数错误');
        }
        $frontlen = count($frontData);
        if ($frontlen < 1) {
            ReturnJson(false, '参数错误');
        }
        $goodsArr = [];
        for ($i = 0, $len = count($frontData); $i < $len; $i++) {
            if (
                !is_numeric($frontData[$i]['goods_id']) || $frontData[$i]['goods_id'] < 1 ||
                !is_numeric($frontData[$i]['number']) || $frontData[$i]['number'] < 1
            ) {
                continue;
            }
            $goodsArr[] = $frontData[$i];
        }

        if (count($goodsArr) < 1) {
            ReturnJson(false, '参数错误');
        }

        $goodsIdArr = array_column($goodsArr, 'goods_id', null);
        $goodsIdArr = Products::where('status',1)->whereIn('id',$goodsIdArr)->pluck('id');

        // 会把无效的 good_id 过滤
        if (count($goodsIdArr) < 1) {
            ReturnJson(false, '参数错误');
        }
        $len = count($goodsArr);
        for ($i = 0; $i < $len; $i++) {
            if (!in_array($goodsArr[$i]['goods_id'], $goodsIdArr)) {
                unset($goodsArr[$i]);
            }
        }
        if ($len < 1) {
            ReturnJson(false, '参数错误');
        }
        //开始数据库操作
        DB::beginTransaction();
        $query = ShopCart::where(['user_id' => $user->id, 'status' => 1]);
        $backData = $query->select(['id', 'goods_id', 'number', 'price_edition',])
            ->keyBy('id')
            ->get()->toArray();
        $dbKey = [
            'user_id',
            'goods_id',
            'number',
            'price_edition',
            'created_at',
            'updated_at'
        ];
        $timestamp = time();
        $backlen = count($backData);
        if ($backlen < 1) { // 数据库里原本就没有数据
            $row = [];
            for ($i = 0; $i < $len; $i++) {
                $row[] = [
                    $user->id,
                    $goodsArr[$i]['goods_id'],
                    $goodsArr[$i]['number'],
                    $goodsArr[$i]['price_edition'],
                    $timestamp,
                    $timestamp,
                ];
            }
            $createShopCart = ShopCart::createMany($row);
            $batchInsert = $createShopCart->count();
            if ($batchInsert != $len) {
                DB::rollback();
                ReturnJson(false,'删除失败');
            }
            DB::commit();
            ReturnJson(true);
        }
        // 筛选出 id和版本 不存在的记录，这是需要新增的记录
        $oldData = [];
        foreach ($backData as $key => $backItem) {
            $tempKey = $backItem['goods_id'] . ',' . $backItem['price_edition'];
            $oldData[$tempKey] = $backItem['id'];
        }
        $needInsert = [];
        $needUpdate = [];
        for ($i = 0; $i < $len; $i++) {
            $tempKey = $goodsArr[$i]['goods_id'] . ',' . $goodsArr[$i]['price_edition'];
            if (!key_exists($tempKey, $oldData)) {
                $needInsert[] = $goodsArr[$i];
            } else {
                $backData[$oldData[$tempKey]]['number'] = $backData[$oldData[$tempKey]]['number'] > $goodsArr[$i]['number']?$backData[$oldData[$tempKey]]['number']:$goodsArr[$i]['number'];
                $needUpdate[] = $backData[$oldData[$tempKey]];
            }
        }
        $insertlen = count($needInsert);
        if ($insertlen > 0) {
            $row = [];
            foreach ($needInsert as $k => $item) {
                $row[] = [
                    $user->id,
                    $item['goods_id'],
                    $item['number'],
                    $item['price_edition'],
                    $timestamp,
                    $timestamp,
                ];
                array_splice($goodsArr, $k, 1);
            }
            $createShopCart = ShopCart::createMany($row);
            $batchInsert = $createShopCart->count();
            if ($batchInsert != $insertlen) {
                DB::rollback();
                ReturnJson(false,'删除失败');
            }
        }
        // 对比 id和版本 存在的记录，主要是对比数量，数量大的覆盖数量少的，数量相等的不用理，这是需要更新的记录
        $updatelen = count($needUpdate);
        if ($updatelen > 0) {
            for ($i = 0; $i < $updatelen; $i++) {
                if (!ShopCart::updateAll(
                    [
                        'number' => $needUpdate[$i]['number'],
                        'updated_at' => $timestamp
                    ],
                    [
                        'id' => $needUpdate[$i]['id']
                    ]
                )) {
                    DB::rollback();
                    ReturnJson(false,'删除失败');
                }
            }
        }
        DB::commit();
        ReturnJson(true);
    }

    /**
     * 分享购物车数据
     * @param string cart 
     * 参数值是类似于[{"goods_id":3,"price_edition":1,"number":1},{"goods_id":6,"price_edition":2,"number":3}]具有数组结构的字符串。
     * 注意一定要用双引号，不能用单引号。
     * cart 里面包含以下3个属性及值
     * goods_id       产品id
     * price_edition  价格版本id
     * number            数量
     */
    public function Share(Request $request)
    {
        $cart = $request->cart;
        $cart_array = json_decode($cart,true);   // 把接收到的参数通过英文分号分割成一个或多个数组
        $cart_array = [
            [
                'goods_id' => '502',
                'price_edition' => 118,
                'number' => 1,
            ],
            [
                'goods_id' => '333',
                'price_edition' => 118,
                'number' => 1,
            ],
        ];
        $results = [];
        if(!empty($cart_array)){
            $Nonexistent = 0; // 设置“购物车对应的商品列表数据里不存在的商品的数量”为0
            $goods = [];
            foreach($cart_array as $key=>$value){
                $product = Products::from('product_routine as product')
                ->leftJoin('product_category as category','product.category_id','=','category.id')
                ->select([
                    'category.thumb',
                    'product.name',
                    'product.id as goods_id',
                    'product.published_date',
                    'product.discount_type',
                    'product.discount_amount as discount_value',
                    'product.discount_time_begin as discount_begin',
                    'product.discount_time_end as discount_end',
                    'product.price',
                    'product.url'
                ])
                ->where([
                    'product.id' => $value['goods_id'],
                    'product.status' => 1
                ])
                ->first()->toArray();
                if(!empty($product)){
                    $results[] = $product;
                }else{
                    $Nonexistent++;
                    $goods[] = [
                        'goods_id' => $value['goods_id'],
                        'price_edition' => $value['price_edition'],
                    ];
                } 
                $results[$key]['published_date'] = $product['published_date'] ? date('Y-m-d', strtotime($product['published_date'])) : '';
                $results[$key]['discount_begin'] = $product['discount_begin'] ? date('Y-m-d', $product['discount_begin']) : '';
                $results[$key]['discount_end'] = $product['discount_end'] ? date('Y-m-d', $product['discount_end']) : '';
                $results[$key]['number'] = $value['number'];
                $results[$key]['price_edition'] = $value['price_edition'];
                $priceEdition = Redis::hget(PriceEditionValues::RedisKey,$value['price_edition']);
                if($priceEdition){
                    $priceEdition = json_decode($priceEdition,true);
                    $priceRule = $priceEdition ? ['language_id' => $priceEdition['language_id'],'edition' => $priceEdition['name'],'rule' => $priceEdition['rules']] : [];
                } else {
                    $priceRule = [];
                }                
                if(!empty($priceRule)){
                    $language = Redis::hget(Languages::RedisKey,$priceRule['language_id']);
                    if($language)
                    {
                        $language = json_decode($language,true);
                        $language = $language['name'];
                    } else {
                        $language = '';
                    }
                    $results[$key]['languageId'] = $language;
                    $results[$key]['price_edition_cent'] = $priceRule['edition'];
                    $price = Products::where('id',$value['goods_id'])->value('price');
                    if(!empty($price)){
                        $results[$key]['price'] = eval("return ".sprintf($priceRule['rule'], $price).";");
                    }
                }
            }
        }
        if($Nonexistent > 0 ){
            ReturnJson(false, $goods); // 产品不存在
        }else{
            ReturnJson(true, $results);
        }
    }

    /**
     * 相关报告
     * 根据报告的goods_ids值，获取相同的keyword值的报告
     * @param array goods_ids
     */
    public function Relevant(Request $request)
    {
        $goods_ids = $request->goods_ids;
        $data = [];
        if(!empty($goods_ids) && is_array($goods_ids)){
            $keywords = Products::whereIn('id', $goods_ids)->pluck('keywords')->toArray();
            if(!empty($keywords) && is_array($keywords)){
                $products = Products::select([
                    'name',
                    'id', 
                    'category_id',
                    'url',
                    'published_date',
                    'price',
                ])
                ->whereIn('keywords', $keywords)
                ->whereNotIn('id',$goods_ids)
                ->limit(5)
                ->get()
                ->toArray();
                if(!empty($products) && is_array($products)){
                    $data = [];
                    foreach($products as $index=>$product){
                        $data[$index]['thumb'] = ProductsCategory::where('id',$product['category_id'])->value('thumb');
                        $data[$index]['name'] = $product['name'];
                        $suffix = date('Y', strtotime($product['published_date']));
                        $description = (new ProductDescription($suffix))->where('product_id',$product['id'])->value('description');
                        $data[$index]['description_seo'] = $description;
                        $data[$index]['published_date'] = $product['published_date'] ? date('Y-m-d', strtotime($product['published_date'])) : '';
                        $data[$index]['price'] = $product['price'];
                        $data[$index]['id'] = $product['id'];
                        $data[$index]['url'] = $product['url'];
                    }
                }
            }            
        }
        ReturnJson(true,'',$data);
    }
}