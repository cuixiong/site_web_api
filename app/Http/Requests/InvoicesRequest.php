<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class InvoicesRequest extends FormRequest {
    /**
     * 新增
     *
     * @param \Illuminate\Http\Request $request
     */
    public function apply($request) {
        $rules = [
            'order_id'        => 'required',
            'invoice_type'    => 'required',
            'company_name'    => 'required',
//            'tax_code'        => 'required',
//            'company_address' => 'required',
//            'phone'           => 'required',
//            'bank_account'    => 'required',
//            'bank_name'       => 'required'
        ];
        $message = [
            'order_id.required'        => '订单不能为空',
            'invoice_type.required'    => '发票类型不能为空',
            'company_name.required'    => '公司名称不能为空',
//            'tax_code.required'        => '纳税人不能为空',
//            'company_address.required' => '注册地址不能为空',
//            'phone.required'           => '注册电话不能为空',
//            'bank_account.required'    => '银行账户不能为空',
//            'bank_name.required'       => '开户银行不能为空',
        ];

        return $this->validateRequest($request, $rules, $message);
    }
    public function applySinglePage($request) {
        $rules = [
            'title'           => 'required',
            'price'           => 'required',
            'invoice_type'    => 'required',
            'company_name'    => 'required',
            'tax_code'        => 'required',
        ];
        $message = [
            'title.required'           => '报告名称不能为空',
            'price.required'           => '金额不能为空',
            'invoice_type.required'    => '发票类型不能为空',
            'company_name.required'    => '公司名称不能为空',
            'tax_code.required'        => '纳税人不能为空',
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
