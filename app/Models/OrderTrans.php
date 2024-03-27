<?php

namespace App\Models;
use App\Http\Controllers\Common\SendEmailController;
use App\Models\Base;
use Illuminate\Support\Facades\DB;

class OrderTrans extends Base
{
    protected $table = 'orders';
    public $errno = false;
    public $msg = '';
    public $user;

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
     * 下单时使用优惠券后计算价格
     */
    public function couponPrice($price,$coupon_id)
    {
        if(!empty($coupon_id)){
            $coupon = Coupon::select(['type','value'])->where('id',$coupon_id)->first();
            if($coupon['type']==1){ // 如果优惠类型是打折
                $price = $price * $coupon['value'] / 100;
            }else if($coupon['type']==2){ // 如果优惠类型是直减（type==2）
                $price = bcsub($price, $coupon['value'],2);
            }
        }else{
            $price = $price;
        }
        return round($price, 2); // round()函数能把金额四舍五入到两位小数，防止3位小数点的金额支付失败（实际情况是不会出现这种bug的）
    }

    public function createBySingle($goodsId, $priceEdition, $payType, $coupon_id, $address, $remarks)
    {
        $user = $this->user;
        $userId = $user->id;
        // 判断商品是否存在
        $goods = Products::select([
            'id', 
            'price',
            'discount_type', 
            'discount_amount',
            'discount_time_begin',
            'discount_time_end',
            'published_date',
        ])
        ->where(['id' => $goodsId, 'status' => 1])->first();
        if (!$goods) {
            $this->errno = ApiCode::INVALID_PARAM;
            return null;
        }
        DB::beginTransaction();
        $timestamp = time();
        $orderAmount = Products::getPrice($priceEdition, $goods); // 订单金额
        $actuallyPaid = Products::getPriceBy($orderAmount, $goods, $timestamp, $coupon_id); // 实付金额（订单金额减去优惠金额后的实际支付金额）,新增一个参数：$coupon_id(优惠券id)
        // $actuallyPaid = $this->couponPrice($actuallyPaid,$coupon_id);
        if(empty($coupon_id)){ // 如果用户下单时没有使用优惠券或优惠券折后价不如产品原本的折后价便宜（前端会处理这一逻辑），那么，$coupon_id的值是空。
            $actuallyPaid = $actuallyPaid; // 就使用没有优惠券的价格（可能是产品原价，也可能是产品自带的折后价，反正不是使用优惠券后的价格）
        }else{                 // 如果用户下单时有使用优惠券
            $actuallyPaid = $this->couponPrice($orderAmount,$coupon_id); // 就按照使用优惠券的价格，而不是按照产品自带的折后价
        }

        // 插入订单表 order
        $order = new self();
        $order->created_at = $timestamp;
        $order->updated_at = $timestamp;
        $order->order_number = date('YmdHis', $timestamp).mt_rand(10, 99);
        $order->user_id = $userId;
        // $order->is_pay = OrderModel::PAY_UNPAID;
        $order->is_pay = 0;
        $order->pay_type = $payType;
        $order->order_amount = $orderAmount;
        $order->actually_paid = round($actuallyPaid, 2);
        $order->username = $user->username;
        $order->email = $user->email;
        $order->phone = $user->phone;
        $order->company = $user->company;
        $order->province_id = $user->province_id;
        $order->city_id = $user->city_id;
        $order->status = 0;
        $order->address = $address;
        $order->coupon_id = $coupon_id ? intval($coupon_id) : 0;
        // $order->position = !empty($user->position)?$user->position:(new Ip2Location())->getLocation(Yii::$app->request->userIP)->area;
        // $order->position = !empty($user->position)?$user->position : 0;
        $order->is_mobile_pay = $this->isMobileClient()==true ? 1 : 0; // 是否为移动端支付：0代表否，1代表是。
        $order->remarks = $remarks;
        $order->created_by = $userId;
        if (!$order->save()) {
            DB::rollBack();
            // $this->errno = ApiCode::INSERT_FAIL;
            $this->errno = '';
            return null;
        }

        // 插入订单商品表 order_goods
        $orderGoods = new OrderGoods();
        $orderGoods->order_id = $order->id;
        $orderGoods->goods_id = $goodsId;
        // $orderGoods->goods_number = $number;
        $orderGoods->goods_number = 1; // 直接下单的话，只能是一件商品
        $orderGoods->goods_original_price = $orderAmount;
        $orderGoods->goods_present_price = $actuallyPaid;
        $orderGoods->price_edition = $priceEdition;
        $orderGoods->created_at = $timestamp;
        $orderGoods->updated_at = $timestamp;

        if (!$orderGoods->save()) {
            DB::rollBack();
            // $this->errno = ApiCode::INSERT_FAIL;
            $this->errno = '';
            return null;
        }
        (new SendEmailController)->placeOrder($orderGoods->id);//暂时注释
        // OrderModel::sendOrderEmail($order, $user);
        // OrderModel::sendPaymentEmail($order); // 发送已付款的邮件 // 记得把这行代码删掉
        DB::commit();

        return $order;
    }

