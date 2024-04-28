<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class UsersAddressRequest extends FormRequest {
    /**
     * 新增
     *
     * @param \Illuminate\Http\Request $request
     */
    public function store($request) {
        $rules = [
            'address'        => 'required',
            'city_id'        => 'required',
            'province_id'    => 'required',
            'consignee'      => 'required',
            'contact_number' => 'required',
            'is_default'     => 'required',
        ];
        $message = [
            'address.required'        => '地址不能为空',
            'city_id.required'        => '城市不能为空',
            'province_id.required'    => '省份不能为空',
            'consignee.required'      => '收货人不能为空',
            'contact_number.required' => '联系电话不能为空',
            'is_default.required'     => '默认参数不能为空',
        ];

        return $this->validateRequest($request, $rules, $message);
    }

    /**
     * 修改数据新增
     *
     * @param \Illuminate\Http\Request $request
     */
    public function update($request) {
        $rules = [
            'id'             => 'required',
            'address'        => 'required',
            'city_id'        => 'required',
            'province_id'    => 'required',
            'consignee'      => 'required',
            'contact_number' => 'required',
            'is_default'     => 'required',
        ];
        $message = [
            'id.required'             => 'id不能为空',
            'address.required'        => '地址不能为空',
            'city_id.required'        => '城市不能为空',
            'province_id.required'    => '省份不能为空',
            'consignee.required'      => '收货人不能为空',
            'contact_number.required' => '联系电话不能为空',
            'is_default.required'     => '默认参数不能为空',
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
