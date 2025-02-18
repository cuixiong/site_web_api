<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use App\Http\Requests\UsersRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CouponUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller {
    // 账号注册
    public function Register(Request $request) {
        $username = $request->name;
        $email = $request->email;
        $country_id = $request->country_id;
        $address = $request->address;
        $province_id = $request->province_id;
        $city_id = $request->city_id;
        $phone = $request->phone;
        $company = $request->company;
        $password = $request->password;
        DB::beginTransaction();
        $user = User::where('email', $email)->first();
        if ($user && $user->check_email == 0) {
            //这个邮箱已经被注册但未验证
            (new SendEmailController)->Register($user->id);//只要是用户以前已经注册但没有验证邮箱，无论以前的邮件里的链接有没有过期，都重新发送验证邮件

            return ReturnJson(201, '您当前邮箱已注册，但未验证');
        }
        if ($user && $user->check_email == 1) {
            //这个邮箱已经被注册并已通过验证，可凭账号密码登录
            return ReturnJson(201, '此邮箱已经注册并完成了验证，请直接登录');
        }
        $model = new User;
        $model->username = $username;
        $model->email = $email;
        $model->area_id = $country_id;
        $model->address = $address;
        $model->province_id = $province_id;
        $model->city_id = $city_id;
        $model->phone = $phone;
        $model->company = $company;
        $model->password = Hash::make($password);// 密码使用hash值
        $model->created_at = time();
        $model->status = 0;
        $model->save();
        DB::commit();
        $user = User::where(['id' => $model->id])->first();
        $user->token = JWTAuth::fromUser($user);//生成token
        $user->save();
        // 发送验证邮件
        (new SendEmailController)->Register($model->id);
        ReturnJson(true);
    }

    // 验证邮箱是否注册
    public function ValidateEmail(Request $request) {
        $email = $request->email;
        if (empty($email)) {
            ReturnJson(false, '邮箱为空');
        }
        $user = User::where(['email' => $email])->first();
        if ($user) {
            if ($user->check_email == 0) {
                (new SendEmailController)->Register($user->id);
                ReturnJson(false, '该邮箱已被注册但未验证邮箱，系统已重新发送一封验证邮件给您，请查收');
            }
            if ($user->check_email == 1) {
                ReturnJson(false, '此邮箱已经注册并完成了验证，请直接登录');
            }
        } else {
            ReturnJson(false, '此邮箱可以注册');
        }
    }

    // 再次发送邮件
    public function SendEmailAgain(Request $request) {
        $email = $request->email;
        if (empty($email)) {
            ReturnJson(false, '邮箱为空');
        }
        $user = User::where(['email' => $email])->first();
        if ($user) {
            if ($user->check_email == 0) {
                (new SendEmailController)->Register($user->id);
                ReturnJson(true, '已发送');
            }
            if ($user->check_email == 1) {
                ReturnJson(false, '此邮箱已经注册并完成了验证');
            }
        } else {
            ReturnJson(false, '此邮箱未注册');
        }
    }

    // 账号登陆
    public function Login(Request $request) {
        $email = $request->email;
        $password = $request->password;
        if (trim($email) == '' || trim($password) == '') {
            ReturnJson(false, '邮箱/密码不能为空');
        }
        $user = User::where(['email' => $email])->first();
        if (!$user) {
            ReturnJson(false, '账号不存在，请先注册');
        }
        if ($user->check_email == 0) {
            //用户的状态如果是0，代表已经注册但未验证邮箱，status等于1代表邮箱已经通过验证的用户
            (new SendEmailController)->Register($user->id);//只要是用户以前已经注册但没有验证邮箱，无论以前的邮件里的链接有没有过期，都重新发送验证邮件
            ReturnJson(false, '账号的邮箱未验证，请先验证！');
        }
        if (!Hash::check($password, $user->password)) {
            ReturnJson(false, '密码不正确');
        }
        if ($user->status == 0) {
            ReturnJson(false, '账号处于封禁状态，禁止登陆');
        }
        $token = JWTAuth::fromUser($user);//生成token
        //'expires' => auth('api')->factory()->getTTL() + 66240,
        // 最后一次登录时间
        $user->login_time = time();
        $user->token = $token;
        $user->update();
        $data = [
            'id'                => $user->id,// ID
            'name'              => $user->name,// 名称
            'username'          => $user->username,// 用户名
            'email'             => $user->email,// 邮箱
            'phone'             => $user->phone,// 手机号
            'area_id'           => [$user->province_id, $user->city_id],
            'country_id'        => $user->area_id,
            'company'           => $user->company,// 公司
            'login_time'        => $user->login_time,// 最近登陆的时间
            'login_time_format' => date('Y-m-d H:i:s', $user->login_time),// 最近登陆的时间
            'token'             => $token,// token
            'address'           => $user->address,
        ];
        ReturnJson(true, '', $data);
    }

    // 发送重置密码邮箱
    public function ResetPasswordEmail(Request $request) {
        if (!empty($request->user->email)) {
            $email = $request->user->email;
        } else {
            $email = $request->email;
        }
        $user = User::where('email', $email)->first();
        if (empty($user)) {
            ReturnJson(false, '邮箱不存在，请先去注册！');
        }
        // 发送重置密码邮件
        (new SendEmailController)->ResetPassword($user->email);
        ReturnJson(true);
    }

    /**
     * Reset Password Request
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function DoResetPassword(Request $request) {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'password' => 'required',
                    'token'    => 'required',
                ],
                [
                    'password.required' => '请输入密码',
                    'token.required'    => '请输入TOKEN',
                ]
            );
            if ($validator->fails()) {
                ReturnJson(false, $validator->errors()->first());
            }
            $token = decrypt($request->token);
            $tokenData = explode('&', $token);
            if (empty($tokenData)) {
                ReturnJson(false, trans('lang.token_error'));
            }
            $id = $tokenData[1];
            $email = $tokenData[0];
            $endTime = $tokenData[2];
            $lastUpdTime = $tokenData[3];
            //有效期校验
            if (empty($endTime) || time() > $endTime) {
                ReturnJson(false, trans('lang.token_expired'));
            }
            $model = User::where('email', $email)->where('id', $id)->first();
            if (!$model) {
                ReturnJson(false, trans('lang.eamail_undefined'));
            }
            // 一次token只能验证一次 , 防止盗号风险
            if (strtotime($model->updated_at) > strtotime($lastUpdTime)) {
                ReturnJson(false, trans('lang.token_expired'));
            }
            $model->password = Hash::make($request->get('password'));
            $model->save();
            ReturnJson(true, trans('lang.request_success'));
        } catch (DecryptException $de) {
            ReturnJson(false, trans('lang.token_error'));
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    /**
     * Verify the authenticity of the email
     *
     * @param use Illuminate\Http\Request $request;
     *
     * @return response Code
     */
    public function CheckEmail(Request $request) {
        try {
            $token = $request->token;
            if (!isset($token) || empty($token)) {
                ReturnJson(false, trans('lang.token_empty'));
            }
            // $token = base64_decode($request->token);
            // list($email,$id) = explode('&',$token);
            $token = $request->token;
            // 可能有bug，比如邮件没限制时间或者封禁账号后通过邮件重新启用了。后面有空再改
            $res = User::where('token', $token)->update(['check_email' => 1, 'status' => 1]);
            // $res = User::where('email',$email)->where('id',$id)->update(['check_email' => 1]);
            $res ? ReturnJson(true, trans('lang.request_success')) : ReturnJson(false, trans('lang.request_error'));
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    /**
     * 验证邮箱是否存在
     */
    public function ExistsEmail(Request $request) {
        $email = $request->email;// 邮箱
        $res = User::where('email', $email)->count();
        if ($res > 0) {
            ReturnJson(true, '该邮箱已注册过，可直接登录');
        } else {
            ReturnJson(false, '该邮箱可以注册');
        }
    }

    /**
     * 会员个人中心里的优惠券列表
     *
     * @param int $status 优惠券状态值：0全部，1未使用，2已使用，3已过期
     * @param int $scene  场景：1代表用户进入自己的个人中心，2代表用户下单。
     * @param int $price  前端传过来的订单总价（原价）
     */
    public function Coupons(Request $request) {
        $status = $request->status;
        $scene = $request->scene;
        $price = $request->price;
        if (!isset($status)) { // 由于允许参数status的值为0，所以这里要用【!isset】
            ReturnJson(false, 'status');
        }
        //        if (!isset($scene)) {
        //            ReturnJson(false, 'scene');
        //        }
        $userId = $request->user()->id;
        $result = CouponUser::from('coupon_users as user')
                            ->select([
                                         'coupon.type',
                                         'value',
                                         'coupon.time_end',
                                         'coupon.time_begin',
                                         'coupon.code',
                                         'coupon.id',
                                         'user.is_used'
                                     ])
                            ->leftJoin('coupons as coupon', 'user.coupon_id', '=', 'coupon.id')
                            ->where([
                                        'user.user_id'  => $userId,
                                        'coupon.status' => 1,
                                    ]);
        switch ($status) {
            case 1: // 未使用的优惠券
                $result = $result->where('user.is_used', CouponUser::isUsedNO);
                break;
            case 2: // 已使用的优惠券
                $result = $result->where('user.is_used', CouponUser::isUsedYes);
                break;
            case 3: // 已过期的优惠券
                $result = $result->where('coupon.time_end', '<', time());
                break;
            default: // 全部优惠券
                break;
        }
        // 如果传入总价，则现金券需满足不超过总价的条件，打折券则无限制
        if (!empty($price)) {
            $result->where(function ($query) use ($price) {
                $query->where(function ($subQuery) use ($price) {
                    $subQuery->where('type', 2)->where('value', '<', $price);
                })->orWhere('type', 1);
            });
        }
        $result = $result->get()->toArray();
        $data = [];
        if (!empty($result) && is_array($result)) {
            foreach ($result as $key => $value) {
                if ($value['is_used'] == CouponUser::isUsedYes) { // 该券已使用
                    $couponStatus = 2;
                } else if ($value['time_end'] < time()) { // 该券已过期
                    $couponStatus = 3;
                } else if ($value['is_used'] == CouponUser::isUsedNO) { // 该券未使用
                    $couponStatus = 1;
                }
                //优惠券状态值：0全部，1未使用，2已使用，3已过期
                if ($status == 1 && $couponStatus != 1) {
                    continue;
                } elseif ($status == 2 && $couponStatus != 2) {
                    continue;
                } elseif ($status == 3 && $couponStatus != 3) {
                    continue;
                }
                $data[$key]['id'] = $value['id'];
                $data[$key]['type'] = $value['type'];
                $data[$key]['value'] = $value['type'] == 1 ? round($value['value'], 0) : (float)$value['value'];
                $data[$key]['day_begin'] = $value['time_begin'] ? date('Y.m.d', $value['time_begin']) : '';
                $data[$key]['day_end'] = $value['time_end'] ? date('Y.m.d', $value['time_end']) : '';
                $data[$key]['day_begin_year'] = date('Y', $value['time_begin']);
                $data[$key]['day_begin_month'] = date('m', $value['time_begin']);
                $data[$key]['day_begin_day'] = date('d', $value['time_begin']);
                $data[$key]['day_end_year'] = date('Y', $value['time_end']);
                $data[$key]['day_end_month'] = date('m', $value['time_end']);
                $data[$key]['day_end_day'] = date('d', $value['time_end']);
                $data[$key]['code'] = $value['code'];
                $data[$key]['status'] = $couponStatus;
            }
        }
        if (!empty($data)) {
            $data = array_values($data);
        }
        ReturnJson(true, '', $data);
    }

    /**
     * 用户信息
     *
     * @return array
     */
    public function Info(Request $request) {
        try {
            $user = $request->user;
            $userId = $user->id;
            $user = User::findOrFail($userId);
            $user = $user->toArray();
            $data = [];
            $data['userid'] = $user['id'];
            $data['username'] = $user['username'];
            $data['email'] = $user['email'];
            $data['phone'] = $user['phone'];
            $data['company'] = $user['company'];
            $data['address'] = $user['address'];
            $data['area'] = [
                $user['province_id'],
                $user['city_id']
            ];
            $data['country_id'] = $user['area_id'];
            // $data['token'] = $user['token'];
            $data['login_time'] = !empty($user['login_time']) ? $user['login_time'] : '';
            $data['login_time_format'] = date('Y-m-d H:i:s', $user['login_time']);
            ReturnJson(true, '', $data);
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage(), []);
        }
    }

    /**
     * 注册验证邮箱
     */
    public function VerifyEmail(Request $request) {
        $data = $request->all();
        if (!isset($data['timestamp']) || !isset($data['randomstr']) || !isset($data['authkey'])
            || !isset($data['sign'])) {
            ReturnJson(false, '参数错误');
        }
        $userToken = base64_decode($data['sign']);
        $id = $userToken['id'];
        if (empty($userToken)) {
            ReturnJson(false, '签名错误');
        }
        $user = User::find($id);
        if ($user->check_email == 0) {
            $token = JWTAuth::fromUser($user);//生成tokenJWTAuth::
            $user->check_email = 1;
            $user->save();
            (new SendEmailController)->RegisterSuccess($user->id);// 注册成功
            ReturnJson(true, '', ['token' => $token]);
        } else {
            ReturnJson(false);
        }
    }

    /**
     *  退出登陆
     */
    public function loginout() {
        //JWTAuth::invalidate($request->bearerToken()); ?
        auth('api')->logout();
        ReturnJson(true, trans('lang.request_success'));
    }

    /**
     *  用户信息修改
     */
    public function update(Request $request) {
        try {
            $usersRequest = new UsersRequest();
            $usersRequest->update($request);
            $input = $request->all();
            $model = new User();
            $user = $request->user;
            $user = $model->findOrFail($user->id);
            $name = $input['name'];
//            $isExist = User::query()->where('username', $name)
//                           ->where('id', '<>', $user->id)
//                           ->count();
//            if ($isExist) {
//                ReturnJson(false, '用户名已存在');
//            }
            $user->username = $name;
            $user->email = $input['email'];
            $user->phone = $input['phone'];
            $user->company = $input['company'] ?? '';
            $user->address = $input['address'] ?? '';
            $user->province_id = $input['province_id'] ?? 0;
            $user->city_id = $input['city_id'] ?? 0;
            $user->area_id = $input['area_id'] ?? 0;
            $rs = $user->save();
            if ($rs) {
                ReturnJson(true, '修改成功');
            } else {
                ReturnJson(false, '修改失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function changePassword(Request $request) {
        try {
            if (checkSiteAccessData(['tycn'])) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'new_password' => 'required|min:6',
                    ],
                    [
                        'new_password.required' => '请输入新密码',
                        'new_password.min'      => '新密码不能少于6位',
                    ]
                );
                if ($validator->fails()) {
                    ReturnJson(false, $validator->errors()->first());
                }
                $input = $request->all();
                $user = $request->user;
                $user = User::findOrFail($user->id);
            } else {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'old_password' => 'required',
                        'new_password' => 'required|min:6',
                    ],
                    [
                        'old_password.required' => '请输入旧密码',
                        'new_password.required' => '请输入新密码',
                        'new_password.min'      => '新密码不能少于6位',
                    ]
                );
                if ($validator->fails()) {
                    ReturnJson(false, $validator->errors()->first());
                }
                $input = $request->all();
                $user = $request->user;
                $user = User::findOrFail($user->id);
                if (!Hash::check($input['old_password'], $user->password)) {
                    ReturnJson(false, '旧密码错误');
                }
            }
            $user->password = Hash::make($input['new_password']);
            $rs = $user->save();
            if ($rs) {
                //修改成功，退出登陆
                auth('api')->logout();
                ReturnJson(true, '修改成功');
            } else {
                ReturnJson(false, '修改失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }
}