    public function createByCart($shopIdArr, $payType, $coupon_id, $address, $remarks)
    {
        $user = $this->user;
        $userId = $user->id;

        $transaction = DB::beginTransaction();
        $timestamp = time();

        $shopCart = ShopCart::find()->alias('shop_cart')->select([
                        'shop_cart.goods_id AS goods_id',
                        'shop_cart.id AS shop_cart_id',
                        'shop_cart.number',
                        'shop_cart.price_edition',
                        'product.price',
                        'product.discount_type',
                        'product.discount_amount',
                        'product.discount_time_begin',
                        'product.discount_time_end',
                    ])->leftJoin(['product' => Products::tableName()],
                        'shop_cart.goods_id = product.id')
                    ->where([
                        'shop_cart.id' => $shopIdArr,
                        'shop_cart.user_id' => $userId,
                        'shop_cart.status' => 1,
                        'product.status' => 1
                    ])->asArray()->all();

        $lenShopCart = count($shopCart);
        if ($lenShopCart < 1) {
            // $this->errno = ApiCode::INSERT_FAIL;
            $this->errno = '';
            return null;
        }

        $orderAmountAll = 0;
        $actuallyPaidAll = 0;
        for ($i = 0; $i < $lenShopCart; $i++) {
            $orderAmount = Products::getPrice($shopCart[$i]['price_edition'], $shopCart[$i]);
            $actuallyPaid = Products::getPriceBy($orderAmount, $shopCart[$i], $timestamp);

            $shopCart[$i]['order_amount'] = $orderAmount;
            $shopCart[$i]['actually_paid'] = $actuallyPaid;

            $orderAmountAll = bcadd($orderAmountAll, bcmul($orderAmount, $shopCart[$i]['number'], 3), 3);
            $actuallyPaidAll = bcadd($actuallyPaidAll, bcmul($actuallyPaid, $shopCart[$i]['number'], 3), 3);
        }
        $orderAmountAll = floatval($orderAmountAll);
        $actuallyPaidAll = floatval($actuallyPaidAll);

        // $actuallyPaidAll = $this->couponPrice($actuallyPaidAll,$coupon_id);
        if(empty($coupon_id)){ // 如果用户下单时没有使用优惠券或优惠券折后价不如产品原本的折后价便宜（前端会处理这一逻辑），那么，$coupon_id的值是空。
            $actuallyPaidAll = $actuallyPaidAll; // 就使用没有优惠券的价格（可能是产品原价，也可能是产品自带的折后价，反正不是使用优惠券后的价格）
        }else{                 // 如果用户下单时有使用优惠券
            $actuallyPaidAll = $this->couponPrice($orderAmountAll,$coupon_id); // 就按照使用优惠券的价格，而不是按照产品自带的折后价
        }

        // 插入订单表 order
        $order = new OrderModel();
        $order->created_at = $timestamp;
        $order->updated_at = $timestamp;
        $order->order_number = date('YmdHis', $timestamp).mt_rand(10, 99);
        $order->user_id = $userId;
        $order->is_pay = OrderModel::PAY_UNPAID;
        $order->pay_type = $payType;
        $order->order_amount = $orderAmountAll;
        $order->actually_paid = round($actuallyPaidAll, 2);
        $order->username = $user->username;
        $order->email = $user->email;
        $order->phone = $user->phone;
        $order->company = $user->company;
        $order->province_id = $user->province_id;
        $order->city_id = $user->city_id;
        $order->address = $address;
        $order->coupon_id = $coupon_id ? intval($coupon_id) : 0;

        $order->position = !empty($user->position)?$user->position:$position = (new Ip2Location())->getLocation(Yii::$app->request->userIP)->area;
        $order->is_mobile_pay = $this->isMobileClient()==true ? 1 : 0; // 是否为移动端支付：0代表否，1代表是。
        $order->remarks = $remarks;
        if (!$order->save()) {
            DB::rollBack();
            // $this->errno = ApiCode::INSERT_FAIL;
            $this->errno = '';
            return null;
        }

        // 插入订单商品表 order_goods
        $key = [
            'order_id',
            'goods_id',
            'goods_number',
            'goods_original_price',
            'goods_present_price',
            'price_edition',
            'created_at',
            'updated_at',
        ];
        $row = [];
        $shopIdArr = [];
        foreach ($shopCart as $item) {
            $row[] = [
                $order->id,
                $item['goods_id'],
                $item['number'],
                $item['order_amount'],
                $item['actually_paid'],
                $item['price_edition'],
                $timestamp,
                $timestamp,
            ];
            $shopIdArr[] = $item['shop_cart_id'];
        }
        $batchInsert = Yii::$app->db->createCommand()->batchInsert(OrderGoods::tableName(), $key, $row)->execute();
        if ($lenShopCart != $batchInsert) {
            DB::rollBack();
            $this->errno = ApiCode::INSERT_FAIL;
            return null;
        }

        // 删除购物车对应的商品
        if ($lenShopCart != ShopCart::deleteAll(['id' => $shopIdArr])) {
            DB::rollBack();
            $this->errno = ApiCode::DELETE_FAIL;
            return null;
        }

        OrderModel::sendOrderEmail($order, $user);
        DB::commit();

        return $order;
    }


