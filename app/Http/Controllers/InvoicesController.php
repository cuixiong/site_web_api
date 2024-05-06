<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoicesRequest;
use App\Models\Invoices;
use App\Models\Order;
use Illuminate\Http\Request;

class InvoicesController extends Controller {
    public function list(Request $request) {
        try {
            $status = $request->input('status', '0');
            if (empty($status) || !isset($status) || !in_array($status, [1, 2])) {
                $status = 0;
            }
            $userId = $request->user->id;
            $model = new Invoices();
            $model = $model->where('user_id', $userId)->when($status, function ($query) use ($status) {
                if ($status == 1) {
                    $query->where('apply_status', 1);
                } elseif ($status == 2) { //已开票
                    $query->where('apply_status', 0);
                }
            })->orderBy('id', 'desc');

            $count = $model->count();

            // 查询偏移量
            if (!empty($request->pageNum) && !empty($request->pageSize)) {
                $model->offset(($request->pageNum - 1) * $request->pageSize);
            }
            // 查询条数
            if (!empty($request->pageSize)) {
                $model->limit($request->pageSize);
            }else{
                $model->limit(15);
            }

            $fields = ['id', 'created_at', 'invoice_type', 'company_name', 'tax_code', 'title',
                       'status', 'apply_status', 'price'];
            $model->select($fields);
            $rs = $model->get();
            $rdata = [];
            $rdata['data'] = $rs;
            $rdata['count'] = $count;
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
            $model = new Invoices();
            $record = $model->findOrFail($request->id);
            $rs = $record->toArray();
            ReturnJson(true, '获取成功', $rs);
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function apply(Request $request) {
        try {
            (new InvoicesRequest())->apply($request);
            $input = $request->all();
            $model = new Order();
            $orderObj = $model->findOrFail($input['order_id']);
            $userId = $request->user->id;
            if ($orderObj->user_id != $userId) {
                ReturnJson(false, '非法操作');
            }
            if (!in_array($orderObj->is_pay, [Order::PAY_FINISH])) {
                ReturnJson(false, '订单还未完成,不能申请');
            }
            $model = new Invoices();
            $isExist = $model->where('order_id', $input['order_id'])->count();
            if ($isExist) {
                ReturnJson(false, '该订单已经申请过');
            }
            $addData = [
                'company_name'    => $input['company_name'],
                'company_address' => $input['company_address'],
                'tax_code'        => $input['tax_code'],
                'invoice_type'    => $input['invoice_type'],
                'price'           => $orderObj->actually_paid,
                'user_id'         => $userId,
                'order_id'        => $input['order_id'],
                'title'           => $orderObj->product_name,
                'apply_status'    => 0,
                'phone'           => $input['phone'],
                'bank_name'       => $input['bank_name'],
                'bank_account'    => $input['bank_account'],
            ];
            $rs = $model->create($addData);
            if (!$rs) {
                ReturnJson(false, '申请失败');
            }
            ReturnJson(true, '申请成功');
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }
}
