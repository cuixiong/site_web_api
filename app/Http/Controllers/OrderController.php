<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Order;
use App\Models\OrderTrans;
use App\Models\Page;
use App\Models\PriceEditions;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * 下单时输入优惠券码，若券码正确，总价打折或减去金额（用户在下单时，如果没有输入优惠券码，前台就不会调用本接口）
     * 同时要往用户（未登录根据email获取用户，已登录根据token获取用户）的账户新增一张优惠券
     */
    public function Coupon(Request $request)
    {
        $username = $request->username;
        $province_id = $request->province_id ?? '';
        $city_id = $request->city_id ?? '';
        $phone = $request->phone;
        $email = $request->email;
        $company = $request->company;

        $code = trim($request->coupon_code);
        if (empty($code)) {
            ReturnJson(false, '优惠券码不能为空');
        }

        $now = time();
        $coupon = Coupon::where(['code' => $code, 'status' => 1])
            ->where('time_begin','<=',$now)
            ->where('time_begin','>=',$now)
            ->fisrt();

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
            $user = User::verificationToken($request->header('token'));
            if ($user == null) { // 如果获取不到头部token，说明此时用户没有登录，并不能说明用户有没有注册账户
                // 根据用户提交的邮箱地址到user表查询是否存在一条数据
                $user = User::where('email',$email)->first();
                if ($user) { // 如果user表存在这个用户的数据，说明用户有注册账户
                    $user->status = 10; // 就把这个用户的邮箱验证状态改为“已验证通过（10）”，其实这样做有点不够安全
                    $user->password_hash = md5('123456'); // 帮用户把他的密码改为初始密码123456，我不知道这样合不合理，但是生涛要求这样
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
                    $model->city_id = $city_id;
                    $model->phone = $phone;
                    $model->email = $email;
                    $model->company = $company;
                    $model->password_hash = md5('123456'); // 帮用户自动生成一个初始密码123456
                    $model->auth_key = Yii::$app->security->generateRandomString();
                    $model->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
                    $model->created_at = time();
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
                    if ($coupon->save(false)) {            // 修改coupon表对应的数据，如果后台管理员之前已经给本用户发放这张优惠券，这里就不会修改数据。
                        $modelCouponUser->user_id = $user->id;
                        $modelCouponUser->coupon_id = $coupon->id;
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
        $isWechat = $request->isWechat;
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
        $exist = User::find()->where(['email' => $email])->one();
        if (!$exist) {
            // $user->id = 0;
            $user->username = $username;
            $user->email = $email;
            $user->phone = $phone;
            $user->company = $company;
            $user->province_id = $province_id;
            $user->city_id = $city_id;
            $user->address = $address;

            $user->password_hash = md5('123456'); // 帮用户自动生成一个初始密码123456
            $user->created_at = time();
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
            if (!in_array($priceEdition, PriceEditions::pluck('id'))) {
                ReturnJson(false, '价格版本错误');
            }

            $orderTrans = new OrderTrans();
            $order = $orderTrans->setUser($user)->createBySingle($goodsId, $priceEdition, $payType, $coupon_id, $address, $remarks);
        } elseif (!empty($shopIdArr)) { // 已登录，通过购物车下单
            $shopIdArr = explode(',', $shopIdArr);
            // echo '<pre>';print_r($shopIdArr);exit;
            if (!array_key_exists($payType, Order::payType())) {
                ReturnJson(false, '支付方式错误');
            }
            if (!is_array($shopIdArr) || count($shopIdArr) < 1) {
                ReturnJson(false, '参数错误');
            }

            foreach ($shopIdArr as $item) {
                if (!preg_match("/^[1-9][0-9]*$/", $item)) {
                    ReturnJson(false, '参数错误');
                }
            }

            $orderTrans = new OrderTrans();
            $order = $orderTrans->setUser($user)->createByCart($shopIdArr, $payType, $coupon_id, $address, $remarks);
        } elseif (!empty($shopcarJson)) { // 未登录，通过购物车下单
            $shopcarArr = json_decode($shopcarJson, true);
            $orderTrans = new OrderTrans();
            $order = $orderTrans->setUser($user)->createByCartWithoutLogin($shopcarArr, $payType, $coupon_id, $address, $remarks);
        } else {
            ReturnJson(false, '参数错误');
        }

        if ($order === null) {
            return $this->echoMsg($orderTrans->getErrno());
        }

        // 把临时订单号加入缓存
        $cache = Yii::$app->cache;
        $cache->set($tempOrderId, [$order->id, $order->order_number], 600); // 十分钟过期
        $pay = PayFactory::create($order->pay_type);
        Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $isMobile = $isMobile == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
        $pay->setOption(Pay::KEY_IS_MOBILE, $isMobile);
        $isWechat = $isWechat == 1 ? Pay::OPTION_ENABLE : Pay::OPTION_DISENABLE;
        $pay->setOption(Pay::KEY_IS_WECHAT, $isWechat);

        return $pay->do($order);
    }
}