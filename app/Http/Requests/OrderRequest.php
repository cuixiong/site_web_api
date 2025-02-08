<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class OrderRequest extends FormRequest {
    /**
     * 修改数据新增
     *
     * @param \Illuminate\Http\Request $request
     */
    public function createandpay($request) {
        $rules = [
            'username'    => 'required',
            'email'       => 'required',
            'phone'       => 'required',
            'company'     => 'required',
//            'province_id' => 'required',
//            'address'     => 'required',
        ];
        $message = [
            'username.required'    => '用户名不能为空',
            'email.required'       => '邮箱不能为空',
            'email.email'          => '邮箱格式错误',
            'phone.required'       => '联系电话不能为空',
            'company.required'     => '公司名不能为空',
//            'province_id.required' => '省份不能为空',
//            'address.required'     => '收货地址不能为空',
        ];

        return $this->validateRequest($request, $rules, $message);
    }

    /**
     * 修改数据新增
     *
     * @param \Illuminate\Http\Request $request
     */
    public function Coupon($request) {
        $rules = [
            'username' => 'required',
            'email'    => 'required',
            'phone'    => 'required',
            'company'  => 'required',
            'code'     => 'required',
        ];
        $message = [
            'username.required' => '用户名不能为空',
            'email.required'    => '邮箱不能为空',
            'email.email'       => '邮箱格式错误',
            'phone.required'    => '联系电话不能为空',
            'company.required'  => '公司名不能为空',
            'code.required'     => '优惠券码不能为空',
        ];

        return $this->validateRequest($request, $rules, $message);
    }

    /**
     * 验证表单数据中间方法
     *
     * @param $request 表单数据
     * @param $rules   验证规则
     * @param $message 错误提示
     */
    protected function validateRequest($request, $rules = [], $message = []) {
        $Validate = Validator::make($request->all(), $rules, $message)->validate();

        return $Validate;
    }
}
