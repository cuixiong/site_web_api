<?php

namespace App\Http\Controllers;

use App\Models\Invoices;
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
            // 查询偏移量
            if (!empty($request->pageNum) && !empty($request->pageSize)) {
                $model->offset(($request->pageNum - 1) * $request->pageSize);
            }
            // 查询条数
            if (!empty($request->pageSize)) {
                $model->limit($request->pageSize);
            }
            $fields = ['id', 'created_at', 'invoice_type', 'company_name', 'tax_code', 'title',
                       'status', 'apply_status', 'price'];
            $model->select($fields);
            $rs = $model->get();
            if ($rs) {
                ReturnJson(true, '获取成功', $rs);
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
}
