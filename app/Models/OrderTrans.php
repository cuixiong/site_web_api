<?php

namespace App\Models;

use App\Const\ApiCode;
use App\Const\PayConst;
use App\Http\Controllers\Common\SendEmailController;
use App\Models\Base;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

class OrderTrans extends Base
{
    protected $table = 'orders';
    public    $errno = false;
    public    $msg   = '';
    public    $user;
    protected $baseProductFields
    = [
        'id AS goods_id',
        'price',
        'discount_time_begin',
        'discount_time_end',
        'discount_type',
        'discount_amount',
        'discount'
    ];

    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function getErrno()
    {
        return $this->errno;
    }

    public function getMsg()
    {
        if (empty($this->msg)) {
            // return ApiCode::$message[$this->errno];
            return '系统出现错误';
        }

        return $this->msg;
    }

    /**
     * 返回优惠后的金额
     */
    public function couponPrice($price, $coupon_id)
    {
        if (!empty($coupon_id)) {
            $coupon = Coupon::select(['type', 'value'])->where('id', $coupon_id)->first();
            if (!empty($coupon)) {
                if ($coupon['type'] == 1) { // 如果优惠类型是打折
                    $discountPrice = Common::getDiscountPrice($price, $coupon['value']); //打折价
                    $price = bcsub($price, $discountPrice, 2); //优惠金额
                } else if ($coupon['type'] == 2) { // 如果优惠类型是直减（type==2）
                    $price = $coupon['value'];
                }
            }
        }
        if ($price <= 0) {
            $price = 0;
        }

        return $price;
    }

    /**
     *
     * @param $goodsId
     * @param $priceEdition
     * @param $payCode
     * @param $coupon_id
     * @param $inputParams array 前端传递过来的参数
     *
     * @return Order|mixed|null
     */
    public function createBySingle($goodsId, $priceEdition, $payCode, $coupon_id, $inputParams)
    {
        $user = $this->user;
        $userId = $user->id;
        // 判断商品是否存在
        $goods = Products::select($this->baseProductFields)
            ->where(['id' => $goodsId, 'status' => 1])
            ->first();
        if (!$goods) {
            ReturnJson(false, '商品不存在');
        }
        $quantity = $inputParams['number'] ?? 0;
        if ($quantity <= 0) {
            ReturnJson(false, '商品数量为0');
        }
        DB::beginTransaction();
        $timestamp = time();
        $orderAmountSingle = Products::getPrice($priceEdition, $goods); // 单价
        $actuallyPaidSingle = Products::getPriceBy($orderAmountSingle, $goods, $timestamp); // 单价实付
        $orderAmount = bcmul($quantity, $orderAmountSingle, 2); // 总原价
        $caclueData = $this->calueTaxRate($payCode, $orderAmount); // 换算的数据
        if (empty($coupon_id)) {
            //原价打完折, 乘汇率 = 优惠价 .   然后原价*汇率  -  优惠价 = 实付金额
            $discountPrice = Products::getPriceBy($orderAmount, $goods, $timestamp);
            $actually_paid_all = $discountPrice;    // 实付价
            $caclueData['coupon_amount'] = bcsub($orderAmount, $actually_paid_all, 2);
            $caclueData['exchange_coupon_amount'] = bcmul($caclueData['coupon_amount'], $caclueData['exchange_rate'], 2);
        } else {
            // 本身打折与优惠券不能同时使用, 因此使用商品原价
            $actually_paid_all = $orderAmount;    // 实付价
            $caclueData['coupon_amount'] = $this->couponPrice($actually_paid_all, $coupon_id);
            $caclueData['exchange_coupon_amount'] = bcmul($caclueData['coupon_amount'], $caclueData['exchange_rate'], 2);
        }
        if ($actually_paid_all <= 0) {
            $this->errno = ApiCode::ORDER_AMOUNT_ERROR;

            return null;
        }
        $caclueData['actually_paid_all'] = $actually_paid_all;
        //计算税率
        $caclueData['tax_amount'] = bcmul($caclueData['actually_paid_all'], $caclueData['tax_rate'], 2);
        $caclueData['actually_paid_all'] = bcadd($caclueData['actually_paid_all'], $caclueData['tax_amount'], 2);
        $order = $this->addOrderData(
            $timestamp,
            $userId,
            $payCode,
            $orderAmount,
            $caclueData,
            $user,
            $coupon_id,
            $inputParams
        );
        if (empty($order)) {
            $this->errno = ApiCode::INSERT_FAIL;

            return null;
        }
        // 插入订单商品表 order_goods
        $orderGoods = new OrderGoods();
        $orderGoods->order_id = $order->id;
        $orderGoods->goods_id = $goodsId;
        // $orderGoods->goods_number = $number;
        $orderGoods->goods_number = $quantity; // 直接下单的话，只能是一件商品
        $orderGoods->goods_original_price = $orderAmountSingle;
        $orderGoods->goods_present_price = $actuallyPaidSingle;
        $orderGoods->price_edition = $priceEdition;
        $orderGoods->created_at = $timestamp;
        $orderGoods->updated_at = $timestamp;
        if (!$orderGoods->save()) {
            DB::rollBack();
            $this->errno = ApiCode::INSERT_FAIL;

            return null;
        }
        DB::commit();

        return $order;
    }

