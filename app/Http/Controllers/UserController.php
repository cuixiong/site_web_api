<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Common\SendEmailController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // 账号注册
    public function Register(Request $request)
    {
        $username = $request->username;
        $email = $request->email;
        $province_id = $request->province_id;
        $area_id = $request->area_id;
        $phone = $request->phone;
        $company = $request->company;
        $password = $request->password;

        DB::beginTransaction();
        $user = User::where('email',$email)->first();
        if ($user && $user->check_email == 0) {
            //这个邮箱已经被注册但未验证
            (new SendEmailController)->Register($user->id);//只要是用户以前已经注册但没有验证邮箱，无论以前的邮件里的链接有没有过期，都重新发送验证邮件
            return ReturnJson(201,'您当前邮箱已注册，但未验证');
        }
        if ($user && $user->check_email == 1) {
            //这个邮箱已经被注册并已通过验证，可凭账号密码登录
            return ReturnJson(201,'此邮箱已经注册并完成了验证，请直接登录');
        }

        $model = new User;
        $model->username = $username;
        $model->email = $email;
        // $model->province_id = $province_id;// MMG中的未知参数
        $model->area_id = $area_id;
        $model->phone = $phone;
        $model->company = $company;
        $model->password = md5($password);
        $model->created_at = time();
        $model->status = 1;
        $model->save();
        DB::commit();
        // 发送验证邮件
        (new SendEmailController)->Register($model->id);
        ReturnJson(true);
    }

    // 验证邮箱是否注册
    public function ValidateEmail(Request $request)
    {
        $email = $request->email;
        if(empty($email)){
            ReturnJson(false,'邮箱为空');
        }
        $user = User::where(['email'=>$email])->first();
        if($user){
            if($user->check_email == 0){
                (new SendEmailController)->Register($user->id);
                ReturnJson(false,'该邮箱已被注册但未验证邮箱，系统已重新发送一封验证邮件给您，请查收');
            }
            if($user->check_email == 1){ 
                ReturnJson(false,'此邮箱已经注册并完成了验证，请直接登录');
            }
        } else {
            ReturnJson(false,'此邮箱可以注册');
        }
    }

    // 账号登陆
    public function Login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;
        if (trim($email) == '' || trim($password) == '') {
            ReturnJson(false,'邮箱/密码不能为空');
        }
        $user = User::where(['email'=>$email])->first();
        if(!$user){
            ReturnJson(false,'账号不存在，请先注册');
        }
        if ($user->check_email == 0) { 
            //用户的状态如果是0，代表已经注册但未验证邮箱，status等于1代表邮箱已经通过验证的用户
            (new SendEmailController)->Register($user->id);//只要是用户以前已经注册但没有验证邮箱，无论以前的邮件里的链接有没有过期，都重新发送验证邮件
            ReturnJson(false,'账号的邮箱未验证，请先验证！');
        }
        if($user->password != md5($password)){
            ReturnJson(false,'密码不正确');
        }
        if($user->status == 0){
            ReturnJson(false,'账号处于封禁状态，禁止登陆');
        }
        // 最后一次登录时间
        $user->login_time = time();
        $user->update();
        $data = [
            'id' => $user->id,// ID
            'name' => $user->name,// 名称
            'username' => $user->username,// 用户名
            'email' => $user->email,// 邮箱
            'phone' => $user->phone,// 手机号
            'area_id' => $user->country_id,// 地区ID
            'company' => $user->company,// 公司
            'login_time' => $user->login_time,// 最近登陆的时间
        ];
        ReturnJson(true,'',$data);
    }

    // 发送重置密码邮箱
    public function ResetPasswordEmail(Request $request)
    {
        $email = $request->email;
        $user = User::where('email',$email)->first();
        if(empty($user)){
            ReturnJson(false,'邮箱不存在，请先去注册！');
        }
        // 发送重置密码邮件
        (new SendEmailController)->ResetPassword($user->email);
        ReturnJson(true);
    }


    /**
     * Reset Password Request
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function DoResetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required',
                'token' =>'required',
            ], [
                'password.required' => '请输入密码',
                'token.required' => '请输入TOKEN',
            ]);
            if ($validator->fails()) {
                ReturnJson(FALSE,$validator->errors()->first());
            }
            $token = base64_decode($request->token);
            list($email,$id) = explode('&',$token);
            $model = User::where('email',$email)->where('id',$id)->first();
            if (!$model) {
                ReturnJson(false,trans('lang.eamail_undefined'));
            }
            $model->password = MD5($request->get('password'));
            $model->save();
            ReturnJson(true,trans('lang.request_success'));
        } catch (\Exception $e) {
            ReturnJson(false,$e->getMessage());
        }
    }

    /**
     * Verify the authenticity of the email
     * @param use Illuminate\Http\Request $request;
     * @return response Code
     */
    public function CheckEmail(Request $request){
        try {
            $token = $request->token;
            if(!isset($token) || empty($token)){
                ReturnJson(FALSE,trans('lang.token_empty'));
            }
            $token = base64_decode($request->token);
            list($email,$id) = explode('&',$token);
            $res = User::where('email',$email)->where('id',$id)->update(['check_email' => 1]);
            $res ? ReturnJson(TRUE,trans('lang.request_success')) : ReturnJson(FALSE,trans('lang.request_error'));
        } catch (\Exception $e) {
            ReturnJson(FALSE,$e->getMessage());
        }
    }

    /**
     * 验证邮箱是否存在
     */
    public function ExistsEmail(Request $request){
        $email = $request->email;// 邮箱
        $res = User::where('email',$email)->count();
        if($res > 0) {
            ReturnJson(true);
        } else {
            ReturnJson(false);
        }
    }
}