<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class UsersRequest extends FormRequest {
    /**
     * 修改数据新增
     *
     * @param \Illuminate\Http\Request $request
     */
    public function update($request) {
        $rules = [
            'name'        => 'required',
            'email'       => 'required',
            'province_id' => 'required',
            'phone'       => 'required',
            'company'     => 'required',
        ];
        $message = [
            'name.required'        => '名称不能为空',
            'email.required'       => '邮箱不能为空',
            'province_id.required' => '地区不能为空',
            'phone.required'       => '电话不能为空',
            'company.required'     => '公司不能为空',
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