    /**
     *
     * @param $shopIdArr
     * @param $payCode
     * @param $coupon_id
     * @param $inputParams array 前端传递过来的参数
     *
     * @return Order|null
     */
    public function createByCart($shopIdArr, $payCode, $coupon_id, $inputParams)
    {
        $user = $this->user;
        $userId = $user->id;
        $shopCartList = ShopCart::query()->where(['user_id' => $userId])
            ->whereIn('id', $shopIdArr)
            ->where("status", 1)
            ->get()->toArray();

        return $this->shopCartSubmitOrder($shopCartList, $coupon_id, $payCode, $user, $inputParams);
    }

    /**
     *
     * @param $shopcarArr
     * @param $payType
     * @param $coupon_id
     * @param $inputParams array 前端传递过来的参数
     *
     * @return Order|null
     */
    public function createByCartWithoutLogin($shopcarArr, $payCode, $coupon_id, $inputParams)
    {
        $user = $this->user;

        return $this->shopCartSubmitOrder($shopcarArr, $coupon_id, $payCode, $user, $inputParams);
    }

    /**
     * 是否移动端访问
     *
     * @return bool
     */
    public function isMobileClient()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        //判断手机发送的客户端标志
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = [
                'nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile',
                'alipay'
            ];
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false)
                && (strpos(
                    $_SERVER['HTTP_ACCEPT'],
                    'text/html'
                ) === false
                    || (strpos(
                        $_SERVER['HTTP_ACCEPT'],
                        'vnd.wap.wml'
                    ) < strpos(
                        $_SERVER['HTTP_ACCEPT'],
                        'text/html'
                    )))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param int $timestamp
     * @param     $userId
     * @param     $payType
     * @param     $orderAmountAll
     * @param     $caclueData  array  价格(优惠,税率,汇率)信息
     * @param     $user
     * @param     $coupon_id
     * @param     $inputParams array 前端传递过来的参数
     *
     * @return Order|false
     */
    private function addOrderData(
        int $timestamp,
        $userId,
        $payCode,
        $orderAmountAll,
        $caclueData,
        $user,
        $coupon_id,
        $inputParams
    ) {
        // 插入订单表 order
        $order = new Order();
        $order->created_at = $timestamp;
        $order->updated_at = $timestamp;
        $order->order_number = date('YmdHis', $timestamp) . mt_rand(10, 99);
        $order->user_id = $userId;
        $order->is_pay = Order::PAY_UNPAID;
        // $order->pay_type = $payType;
        $order->pay_code = $payCode;
        $order->order_amount = $orderAmountAll; // 原价
        $order->actually_paid = $caclueData['actually_paid_all']; // 实付金额
        $order->username = $inputParams['username'];
        $order->email = $inputParams['email'];
        $order->phone = $inputParams['phone'];
        $order->company = $inputParams['company'];
        $order->department = !empty($inputParams['department']) ? $inputParams['department'] : '';
        $order->country_id = !empty($inputParams['country_id']) ? $inputParams['country_id'] : 0;
        $order->province_id = !empty($inputParams['province_id']) ? $inputParams['province_id'] : 0;
        $order->city_id = !empty($inputParams['city_id']) ? $inputParams['city_id'] : 0;
        $order->address = !empty($inputParams['address']) ? $inputParams['address'] : '';
        $order->remarks = !empty($inputParams['remarks']) ? $inputParams['remarks'] : '';
        $order->coupon_id = $coupon_id ? intval($coupon_id) : 0;
        $order->coupon_amount = !empty($caclueData['coupon_amount']) ? $caclueData['coupon_amount'] : 0; //优惠金额
        $order->exchange_rate = $caclueData['exchange_rate']; //汇率比例
        $order->exchange_amount = $caclueData['exchange_amount']; //汇率金额
        $order->tax_amount = $caclueData['tax_amount']; //税率金额
        $order->tax_rate = $caclueData['tax_rate']; //税率
        $order->pay_coin_type = $caclueData['pay_coin_type'] ?? PayConst::COIN_TYPE_CNY; //货币类型
        $order->is_mobile_pay = $this->isMobileClient() == true ? 1 : 0; // 是否为移动端支付：0代表否，1代表是。
        if (!$order->save()) {
            DB::rollBack();
            $this->errno = '插入订单失败';

            return false;
        }
        //插入订单成功后, 且使用了优惠券 + 登陆状态 标记使用优惠券  统一在订单回调中改变优惠券状态
        //        if (!empty($coupon_id) && !empty($userId)) {
        //            list($useStatus, $msg) = (new OrderService())->useCouponByUser($userId, $coupon_id, $order->id);
        //            if (empty($useStatus)) {
        //                DB::rollBack();
        //                $this->errno = '优惠券使用失败'.$msg;
        //
        //                return false;
        //            }
        //        }
        return $order;
    }

    /**
     *  计算折扣率
     */
    public function caclueRate() {}

    /**
     *
     * @param $shopCartList
     * @param $coupon_id
     * @param $payCode
     * @param $user
     * @param $inputParams array 前端传递过来的参数
     *
     * @return Order|mixed|null
     */
    private function shopCartSubmitOrder(
        $shopCartList,
        $coupon_id,
        $payCode,
        $user,
        $inputParams
    ) {
        $userId = $user->id;
        DB::beginTransaction();
        $timestamp = time();
        $lenShopCart = count($shopCartList);
        $goodsIdArr = array_column($shopCartList, 'goods_id');
        $goods = Products::query()
            ->select($this->baseProductFields)
            ->where('status', 1)
            ->whereIn('id', $goodsIdArr)
            ->get()->toArray();
        if (count($goods) < 1) {
            $this->errno = ApiCode::INVALID_PARAM;

            return null;
        }
        // 获取购物车有效的商品
        $realShopArr = [];
        foreach ($shopCartList as $key => $item) {
            foreach ($goods as $good) {
                if ($item['goods_id'] == $good['goods_id']) {
                    $realShopArr[] = array_merge($item, $good);
                }
            }
        }
        if (empty($realShopArr)) {
            $this->errno = ApiCode::SHOP_CART_NOT_EXIST;

            return null;
        }
        $orderAmountAll = 0;
        $actuallyPaidAll = 0;
        foreach ($realShopArr as &$shopItem) {
            $orderAmount = Products::getPrice($shopItem['price_edition'], $shopItem);
            $actuallyPaid = Products::getPriceBy(
                $orderAmount,
                $shopItem,
                $timestamp
            ); // 新增一个参数：$coupon_id(优惠券id)
            $shopItem['order_amount'] = $orderAmount;
            $shopItem['actually_paid'] = $actuallyPaid;
            $orderAmountAll = bcadd($orderAmountAll, bcmul($orderAmount, $shopItem['number'], 2), 2);
            $actuallyPaidAll = bcadd($actuallyPaidAll, bcmul($actuallyPaid, $shopItem['number'], 2), 2);
        }

        $caclueData = $this->calueTaxRate($payCode, $orderAmountAll);
        if (empty($coupon_id)) {
            $caclueData['coupon_amount'] = bcsub($orderAmountAll, $actuallyPaidAll, 2);
        } else {
            //使用优惠券后的优惠金额
            $caclueData['coupon_amount'] = $this->couponPrice($orderAmountAll, $coupon_id);
            $actually_paid_all = bcsub($orderAmountAll, $caclueData['coupon_amount'], 2);
        }
        if ($orderAmountAll <= 0 || $actually_paid_all <= 0) {
            $this->errno = ApiCode::ORDER_AMOUNT_ERROR;

            return null;
        }
        $caclueData['actually_paid_all'] = $actually_paid_all;
        //计算税率
        $caclueData['tax_amount'] = bcmul($caclueData['actually_paid_all'], $caclueData['tax_rate'], 2);
        $caclueData['actually_paid_all'] = bcadd($caclueData['actually_paid_all'], $caclueData['tax_amount'], 2);
        $order = $this->addOrderData(
            $timestamp,
            $userId,
            $payCode,
            $orderAmountAll,
            $caclueData,
            $user,
            $coupon_id,
            $inputParams
        );
        if (empty($order)) {
            $this->errno = ApiCode::INSERT_FAIL;

            return null;
        }
        // 插入订单商品表 order_goods
        $row = [];
        foreach ($realShopArr as $item) {
            $orderGoodsData = [
                'order_id'             => $order->id,
                'goods_id'             => $item['goods_id'],
                'goods_number'         => $item['number'],
                'goods_original_price' => $item['order_amount'],
                'goods_present_price'  => $item['actually_paid'],
                'price_edition'        => $item['price_edition'],
                'created_at'           => $timestamp,
                'updated_at'           => $timestamp,
            ];
            $row[] = $orderGoodsData;
        }
        $batchInsert = OrderGoods::insert($row);
        if ($lenShopCart != $batchInsert) {
            DB::rollBack();
            $this->errno = ApiCode::INSERT_FAIL;

            return null;
        }
        //OrderModel::sendOrderEmail($order, $user);
        DB::commit();

        return $order;
    }

    /**
     * 计算税率
     *
     * @param       $payCode
     * @param       $orderAmount
     *
     * @return      array
     */
    public function calueTaxRate($payCode, $orderAmount)
    {
        //支付配置税率/汇率配置
        // $payCode = Pay::query()->where("id", $payType)->value("code");
        $tax_rate = 0;
        $exchange_rate = 1;
        $pay_coin_type = PayConst::COIN_TYPE_CNY;
        $payInfo = Pay::query()->where("code", $payCode)->first();
        if (empty($payInfo)) {
            return [];
        }
        $tax_rate = $payInfo->pay_tax_rate ?? $tax_rate;
        $exchange_rate = $payInfo->pay_exchange_rate ?? $exchange_rate;
        $pay_coin_type = $payInfo->pay_coin_type ?? $pay_coin_type;
        //折后金额*汇率
        $exchange_amount = bcmul($orderAmount, $exchange_rate, 2);
        //        //税
        //        $tax_amount = bcmul($orderAmount, $tax_rate, 2);
        //        //实付金额 + 税
        //        $actuallyPaidAll = bcadd($orderAmount, $tax_amount, 2);
        $data = [];
        $data['tax_rate'] = $tax_rate;
        //        $data['tax_amount'] = $tax_amount;
        //        $data['actually_paid_all'] = $actuallyPaidAll;
        $data['exchange_amount'] = $exchange_amount; //汇率金额
        $data['exchange_rate'] = $exchange_rate;
        $data['pay_coin_type'] = $pay_coin_type;

        return $data;
    }

    /**
     * 获取支付税率
     *
     * @param $payCode
     *
     * @return array
     */
    public function getTaxRate($payCode)
    {
        //支付配置税率/汇率配置
        // $payCode = Pay::query()->where("id", $payType)->value("code");
        $tax_rate = 0;
        $exchange_rate = 1;
        $pay_coin_type = PayConst::COIN_TYPE_CNY;
        $payInfo = Pay::query()->where("code", $payCode)->first();
        if (empty($payInfo)) {
            return [$tax_rate, $exchange_rate];
        }
        $tax_rate = $payInfo->pay_tax_rate ?? $tax_rate;
        $exchange_rate = $payInfo->pay_exchange_rate ?? $exchange_rate;
        $pay_coin_type = $payInfo->pay_coin_type ?? $pay_coin_type;

        return [$tax_rate, $exchange_rate];
    }
}
