<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pay\Pay;
use App\Http\Controllers\Pay\PayFactory;
use App\Http\Controllers\Pay\Wechatpay;
use App\Models\City;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\OrderStatus;
use App\Models\OrderTrans;
use App\Models\Pay as ModelsPay;
use App\Models\PriceEditionValues;
use App\Models\User;
use App\Models\WechatTool;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    /**
     * 下单时输入优惠券码，若券码正确，总价打折或减去金额（用户在下单时，如果没有输入优惠券码，前台就不会调用本接口）
     * 同时要往用户（未登录根据email获取用户，已登录根据token获取用户）的账户新增一张优惠券
     */
    public function Coupon(Request $request)
    {
        $username = $request->username;
        $province_id = $request->province_id ?? 0;
        $area_id = $request->city_id ?? 0;
        $phone = $request->phone;
        $email = $request->email;
        $email = $request->city_id;
        $company = $request->company;

        $code = trim($request->code);
        if (empty($code)) {
            ReturnJson(false, '优惠券码不能为空');
        }

        $now = time();
        $coupon = Coupon::where('code',$code)
            ->where('status',1)
            ->where('time_begin','<=',$now)
            ->where('time_end','>=',$now)
            ->first();
        if (empty($coupon)) {                                              // 如果查询不到该优惠券或券码错误或已过期,
            ReturnJson(false, '券码错误或已过期');        // 就提示 券码错误或已过期.
        } else {
            // 如果用户付款前输入的优惠券码正确，即“用户-优惠券对应关系表”coupon_user里有这条数据
            // 还要根据用户输入的email和优惠券码coupon_code到“用户-优惠券对应关系表”coupon_user里判断这条数据的is_used的值（是否已使用：1否，2是。这里不要用0）是不是2，
            // 如果值是2，代表这个用户输入的券码已被他使用过了，要提示不能再使用了。
            $userId = User::where('email',$email)->value('id');
            $ucouponId = Coupon::where('code',$code)->value('id');
            $is_used = CouponUser::select('is_used')->where('user_id',$userId)->where('coupon_id',$ucouponId)->value('is_used');
            if ($is_used==2) {
                ReturnJson(true,'这张优惠券已被你使用过了');  // 提示'这张优惠券已被你使用过了',
            }
            $coupon->save();
            $data = [
                'id' => $coupon->id,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'email' => $email,
                'day_begin' => date('Y.m.d',$coupon->time_begin),
                'day_end' => date('Y.m.d',$coupon->time_end),
            ];

            $model = new User();
            $modelCouponUser = new CouponUser();
            $user = User::IsLogin();
            if ($user == null) { // 如果获取不到头部token，说明此时用户没有登录，并不能说明用户有没有注册账户
                // 根据用户提交的邮箱地址到user表查询是否存在一条数据
                $user = User::where('email',$email)->first();
                if ($user) { // 如果user表存在这个用户的数据，说明用户有注册账户
                    $user->status = 10; // 就把这个用户的邮箱验证状态改为“已验证通过（10）”，其实这样做有点不够安全
                    $user->password = md5('123456'); // 帮用户把他的密码改为初始密码123456，我不知道这样合不合理，但是生涛要求这样
                    $user->save();

                    $user_ids = explode(',', $coupon->user_ids); // 把用户输入券码的这张优惠券的user_ids值转为数组
                    if (in_array($user->id, $user_ids) != true) {
                        array_push($user_ids, $user->id);
                        $coupon->user_ids = implode(',', $user_ids);
                        if ($coupon->save()) {            // 修改coupon表对应的数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会修改数据。
                            $modelCouponUser->user_id = $user->id;
                            $modelCouponUser->coupon_id = $coupon->id;
                            $modelCouponUser->save();  // 给coupon_user表新增一条数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会新增一条数据。
                        }
                    }
                } else {      // 如果user表不存在这个用户的数据，说明用户没有注册账户
                    // 就帮用户自动生成一个账号
                    if ($username == '') {
                        ReturnJson(false, '姓名不能为空');
                    }
                    if (trim($phone) == '') {
                        ReturnJson(false, '电话不能为空');
                    }
                    if ($email == '') {
                        ReturnJson(false, '邮箱地址不能为空');
                    }
                    if ($company == '') {
                        ReturnJson(false, '公司不能为空');
                    }
                    $model->username = $username;
                    $model->province_id = $province_id;
                    $model->area_id = $area_id;
                    $model->phone = $phone;
                    $model->email = $email;
                    $model->company = $company;
                    $model->password = md5('123456'); // 帮用户自动生成一个初始密码123456
                    $model->created_at = time();
                    $model->created_by = 0;
                    $model->status = 10; // 就把这个用户的邮箱验证状态改为“已验证通过（10）”，其实这样做有点不够安全
                    if ($model->save()) {
                        $user_ids = explode(',', $coupon->user_ids); // 把用户输入券码的这张优惠券的user_ids值转为数组
                        if (in_array($model->id, $user_ids) != true) {
                            array_push($user_ids, $model->id);
                            $coupon->user_ids = implode(',', $user_ids);
                            if ($coupon->save()) {           // 修改coupon表对应是数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会修改数据。
                                $modelCouponUser = new CouponUser();
                                $modelCouponUser->user_id = $model->id;
                                $modelCouponUser->coupon_id = $coupon->id;
                                $modelCouponUser->created_by = 0;
                                $modelCouponUser->save();  // 给coupon_user表新增一条数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会新增一条数据。
                            }
                        }
                    }
                }
            } else { // 如果获取到头部token，说明此时用户已经登录
                $user_ids = explode(',', $coupon->user_ids); // 把用户输入券码的这张优惠券的user_ids值转为数组
                if (in_array($user->id, $user_ids) != true) {
                    array_push($user_ids, $user->id);
                    $coupon->user_ids = implode(',', $user_ids);
                    if ($coupon->save()) {            // 修改coupon表对应的数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会修改数据。
                        $modelCouponUser->user_id = $user->id;
                        $modelCouponUser->coupon_id = $coupon->id;
                        $modelCouponUser->created_by = $user->id;
                        $modelCouponUser->created_by = 0;
                        $modelCouponUser->save();  // 给coupon_user表新增一条数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会新增一条数据。
                    }
                }
            }
            ReturnJson(true,'',$data);
        }
    }

    /**
     * 下单付款
     */
    public function CreateAndPay(Request $request)
    {
        $goodsId = $request->goods_id;
        $shopIdArr = $request->shop_id;
        $shopcarJson = $request->shopcar_json;
        $isMobile = $request->is_mobile;
        $isWechat = $request->is_wechat;
        $coupon_id = $request->coupon_id ?? ''; // 优惠券id：无论是用户输入优惠券码，还是用户选择某一种优惠券，都接收coupon_id
        $username = $request->username;
        $email = $request->email;
        $phone = $request->phone;
        $company = $request->company;
        $province_id = $request->province_id ?? 0;
        $address = $request->address ?? '';
        $city_id = $request->city_id ?? 0;
        $remarks = $request->remarks; // 订单备注

        if (
            empty($username) || empty($email) || empty($phone) || empty($company)
        ) {
            ReturnJson(false ,'参数不正确');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ReturnJson(false ,'邮箱格式不正确');
        }
        if ($company == '') {
            ReturnJson(false ,'公司名称不能为空');
        }
        if (trim($phone) == '') {
            ReturnJson(false ,'手机号不能为空');
        }
        // 新需求：下单付款成功后自动给用户注册一个账户（实际情况是：还没付款） 开始
        $user = new User();
        $exist = User::where('email',$email)->first();
        if (!$exist) {
            // $user->id = 0;
            $user->username = $username;
            $user->email = $email;
            $user->phone = $phone;
            $user->company = $company;
            $user->province_id = $province_id;
            $user->area_id = $city_id;
            $user->address = $address;

            $user->password = md5('123456'); // 帮用户自动生成一个初始密码123456
            $user->created_at = time();
            $user->created_by = 0;
            $user->status = 10; // 就把这个用户的邮箱验证状态改为“已验证通过（10）”，其实这样做有点不够安全
            $user->save();
        } else {
            $user->id = $exist->id;
            $user->username = $username;
            $user->email = $email;
            $user->phone = $phone;
            $user->company = $company;
            $user->province_id = $province_id;
            $user->city_id = $city_id;
            $user->address = $address;
        }
        // 新需求：下单付款成功后自动给用户注册一个账户 结束

        $payType = $request->pay_type;
        $tempOrderId = $request->temp_order_id; // 临时订单号
        if (!empty($goodsId)) { // 直接下单
            $priceEdition = $request->price_edition;
            if (!array_key_exists($payType, Order::payType())) {
                ReturnJson(false, '支付方式错误');
            }
            if (!in_array($priceEdition, PriceEditionValues::GetPriceEditonsIds())) {
                ReturnJson(false, '价格版本错误');
            }

            $orderTrans = new OrderTrans();
            $order = $orderTrans->setUser($user)->createBySingle($goodsId, $priceEdition, $payType, $coupon_id, $address, $remarks);
        } elseif (!empty($shopIdArr)) { // 已登录，通过购物车下单
            $shopIdArr = explode(',', $shopIdArr);
            if (!array_key_exists($payType, Order::payType())) {
                ReturnJson(false, '支付方式错误');
            }
            if (!is_array($shopIdArr) || count($shopIdArr) < 1) {
                ReturnJson(false, '参数错误1');
            }

            foreach ($shopIdArr as $item) {
                if (!preg_match("/^[1-9][0-9]*$/", $item)) {
                    ReturnJson(false, '参数错误2');
                }
            }

            $orderTrans = new OrderTrans();
            $order = $orderTrans->setUser($user)->createByCart($shopIdArr, $payType, $coupon_id, $address, $remarks);
        } elseif (!empty($shopcarJson)) { // 未登录，通过购物车下单
            $shopcarArr = json_decode($shopcarJson, true);
            $orderTrans = new OrderTrans();
            $order = $orderTrans->setUser($user)->createByCartWithoutLogin($shopcarArr, $payType, $coupon_id, $address, $remarks);
        } else {
            ReturnJson(false, '参数错误3');
        }

        if ($order === null) {
            return $this->echoMsg($orderTrans->getErrno());
        }

        // 把临时订单号加入缓存
        Cache::store('file')->put('$tempOrderId',[$order->id, $order->order_number], 600); // 十分钟过期
        $pay = PayFactory::create($order->pay_type);
        $isMobile = $isMobile == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
        $pay->setOption(Pay::KEY_IS_MOBILE, $isMobile);
        $isWechat = $isWechat == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
        $pay->setOption(Pay::KEY_IS_WECHAT, $isWechat);

        return $pay->do($order);
    }

    /**
     * 支付方式
     */
    public function Payment()
    {
        $data = ModelsPay::select([
            'id',
            'name',
            'image as img',
            'content as notice'
        ])
        ->where('status',1)
        ->orderBy('sort','asc')
        ->get()
        ->toArray();
        ReturnJson(true, 'success', $data);
    }

    public function WechatOrder()
    {
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $referer = $_GET['referer'] ?? env('APP_URL');
        if (empty($code) || empty($state)) {
            throw new Exception('invalid param');
        }
        $orderId = $state;
        $order = Order::find($orderId);
        if (empty($order)) {
            throw new Exception('order_id not found');
        }
        $returnUrl = env('APP_URL') . '/paymentComplete/' . $order->id;
        try {
            $wechatTool = new WechatTool();
            $wechatpay = new Wechatpay();

            $openid = $wechatTool->getOpenid($code);
            $prepayid = $wechatpay->getPrepayid($order, $openid);
            $timestamp = time();
            $nonce = substr(str_shuffle('0123456789abcdefghijklnmopqrstuvwxyz'), mt_rand(0, 36 - 33), 32);
            $sign = $wechatpay->getJssdkSign($timestamp, $nonce, $prepayid);
            $prepayid = 'prepay_id=' . $prepayid;
            $appid = $wechatTool::$APPID;

            $html = <<<EOF
            <script>
            function onBridgeReady() {
                WeixinJSBridge.invoke('getBrandWCPayRequest', {
                    "appId": "$appid",
                    //公众号名称，由商户传入
                    "timeStamp": "$timestamp",
                    //时间戳，自1970年以来的秒数
                    "nonceStr": "$nonce",
                    //随机串
                    "package": "$prepayid",
                    "signType": "RSA",
                    //微信签名方式：
                    "paySign": "$sign" //微信签名
                },
                function(res) {
                    if (res.err_msg == "get_brand_wcpay_request:ok") {
                        // 使用以上方式判断前端返回,微信团队郑重提示：
                        // res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
                        window.location='$returnUrl';
                    } else if (res.err_msg == "get_brand_wcpay_request:cancel" || res.err_msg == "get_brand_wcpay_request:fail") {
                        alert('支付取消');
                        window.location='$referer';
                    } else if (res.err_msg == "system:function_not_implement") {
                        window.document.write("<h1>请使用手机微信打开</h1>");
                        alert('请使用手机微信打开');
                    } else {
                        alert('支付取消');
                        window.location='$referer';
                    }
                });
            }
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
                } else if (document.attachEvent) {
                    document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
                    document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
                }
            } else {
                onBridgeReady();
            }
            </script>
            EOF;

            return $html;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // 进行错误处理
            $msg = $e->getMessage() . "\n";
            if ($e->hasResponse()) {
                $msg .= $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase() . "\n";
                $msg .= $e->getResponse()->getBody();
            }
            return $msg;
        }
    }

    /**
     * 订单明细
     */
    public function Details(Request $request)
    {
        $orderId = $request->order_id;
        $order = Order::select([
            'order_amount', 
            'actually_paid',
            'is_pay', 
            'pay_type', 
            'order_number', 
            // 'FROM_UNIXTIME(pay_time,"%Y-%m-%d %H:%i:%s") as pay_time',
            'user_id',
            'province_id',
            'city_id',
            'address',
            'remarks',
        ])
        ->where(['id' => $orderId])
        ->first()->toArray();
        if (!is_array($order)) {
            ReturnJson(false,'订单不存在');
        }
        $orderStatus = $order['is_pay'] ?? '';
        $orderNumber =  $order['order_number'] ?? 0;
        $payTime =  $order['pay_time'] ?? 0;

        if ($orderStatus == Order::PAY_UNPAID) {
            // 主动查询订单状态
            // 未付款
            ReturnJson(true,'',['is_pay' => $orderStatus, 'order_number' => $orderNumber]);
        }

        $orderGoods = OrderGoods::from('order_goods')->select([
            'product.name',
            'language.name as language',
            'edition.name as edition',
            'order_goods.goods_number',
            'order_goods.goods_original_price',
            'order_goods.goods_present_price',
            'order_goods.goods_id as product_id',
            'product.url',
            // 'order_goods.price_edition',
        ])
            ->leftJoin('product_routine as product', 'product.id','order_goods.goods_id')
            ->leftJoin('price_edition_values as edition', 'edition.id','order_goods.price_edition')
            ->leftJoin('languages as language', 'language.id','edition.language_id')
            ->where(['order_goods.order_id' => $orderId, 'product.status' => 1])
            ->get()->toArray();

        if(!empty($order['user_id'])){
            $user = User::select(['username', 'email', 'phone', 'company', 'province_id', 'area_id', 'address'])->where('id',$order['user_id'])->first();
            $province = City::where('id',$order['province_id'])->value('name');
            $city = City::where('id',$order['city_id'])->value('name');
            $address = $province . $city . $order['address'];
            $_user = [
                'name' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'company' => $user['company'],
                'address' => $address,
            ];
        }
        $discount_value = bcsub($order['order_amount'], $order['actually_paid'], 2);

        $data = [
            'order' => [
                'order_amount' => $order['order_amount'], // 订单总额
                'discount_value' => $discount_value, // 优惠金额
                'actually_paid' => $order['actually_paid'], // 实付金额
                'order_status' => OrderStatus::where('id',$order['is_pay'])->value('name'),
                'pay_type' => ModelsPay::where('id',$order['pay_type'])->value('name'),
                'order_number' => $orderNumber,
                'pay_time' => $payTime,
                'remarks' => $order['remarks'] ? $order['remarks'] : '',
            ],
            'goods' => $orderGoods,
            'user' => $_user,           // 用户的初始账户信息
            'is_pay' => $orderStatus,
        ];
        ReturnJson(true,'',$data);
    }
}