    public function createByCartWithoutLogin($shopcarArr, $payType, $coupon_id, $address, $remarks)
    {
        $user = $this->user;
        $userId = $user->id;

        DB::beginTransaction();
        $timestamp = time();

        $lenShopCart = count($shopcarArr);

        $goodsIdArr = array_column($shopcarArr, 'goods_id');
        $goods = Products::find()->select([
            'id AS goods_id',
            'price',
            'discount_time_begin',
            'discount_time_end',
            'discount_type',
            'discount_amount',
            ])->where(['id' => $goodsIdArr])->asArray()->all();
        if (count($goods) < 1) {
            $this->errno = ApiCode::INVALID_PARAM;
            return null;
        }
        foreach ($shopcarArr as $key => $item) {
            foreach ($goods as $good) {
                if ($item['goods_id'] == $good['goods_id']) {
                    $shopcarArr[$key] = array_merge($shopcarArr[$key], $good);
                }
            }
        }

        $orderAmountAll = 0;
        $actuallyPaidAll = 0;
        for ($i = 0; $i < $lenShopCart; $i++) {
            $orderAmount = Product::getPrice($shopcarArr[$i]['price_edition'], $shopcarArr[$i]);
            $actuallyPaid = Product::getPriceBy($orderAmount, $shopcarArr[$i], $timestamp, $coupon_id); // 新增一个参数：$coupon_id(优惠券id)

            $shopcarArr[$i]['order_amount'] = $orderAmount;
            $shopcarArr[$i]['actually_paid'] = $actuallyPaid;

            $orderAmountAll = bcadd($orderAmountAll, bcmul($orderAmount, $shopcarArr[$i]['number'], 3), 3);
            $actuallyPaidAll = bcadd($actuallyPaidAll, bcmul($actuallyPaid, $shopcarArr[$i]['number'], 3), 3);
        }
        $orderAmountAll = floatval($orderAmountAll);
        $actuallyPaidAll = floatval($actuallyPaidAll);
        // $actuallyPaidAll = $this->couponPrice($actuallyPaidAll,$coupon_id);
        if(empty($coupon_id)){ // 如果用户下单时没有使用优惠券或优惠券折后价不如产品原本的折后价便宜（前端会处理这一逻辑），那么，$coupon_id的值是空。
            $actuallyPaidAll = $actuallyPaidAll; // 就使用没有优惠券的价格（可能是产品原价，也可能是产品自带的折后价，反正不是使用优惠券后的价格）
        }else{                 // 如果用户下单时有使用优惠券
            $actuallyPaidAll = $this->couponPrice($orderAmountAll,$coupon_id); // 就按照使用优惠券的价格，而不是按照产品自带的折后价
        }

        // 插入订单表 order
        $order = new OrderModel();
        $order->created_at = $timestamp;
        $order->updated_at = $timestamp;
        $order->order_number = date('YmdHis', $timestamp).mt_rand(10, 99);
        $order->user_id = $userId;
        $order->is_pay = OrderModel::PAY_UNPAID;
        $order->pay_type = $payType;
        $order->order_amount = $orderAmountAll; // 原价
        $order->actually_paid = round($actuallyPaidAll ,2); // 折后
        $order->username = $user->username;
        $order->email = $user->email;
        $order->phone = $user->phone;
        $order->company = $user->company;
        $order->province_id = $user->province_id;
        $order->city_id = $user->city_id;
        $order->address = $address;
        $order->coupon_id = $coupon_id ? intval($coupon_id) : 0;

        $order->position = !empty($user->position)?$user->position:$position = (new Ip2Location())->getLocation(Yii::$app->request->userIP)->area;
        $order->is_mobile_pay = $this->isMobileClient()==true ? 1 : 0; // 是否为移动端支付：0代表否，1代表是。
        $order->remarks = $remarks;
        if (!$order->save(false)) {
            DB::rollBack();
            $this->errno = ApiCode::INSERT_FAIL;
            return null;
        }

        // 插入订单商品表 order_goods
        $key = [
            'order_id',
            'goods_id',
            'goods_number',
            'goods_original_price',
            'goods_present_price',
            'price_edition',
            'created_at',
            'updated_at',
        ];
        $row = [];
        $shopIdArr = [];
        foreach ($shopcarArr as $item) {
            $row[] = [
                $order->id,
                $item['goods_id'],
                $item['number'],
                $item['order_amount'],
                $item['actually_paid'],
                $item['price_edition'],
                $timestamp,
                $timestamp,
            ];
        }
        $batchInsert = Yii::$app->db->createCommand()->batchInsert(OrderGoods::tableName(), $key, $row)->execute();
        if ($lenShopCart != $batchInsert) {
            DB::rollBack();
            $this->errno = ApiCode::INSERT_FAIL;
            return null;
        }

        OrderModel::sendOrderEmail($order, $user);
        DB::commit();

        return $order;
    }
    
    /**
     * 是否移动端访问
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
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp',
                'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu',
                'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi',
                'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile','alipay'
            ];
            
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(".implode('|', $clientkeywords).")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                    return true;
            }
        }
        
        //协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                    return true;
            }
        }
        
        return false;
    }
}
