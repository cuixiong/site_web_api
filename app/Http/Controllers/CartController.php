<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Common;
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

class CartController extends Controller {
    /**
     * 购物车列表
     */
    public function List(Request $request) {
        $time = time();
        $shopCart = ShopCart::from('shop_carts as cart')
                            ->select([
                                         'cart.id',
                                         'cart.goods_id',
                                         'cart.number',
                                         'cart.price_edition',
                                         'edition.name as price_name',
                                         'edition.rules',
                                         'edition.language_id',
                                         // 'language.language',
                                         'products.url',
                                         'products.thumb',
                                         'products.name',
                                         'products.price',
                                         'products.discount_type',
                                         'products.discount',
                                         'products.discount_amount',
                                         'products.discount_time_begin',
                                         'products.discount_time_end',
                                         'products.published_date',
                                         'products.publisher_id',
                                         'products.category_id',
                                     ])
                            ->leftJoin('product_routine as products', 'products.id', '=', 'cart.goods_id')
                            ->leftJoin('price_edition_values as edition', 'cart.price_edition', '=', 'edition.id')
                            ->where([
                                        'cart.user_id'    => $request->user->id,
                                        'products.status' => 1 // product的status值如果是0，相当于删除这份报告
                                    ])
                            ->where('products.published_date' , '<=' , $time)
                            ->get()->toArray();
        if (empty($shopCart)) {
            $data = [
                'result'     => [],
                'goodsCount' => 0,
                'totalPrice' => 0,
            ];

            return ['code' => 0, 'message' => '购物车为空', 'data' => $data];
        }
        $goodsCount = 0;
        $totalPrice = 0;
        $shopCartData = [];
        $languageIdList = Languages::GetListById();
        $time = time();
        foreach ($shopCart as $key => $value) {
            if (!empty($value['thumb'])) {
                $thumbImg = $value['thumb'];
            } else {
                $thumbImg = ProductsCategory::where('id', $value['category_id'])->value('thumb');
            }
            $shopCartData[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($thumbImg);
            $shopCartData[$key]['name'] = $value['name'];
            $shopCartData[$key]['goods_id'] = $value['goods_id'];
            $shopCartData[$key]['url'] = $value['url'];
            $shopCartData[$key]['published_date'] = $value['published_date'] ? $value['published_date'] : '';
            $shopCartData[$key]['price_edition_cent'] = $value['price_name']; // 原本是edition，改为迁就前端的price_edition_cent
            $shopCartData[$key]['language_id'] = $value['language_id'];
            $shopCartData[$key]['language_name'] = $languageIdList[$value['language_id']] ?? 0;
            $shopCartData[$key]['price_edition'] = $value['price_edition'];
            $shopCartData[$key]['price'] = eval("return ".sprintf($value['rules'], $value['price']).";");
            $shopCartData[$key]['number'] = intval($value['number']); // 把返回的number值由原来的字符类型变成整数类型
            $shopCartData[$key]['id'] = $value['id'];
            $shopCartData[$key]['discount_type'] = $value['discount_type'];
            $shopCartData[$key]['discount_amount'] = $value['discount_amount'];
            $shopCartData[$key]['discount'] = $value['discount'];
            $shopCartData[$key]['discount_time_begin'] = $value['discount_time_begin'] ? date(
                'Y-m-d', $value['discount_time_begin']
            ) : '';
            $shopCartData[$key]['discount_time_end'] = $value['discount_time_end'] ? date(
                'Y-m-d', $value['discount_time_end']
            ) : '';
            //判断当前报告是否在优惠时间内
            if($value['discount_time_begin'] <= $time && $value['discount_time_end'] >= $time){
                $shopCartData[$key]['discount_status'] = 1;
            }else{
                $shopCartData[$key]['discount_status'] = 0;
            }
            // 计算报告价格
            $languages = Languages::GetList();
            $shopCartData[$key]['prices'] = Products::CountPrice(
                $value['price'], $value['publisher_id'], $languages
            ) ?? [];
            $goodsCount += $value['number'];
            $totalPrice += bcmul($shopCartData[$key]['price'], $value['number']);
        }
        $data = [
            'result'     => $shopCartData,
            'goodsCount' => $goodsCount,
            'totalPrice' => $totalPrice,
        ];
        ReturnJson(true, '', $data);
    }

    /**
     * 购物车添加
     */
    public function Add(Request $request) {
        $goods_id = $request->goods_id; // id 改为
        $number = $request->number ?? 0; // num 改为
        $price_edition = $request->price_edition;
        $data = ShopCart::where([
                                    'user_id'       => $request->user->id,
                                    'goods_id'      => $goods_id,
                                    'price_edition' => $price_edition
                                ])->first();
        if (!empty($data)) {
            $data->number += $number; // 添加数量
            if (!$data->save()) {
                ReturnJson(false, '', $data->getModelError());
            }
        } else { // 新增
            $model = new ShopCart();
            $model->user_id = $request->user->id;
            $model->goods_id = $goods_id;
            $model->number = $number;
            $model->price_edition = $price_edition;
            if (!$model->save()) {
                ReturnJson(false, '', $model->getModelError());
            }
        }
        ReturnJson(false, 'success');
    }

    /**
     * 购物车删除
     */
    public function Delete(Request $request) {
        $CartIds = $request->ids;
        if (!is_array($CartIds) && empty($CartIds)) {
            ReturnJson(false, '请选择需要删除的商品ID');
        }
        DB::beginTransaction();
        try {
            ShopCart::whereIn('id', $CartIds)->where("user_id", $request->user->id)->delete();
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
    public function UpdataGoodsNumber(Request $request) {
        $id = $request->id;
        $number = $request->number;
        if (empty($id) || $number < 1) {
            ReturnJson(false, '参数错误');
        }
        $res = ShopCart::where('id', $id)->where(['user_id' => $request->user->id, 'status' => 1])
                       ->update(['number' => $number]);
        if (!$res) {
            ReturnJson(false, '修改失败');
        }
        ReturnJson(true);
    }

    /**
     * 改变购物车里的某个产品的价格版本
     *
     * @param interger cart_id  购物车编号
     * @param interger price_edition 价格版本号
     */
    public function ChangeEdition(Request $request) {
        $id = $request->id;
        $price_edition = $request->price_edition;
        if (empty($id)) {
            ReturnJson(false, '购物车编号不能为空');
        }
        $data = ShopCart::find($id);
        if (empty($data)) {
            ReturnJson(false, '购物车编号有误');
        }
        if (empty($price_edition)) {
            ReturnJson(false, '价格版本号不能为空');
        }
        $data = ShopCart::where('id', $id)->where("user_id", $request->user->id)->first();
        if (!empty($data)) {
            $data->price_edition = $price_edition;
            $data->save();
            ReturnJson(true);
        }
    }

    /**
     * 购物车同步接口
     *
     * @param goods_id      商品编号 int
     * @param number        购买数量 int
     * @param price_edition 价格版本 int
     */
    public function Sync(Request $request) {
        $user = $request->user;
        if (is_null($user)) {
            ReturnJson(false, '用户未登录');
        }
        $frontData = $request->all();
        if (empty($frontData)) {
            ReturnJson(false, '参数错误');
        }
        $goodsArr = [];
        for ($i = 0, $len = count($frontData); $i < $len; $i++) {
            if (empty($frontData[$i]['goods_id']) || empty($frontData[$i]['number'])) {
                continue;
            }
            $goodsArr[] = $frontData[$i];
        }
        if (empty($goodsArr)) {
            ReturnJson(false, '参数错误');
        }
        $goodsIdArr = array_column($goodsArr, 'goods_id', null);
        $goodsIdArr = Products::where('status', 1)->whereIn('id', $goodsIdArr)->pluck('id');
        // 会把无效的 good_id 过滤
        if (empty($goodsIdArr)) {
            ReturnJson(false, '报告编号有误或无效');
        }
        // 过滤购物车无效的商品
        $len = count($goodsArr);
        for ($i = 0; $i < $len; $i++) {
            if (!in_array($goodsArr[$i]['goods_id'], $goodsIdArr)) {
                unset($goodsArr[$i]);
            }
        }
        if (empty($goodsArr)) {
            ReturnJson(false, '参数错误,无有效的报告');
        }
        //开始数据库操作
        DB::beginTransaction();
        $query = ShopCart::where(['user_id' => $user->id, 'status' => 1]);
        $backData = $query->select(['id', 'goods_id', 'number', 'price_edition',])
                          ->keyBy('id')
                          ->get()->toArray();
        $timestamp = time();
        $backlen = count($backData);
        if ($backlen < 1) { // 该用户当前没有购物车, 直接全部插入
            $this->addCardByData($len, $user, $goodsArr, $timestamp);
            ReturnJson(true, '请求成功');
        }
        // 筛选出 id和版本 不存在的记录，这是需要新增的记录
        $oldData = [];
        foreach ($backData as $key => $backItem) {
            $tempKey = $backItem['goods_id'].','.$backItem['price_edition'];
            $oldData[$tempKey] = $backItem['id'];
        }
        $needInsert = [];
        $needUpdate = [];
        for ($i = 0; $i < count($goodsArr); $i++) {
            $tempKey = $goodsArr[$i]['goods_id'].','.$goodsArr[$i]['price_edition'];
            if (!key_exists($tempKey, $oldData)) {
                $needInsert[] = $goodsArr[$i];
            } else {
                $backData[$oldData[$tempKey]]['number'] = $backData[$oldData[$tempKey]]['number']
                                                          > $goodsArr[$i]['number']
                    ? $backData[$oldData[$tempKey]]['number'] : $goodsArr[$i]['number'];
                $needUpdate[] = $backData[$oldData[$tempKey]];
            }
        }
        $insertlen = count($needInsert);
        if ($insertlen > 0) {
            $this->addCardByData($insertlen, $user, $needInsert, $timestamp);
        }
        // 对比 id和版本 存在的记录，主要是对比数量，数量大的覆盖数量少的，数量相等的不用理，这是需要更新的记录
        $updatelen = count($needUpdate);
        if ($updatelen > 0) {
            for ($i = 0; $i < $updatelen; $i++) {
                if (!ShopCart::updateAll(
                    [
                        'number'     => $needUpdate[$i]['number'],
                        'updated_at' => $timestamp
                    ],
                    [
                        'id' => $needUpdate[$i]['id']
                    ]
                )) {
                    DB::rollback();
                    ReturnJson(false, '删除失败');
                }
            }
        }
        DB::commit();
        ReturnJson(true);
    }

    /**
     * 分享购物车数据
     *
     * @param string cart
     * 参数值是类似于[{"goods_id":3,"price_edition":1,"number":1},{"goods_id":6,"price_edition":2,"number":3}]具有数组结构的字符串。
     * 注意一定要用双引号，不能用单引号。
     * cart 里面包含以下3个属性及值
     * goods_id       产品id
     * price_edition  价格版本id
     * number            数量
     */
    public function Share(Request $request) {
        $cart = $request->cart;
        $cart_array = json_decode($cart, true);   // 把接收到的参数通过英文分号分割成一个或多个数组
        $results = [];
        $Nonexistent = 0; // 设置“购物车对应的商品列表数据里不存在的商品的数量”为0
        if (!empty($cart_array)) {
            $goods = [];
            $languagesList = Languages::GetListById();
            $time = time();
            //语言列表
            $languages = Languages::GetList();
            foreach ($cart_array as $key => $value) {
                $product = Products::from('product_routine as product')
                                   ->leftJoin('product_category as category', 'product.category_id', '=', 'category.id')
                                   ->select([
                                                'category.thumb as category_thumb',
                                                'product.name',
                                                'product.id as goods_id',
                                                'product.thumb as thumb',
                                                'product.published_date',
                                                'product.category_id',
                                                'product.discount_type',
                                                'product.discount_amount as discount_amount',
                                                'product.discount as discount',
                                                'product.discount_time_begin as discount_begin',
                                                'product.discount_time_end as discount_end',
                                                'product.price',
                                                'product.publisher_id',
                                                'product.url'
                                            ])
                                   ->where([
                                               'product.id'     => $value['goods_id'],
                                               'product.status' => 1,
                                           ])
                                    ->where('product.published_date' , '<=' , $time)
                                    ->first();

                if (!empty($product)) {
                    $product = $product->toArray();
                    $results[$key] = $product;
                    if (!empty($product['thumb'])) {
                        $results[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($product['thumb']);
                    } else {
                        $results[$key]['thumb'] = Common::cutoffSiteUploadPathPrefix($product['category_thumb']);
                    }
                    $results[$key]['published_date'] = $product['published_date'];
                    $results[$key]['discount_begin'] = $product['discount_begin'] ? date(
                        'Y-m-d', $product['discount_begin']
                    ) : '';
                    $results[$key]['discount_end'] = $product['discount_end'] ? date('Y-m-d', $product['discount_end'])
                        : '';
                    $results[$key]['discount_type'] = $product['discount_type'];
                    $results[$key]['discount'] = $product['discount'];
                    $results[$key]['discount_amount'] = $product['discount_amount'];
                    //判断当前报告是否在优惠时间内
                    if($product['discount_begin'] <= $time && $product['discount_end'] >= $time){
                        $results[$key]['discount_status'] = 1;
                    }else{
                        $results[$key]['discount_status'] = 0;
                    }


                    $results[$key]['number'] = $value['number'];
                    $results[$key]['price_edition'] = $value['price_edition'];
                    $priceEditionInfo = PriceEditionValues::find($value['price_edition']);


                    $results[$key]['prices'] = Products::CountPrice(
                        $product['price'], $product['publisher_id'], $languages
                    ) ?? [];

                    if (!empty($priceEditionInfo)) {
                        $price = $product['price'];
                        $results[$key]['languageId'] = $priceEditionInfo->language_id;
                        $results[$key]['price_edition_name'] = $priceEditionInfo->name;
                        $results[$key]['price_edition_cent'] = $priceEditionInfo->edition_id;
                        $results[$key]['price'] = eval("return ".sprintf($priceEditionInfo->rules, $price).";");
                        $results[$key]['language_name'] = $languagesList[$priceEditionInfo->language_id];
                    } else {
                        $results[$key]['price_edition_name'] = '';
                        $results[$key]['languageId'] = '';
                        $results[$key]['price_edition_cent'] = '';
                        $results[$key]['price'] = '';
                        $results[$key]['language_name'] = '';
                    }
                } else {
                    $Nonexistent++;
                    $goods[] = [
                        'goods_id'      => $value['goods_id'],
                        'price_edition' => $value['price_edition'],
                    ];
                }
            }
        }
        ReturnJson(true, '', $results);
//        if ($Nonexistent > 0) {
//            ReturnJson(false, $goods); // 产品不存在
//        } else {
//            ReturnJson(true, '', $results);
//        }
    }

    /**
     * 相关报告
     * 根据报告的goods_ids值，获取相同的keyword值的报告
     *
     * @param array goods_ids
     */
    public function Relevant(Request $request)
    {
        $goods_ids = $request->goods_ids;
        $data = [];
        if (!empty($goods_ids) && !is_array($goods_ids)) {
            $goods_ids = explode(',',$goods_ids);
        }
        if (!empty($goods_ids) && is_array($goods_ids)) {
            $keywords = Products::whereIn('id', $goods_ids)->pluck('keywords')->toArray();
            if (!empty($keywords) && is_array($keywords)) {
                $products = Products::select([
                    'id',
                    'name',
                    'thumb',
                    'category_id',
                    'url',
                    'published_date',
                    'price',
                ])
                    ->whereIn('keywords', $keywords)
                    ->whereNotIn('id', $goods_ids)
                    ->limit(5)
                    ->get()
                    ->toArray();
                if (!empty($products) && is_array($products)) {
                    $data = [];
                    foreach ($products as $index => $product) {
                        $data[$index]['thumb'] = Products::getThumbImgUrl($product);
                        $data[$index]['name'] = $product['name'];
                        $suffix = date('Y', strtotime($product['published_date']));
                        $description = (new ProductDescription($suffix))->where('product_id', $product['id'])->value(
                            'description'
                        );
                        $data[$index]['description_seo'] = $description;
                        $data[$index]['published_date'] = $product['published_date'] ? date(
                            'Y-m-d',
                            strtotime(
                                $product['published_date']
                            )
                        ) : '';
                        $data[$index]['price'] = $product['price'];
                        $data[$index]['id'] = $product['id'];
                        $data[$index]['url'] = $product['url'];
                    }
                }
            }
        }
        ReturnJson(true, '', $data);
    }

    /**
     *
     * @param int   $len
     * @param mixed $user
     * @param array $goodsArr
     * @param int   $timestamp
     *
     *
     */
    private function addCardByData(int $len, mixed $user, array $goodsArr, int $timestamp) {
        $row = [];
        for ($i = 0; $i < $len; $i++) {
            $add_data = [
                'user_id'       => $user->id,
                'goods_id'      => $goodsArr[$i]['goods_id'],
                'number'        => $goodsArr[$i]['number'],
                'price_edition' => $goodsArr[$i]['price_edition'],
                'created_at'    => $timestamp,
                'updated_at'    => $timestamp,
            ];
            $row[] = $add_data;
        }
        $createShopCart = ShopCart::insert($row);
        $batchInsert = $createShopCart->count();
        if ($batchInsert != $len) {
            DB::rollback();
            ReturnJson(false, '新增失败');
        }
        DB::commit();
    }

    public function goodsExist(Request $request) {
        $goods_id_list = $request->goods_id_list ?? [];
        if(empty($goods_id_list )){
            ReturnJson(false, '参数异常');
        }

        //new Products();
        $existGoodsIdlist = Products::query()->whereIn("id" , $goods_id_list)
            ->where("status" , 1)
            ->where("published_date" , "<=", time())
            ->pluck("id")->toArray();

        $data['goods_id_list'] = $existGoodsIdlist;
        ReturnJson(true, 'ok' , $data);

    }
}
