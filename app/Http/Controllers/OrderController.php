<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pay\Pay;
use App\Http\Controllers\Pay\PayFactory;
use App\Http\Controllers\Pay\Wechatpay;
use App\Http\Requests\InvoicesRequest;
use App\Http\Requests\OrderRequest;
use App\Models\City;
use App\Models\Common;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\OrderStatus;
use App\Models\OrderTrans;
use App\Models\Pay as ModelsPay;
use App\Models\PriceEditionValues;
use App\Models\Products;
use App\Models\User;
use App\Models\WechatTool;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller {
    /**
     * 下单时输入优惠券码，若券码正确，总价打折或减去金额（用户在下单时，如果没有输入优惠券码，前台就不会调用本接口）
     * 同时要往用户（未登录根据email获取用户，已登录根据token获取用户）的账户新增一张优惠券
     */
    public function Coupon(Request $request) {
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
        $coupon = Coupon::where('code', $code)
                        ->where('status', 1)
                        ->where('time_begin', '<=', $now)
                        ->where('time_end', '>=', $now)
                        ->first();
        if (empty($coupon)) {                                              // 如果查询不到该优惠券或券码错误或已过期,
            ReturnJson(false, '券码错误或已过期');        // 就提示 券码错误或已过期.
        } else {
            // 如果用户付款前输入的优惠券码正确，即“用户-优惠券对应关系表”coupon_user里有这条数据
            // 还要根据用户输入的email和优惠券码coupon_code到“用户-优惠券对应关系表”coupon_user里判断这条数据的is_used的值（是否已使用：1否，2是。这里不要用0）是不是2，
            // 如果值是2，代表这个用户输入的券码已被他使用过了，要提示不能再使用了。
            $userId = User::where('email', $email)->value('id');
            $ucouponId = Coupon::where('code', $code)->value('id');
            $is_used = CouponUser::where('user_id', $userId)->where('coupon_id', $ucouponId)->value('is_used');
            if ($is_used == CouponUser::isUsedYes) {
                ReturnJson(true, '这张优惠券已被你使用过了');  // 提示'这张优惠券已被你使用过了',
            }
            $data = [
                'id'        => $coupon->id,
                'type'      => $coupon->type,
                'value'     => $coupon->value,
                'email'     => $email,
                'day_begin' => date('Y.m.d', $coupon->time_begin),
                'day_end'   => date('Y.m.d', $coupon->time_end),
            ];
            $model = new User();
            $modelCouponUser = new CouponUser();
            $user = User::IsLogin();
            if ($user == null) { // 如果获取不到头部token，说明此时用户没有登录，并不能说明用户有没有注册账户
                // 根据用户提交的邮箱地址到user表查询是否存在一条数据
                $user = User::where('email', $email)->first();
                if ($user) { // 如果user表存在这个用户的数据，说明用户有注册账户
                    $user->status = 10; // 就把这个用户的邮箱验证状态改为“已验证通过（10）”，其实这样做有点不够安全
                    $user->password = Hash::make('123456'); // 帮用户把他的密码改为初始密码123456，我不知道这样合不合理，但是生涛要求这样
                    $user->save();
                    $this->getCouponUser($coupon, $user, $modelCouponUser);
                } else {
                    // 无用户，就新增用户
                    /**
                     * @var User $user
                     */
                    $user = $this->addUser(
                        $username, $phone, $email, $company, $model, $province_id, $area_id
                    );
                    if (!empty($user->id)) {
                        $this->getCouponUser($coupon, $user, $modelCouponUser);
                    }
                }
            } else { // 如果获取到头部token，说明此时用户已经登录
                $this->getCouponUser($coupon, $user, $modelCouponUser);
            }
            ReturnJson(true, '', $data);
        }
    }

    /**
     * 下单付款
     */
    public function CreateAndPay(Request $request) {
        try {
            $coupon_id = $request->coupon_id ?? ''; // 优惠券id：无论是用户输入优惠券码，还是用户选择某一种优惠券，都接收coupon_id
            $remarks = $request->remarks; // 订单备注
            $address = $request->address;
            //校验请求参数
            (new OrderRequest())->createandpay($request);
            //校验支付方式
            $payType = $request->pay_type;
            if (!array_key_exists($payType, Order::payType())) {
                ReturnJson(false, '支付方式错误');
            }
            //获取用户, 没有登录，则自动注册
            $user = $this->getUser($request);
            if (!empty($request->goods_id)) { // 直接下单
                $priceEdition = $request->price_edition ?? 0;
                $isExist = PriceEditionValues::query()->where("id", $priceEdition)->count();
                if ($isExist <= 0) {
                    ReturnJson(false, '价格版本错误');
                }
                $orderTrans = new OrderTrans();
                $order = $orderTrans->setUser($user)->createBySingle(
                    $request->goods_id, $priceEdition, $payType, $coupon_id, $address, $remarks
                );
            } elseif (!empty($request->shop_id)) { // 已登录，通过购物车下单
                $shopIdArr = $request->shop_id;
                $shopIdArr = explode(',', $shopIdArr);
                if (!is_array($shopIdArr) || count($shopIdArr) < 1) {
                    ReturnJson(false, '参数错误1');
                }
                $orderTrans = new OrderTrans();
                $order = $orderTrans->setUser($user)->createByCart(
                    $shopIdArr, $payType, $coupon_id, $address, $remarks
                );
            } elseif (!empty($request->shopcar_json)) { // 未登录，通过购物车下单
                $shopcarJson = $request->shopcar_json;
                $shopcarArr = json_decode($shopcarJson, true);
                $orderTrans = new OrderTrans();
                $order = $orderTrans->setUser($user)->createByCartWithoutLogin(
                    $shopcarArr, $payType, $coupon_id, $address, $remarks
                );
            } else {
                ReturnJson(false, '参数错误3');
            }
            if ($order === null) {
                return $this->echoMsg($orderTrans->getErrno());
            }
            // 把临时订单号加入缓存
            Cache::store('file')->put('$tempOrderId', [$order->id, $order->order_number], 600); // 十分钟过期
            //拉起支付
            $pay = PayFactory::create($order->pay_type);
            $isMobile = $request->is_mobile;
            $isWechat = $request->is_wechat;
            $isMobile = $isMobile == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
            $pay->setOption(Pay::KEY_IS_MOBILE, $isMobile);
            $isWechat = $isWechat == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
            $pay->setOption(Pay::KEY_IS_WECHAT, $isWechat);

            return $pay->do($order);
        } catch (\Exception $e) {
            $errData = [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data'  => $request->all(),
            ];
            \Log::error('下单支付失败,错误信息:'.json_encode($errData));
            ReturnJson(false, '未知错误');
        }
    }

    /**
     * 再次支付
     */
    public function Pay(Request $request) {
        $orderId = $request->order_id;
        $order = Order::where(['id' => $orderId])->first();
        if (!$order) {
            ReturnJson(false, '订单不存在');
        }
        if ($order->is_pay == Order::PAY_SUCCESS) {
            $msg = '该订单已支付';
            $url = rtrim(env('APP_URL'), '/').'/paymentComplete/'.$order->id;

            return '<script>window.document.write("<h1>'.$msg.'</h1>");alert("'.$msg.'");window.location="'.$url
                   .'";</script>';
        }
        // 把临时订单号加入缓存
        Cache::store('file')->put('$tempOrderId', [$order->id, $order->order_number], 600); // 十分钟过期
        $pay = PayFactory::create($order->pay_type);
        $isMobile = $order->isMobile == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
        $pay->setOption(Pay::KEY_IS_MOBILE, $isMobile);
        $isWechat = $order->isWechat == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
        $pay->setOption(Pay::KEY_IS_WECHAT, $isWechat);

        return $pay->do($order);
    }

    /**
     * 支付方式
     */
    public function Payment() {
        $data = ModelsPay::select(['id', 'name', 'image as img', 'content as notice'])
                         ->where('status', 1)
                         ->orderBy('sort', 'asc')
                         ->get()
                         ->toArray();
        foreach ($data as &$item) {
            $item['img'] = Common::cutoffSiteUploadPathPrefix($item['img']);
        }
        ReturnJson(true, 'success', $data);
    }

    public function WechatOrder() {
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
        $returnUrl = rtrim(env('APP_URL'), '/').'/paymentComplete/'.$order->id;
        try {
            $wechatTool = new WechatTool();
            $wechatpay = new Wechatpay();
            $openid = $wechatTool->getOpenid($code);
            $prepayid = $wechatpay->getPrepayid($order, $openid);
            $timestamp = time();
            $nonce = substr(str_shuffle('0123456789abcdefghijklnmopqrstuvwxyz'), mt_rand(0, 36 - 33), 32);
            $sign = $wechatpay->getJssdkSign($timestamp, $nonce, $prepayid);
            $prepayid = 'prepay_id='.$prepayid;
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
            $msg = $e->getMessage()."\n";
            if ($e->hasResponse()) {
                $msg .= $e->getResponse()->getStatusCode().' '.$e->getResponse()->getReasonPhrase()."\n";
                $msg .= $e->getResponse()->getBody();
            }

            return $msg;
        }
    }

    /**
     * 订单明细
     */
    public function Details(Request $request) {
        $orderId = $request->order_id;
        $field = [
            'order_amount', 'actually_paid',
            'is_pay', 'pay_type',
            'order_number', 'pay_time',
            'user_id', 'province_id',
            'city_id', 'address', 'remarks',
        ];
        $order = Order::select($field)
                      ->where(['id' => $orderId])
                      ->first();
        if (!$order) {
            ReturnJson(false, '订单不存在');
        }
        $orderStatus = $order['is_pay'] ?? '';
        $orderNumber = $order['order_number'] ?? '';
        $payTime = !empty($order['pay_time']) ? date('Y-m-d H:i:s', $order['pay_time']) : '';
        if ($orderStatus == Order::PAY_UNPAID) {
            // 主动查询订单状态
            // 未付款
            ReturnJson(true, '', ['is_pay' => $orderStatus, 'order_number' => $orderNumber]);
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
                                ->leftJoin('product_routine as product', 'product.id', 'order_goods.goods_id')
                                ->leftJoin('price_edition_values as edition', 'edition.id', 'order_goods.price_edition')
                                ->leftJoin('languages as language', 'language.id', 'edition.language_id')
                                ->where(['order_goods.order_id' => $orderId, 'product.status' => 1])
                                ->get()->toArray();
        if (!empty($order['user_id'])) {
            $user = User::select(['username', 'email', 'phone', 'company', 'province_id', 'city_id', 'address'])->where(
                'id', $order['user_id']
            )->first();
            $province = City::where('id', $order['province_id'])->value('name');
            $city = City::where('id', $order['city_id'])->value('name');
            $address = $province.$city.$order['address'];
            $_user = [
                'name'    => $user['username'] ?? '',
                'email'   => $user['email'] ?? '',
                'phone'   => $user['phone'] ?? '',
                'company' => $user['company'] ?? '',
                'address' => $address,
            ];
        }
        $discount_value = bcsub($order['order_amount'], $order['actually_paid'], 2);
        $data = [
            'order'  => [
                'order_amount'   => $order['order_amount'], // 订单总额
                'discount_value' => $discount_value, // 优惠金额
                'actually_paid'  => $order['actually_paid'], // 实付金额
                'order_status'   => OrderStatus::where('id', $order['is_pay'])->value('name'),
                'pay_type'       => ModelsPay::where('id', $order['pay_type'])->value('name'),
                'order_number'   => $orderNumber,
                'pay_time'       => $payTime,
                'remarks'        => $order['remarks'] ? $order['remarks'] : '',
            ],
            'goods'  => $orderGoods,
            'user'   => $_user,           // 用户的初始账户信息
            'is_pay' => $orderStatus,
        ];
        ReturnJson(true, '', $data);
    }

    public function list(Request $request) {
        try {
            $status = $request->input('status', '0');
            if (empty($status) || !isset($status) || !in_array($status, array_keys(Order::PAY_STATUS_TYPE))) {
                $status = 0;
            }
            $userId = $request->user->id;
            $model = new Order();
            $model = $model->where('user_id', $userId)
                           ->where('is_delete', 0) //是否被前台用户删除:0代表否,1代表是
                           ->when($status, function ($query) use ($status) {
                    $query->where('is_pay', $status);
                })
                           ->orderBy('id', 'desc');
            $count = $model->count();
            // 查询偏移量
            if (!empty($request->pageNum) && !empty($request->pageSize)) {
                $model->offset(($request->pageNum - 1) * $request->pageSize);
            }
            // 查询条数
            if (!empty($request->pageSize)) {
                $model->limit($request->pageSize);
            }
            $fields = ['id', 'created_at', 'order_number', 'order_amount', 'is_pay'];
            $model->select($fields);
            $rs = $model->get();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['data'] = $rs;
            if ($rs) {
                ReturnJson(true, '获取成功', $rdata);
            } else {
                ReturnJson(false, '获取失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function form(Request $request) {
        try {
            $model = new Order();
            $record = $model->findOrFail($request->id);
            $userId = $request->user->id;
            if ($record->user_id != $userId) {
                ReturnJson(false, '非法操作');
            }
            $addressInfo = $record->addressInfo;
            $payTypeText = $record->PayTypeText ?? '';
            $is_invoice = $record->is_invoice;
            $createDate = $record->create_date;
            $orderInfo = $record->toArray();
            $orderInfo = Arr::only($orderInfo, ['id', 'order_number', 'created_at', 'is_pay_text', 'is_pay', 'pay_type',
                                                'order_amount', 'actually_paid']
            );
            $orderInfo['pay_type_text'] = $payTypeText;
            $orderInfo['create_date'] = $createDate;
            $orderInfo['is_invoice'] = $is_invoice;
            $orderGoodsMolde = new OrderGoods();
            $ogArrList = [];
            $ogList = $orderGoodsMolde->where("order_id", $orderInfo['id'])->get();
            foreach ($ogList as $key => $value) {
                /**
                 * @var $value OrderGoods
                 */
                $value['product_info'] = $value->product_info;
                $value['price_edition_info'] = $value->price_edition_info;
                $orderGoodsArr = $value->toArray();
                $ogArrList[] = $orderGoodsArr;
            }
            $orderInfo['order_goods'] = $ogArrList;
            $orderInfo['order_addr'] = $addressInfo;
            ReturnJson(true, '获取成功', $orderInfo);
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function delete(Request $request) {
        try {
            $model = new Order();
            $record = $model->findOrFail($request->id);
            $userId = $request->user->id;
            if ($record->user_id != $userId) {
                ReturnJson(false, '非法操作');
            }
            if (in_array($record->is_pay, [Order::PAY_SUCCESS, Order::PAY_FINISH])) {
                ReturnJson(false, '订单已支付，不能删除');
            }
            $record->is_delete = 1; //是否被前台用户删除:0代表否,1代表是
            $record->is_pay = Order::PAY_CANCEL; //取消状态
            if ($record->save()) {
                ReturnJson(true, '删除成功');
            } else {
                ReturnJson(false, '删除失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function changePayType(Request $request) {
        try {
            $model = new Order();
            $request->validate([
                                   'order_id'    => 'required|integer',
                                   'pay_type_id' => 'required|integer',
                               ],
                               [
                                   'order_id.required'    => '订单ID不能为空',
                                   'order_id.integer'     => '订单ID必须为数字',
                                   'pay_type_id.required' => '支付方式不能为空',
                                   'pay_type_id.integer'  => '支付方式必须为数字',
                               ]
            );
            $orderId = $request->input('order_id');
            $record = $model->findOrFail($orderId);
            $userId = $request->user->id;
            if ($record->user_id != $userId) {
                ReturnJson(false, '非法操作');
            }
            if (in_array($record->is_pay, [Order::PAY_SUCCESS, Order::PAY_FINISH])) {
                ReturnJson(false, '订单已支付，不能修改');
            }
            $payTypeId = $request->input('pay_type_id');
            $isExist = ModelsPay::query()
                                ->where("id", $payTypeId)
                                ->where("status", 1)
                                ->count();
            if (empty($isExist) || $isExist <= 0) {
                ReturnJson(false, '支付方式不存在');
            }
            $record->pay_type = $payTypeId; //取消状态
            if ($record->save()) {
                ReturnJson(true, '修改成功');
            } else {
                ReturnJson(false, '修改失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function pullPay(Request $request) {
        try {
            // TODO: cuizhixiong 2024/4/30   拉起支付需限流 , 防止被刷
            $model = new Order();
            $request->validate([
                                   'order_id' => 'required|integer',
                               ],
                               [
                                   'order_id.required' => '订单ID不能为空',
                                   'order_id.integer'  => '订单ID必须为数字',
                               ]
            );
            $orderId = $request->input('order_id');
            $order = $model->findOrFail($orderId);
            $userId = $request->user->id;
            if ($order->user_id != $userId) {
                ReturnJson(false, '非法操作');
            }
            if (!in_array($order->is_pay, [Order::PAY_UNPAID])) {
                ReturnJson(false, '未支付的订单才能拉起支付');
            }
            $pay = PayFactory::create($order->pay_type);
            $isMobile = isMobile() ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
            $pay->setOption(Pay::KEY_IS_MOBILE, $isMobile);
            $isWechat = isWeixin() ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
            $pay->setOption(Pay::KEY_IS_WECHAT, $isWechat);

            return $pay->do($order);
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    /**
     * $request
     * @return User
     */
    private function getUser($request) {
        $email = $request->email;
        $username = $request->username;
        $phone = $request->phone;
        $company = $request->company;
        $province_id = $request->province_id ?? 0;
        $address = $request->address ?? '';
        $city_id = $request->city_id ?? 0;
        $user = new User();
        $exist = User::where('email', $email)->first();
        if (!$exist) {
            // 新需求：下单付款成功后自动给用户注册一个账户（实际情况是：还没付款） 开始
            // $user->id = 0;
            $user->username = $username;
            $user->email = $email;
            $user->phone = $phone;
            $user->company = $company;
            $user->province_id = $province_id;
            $user->area_id = $city_id;
            $user->address = $address;
            $user->password = Hash::make('123456'); // 帮用户自动生成一个初始密码123456
            $user->created_at = time();
            $user->created_by = 0;
            $user->status = 10; // 就把这个用户的邮箱验证状态改为“已验证通过（10）”，其实这样做有点不够安全
            $user->save();
            // 新需求：下单付款成功后自动给用户注册一个账户 结束
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

        return $user;
    }

    /**
     *
     * @param mixed $username
     * @param mixed $phone
     * @param mixed $email
     * @param mixed $company
     * @param User  $model
     * @param mixed $province_id
     * @param mixed $area_id
     *
     */
    public function addUser(
        mixed $username, mixed $phone, mixed $email, mixed $company, User $model, mixed $province_id, mixed $area_id
    ) {
        // 如果user表不存在这个用户的数据，说明用户没有注册账户
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
        $model->password = Hash::make('123456'); // 帮用户自动生成一个初始密码123456
        $model->created_at = time();
        $model->created_by = 0;
        $model->status = 10; // 就把这个用户的邮箱验证状态改为“已验证通过（10）”，其实这样做有点不够安全

        return $model->save();
    }

    /**
     *
     * @param Coupon     $coupon
     * @param User       $user
     * @param CouponUser $modelCouponUser
     *
     * @return mixed
     */
    private function getCouponUser($coupon, $user, CouponUser $modelCouponUser) {
        $user_ids = explode(',', $coupon->user_ids); // 把用户输入券码的这张优惠券的user_ids值转为数组
        if (!in_array($user->id, $user_ids)) {
            array_push($user_ids, $user->id);
            $coupon->user_ids = implode(',', $user_ids);
            if ($coupon->save()) {            // 修改coupon表对应的数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会修改数据。
                $modelCouponUser->user_id = $user->id;
                $modelCouponUser->coupon_id = $coupon->id;
                $modelCouponUser->is_used = CouponUser::isUsedNO;
                $modelCouponUser->save();  // 给coupon_user表新增一条数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会新增一条数据。
            }
        }
    }
}
