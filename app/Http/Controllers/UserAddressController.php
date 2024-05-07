<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsersAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressController extends Controller {
    public function list(Request $request) {
        try {
            $userId = $request->user->id;
            $model = new UserAddress();
            $model = $model->where('user_id', $userId)->orderBy('sort', 'asc');
            // 查询偏移量
            if (!empty($request->pageNum) && !empty($request->pageSize)) {
                $model->offset(($request->pageNum - 1) * $request->pageSize);
            }
            // 查询条数
            if (!empty($request->pageSize)) {
                $model->limit($request->pageSize);
            }
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

    public function store(Request $request) {
        try {
            $usersAddressRequest = new UsersAddressRequest();
            $usersAddressRequest->store($request);
            $input = $request->all();
            $model = new UserAddress();
            $is_default = $input['is_default'];
            $userId = $request->user->id;
            if ($is_default == 1) {
                //如果有设置默认地址，则将之前默认地址改为非默认
                $model->where('user_id', $userId)->update(['is_default' => 0]);
            }
            $input['user_id'] = $userId;
            $rs = $model->create($input);
            if ($rs) {
                ReturnJson(true, '添加成功');
            } else {
                ReturnJson(false, '添加失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function form(Request $request) {
        try {
            $model = new UserAddress();
            $record = $model->findOrFail($request->id);
            $rs = $record->toArray();
            $userId = $request->user->id;
            if ($userId != $record->user_id) {
                ReturnJson(false, '非法操作');
            }
            $rs['area_id'] = [
                $record['province_id'],
                $record['city_id'],
            ];
            ReturnJson(true, '获取成功', $rs);
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function update(Request $request) {
        try {
            $usersAddressRequest = new UsersAddressRequest();
            $usersAddressRequest->update($request);
            $input = $request->all();
            $model = new UserAddress();
            $record = $model->findOrFail($input['id']);
            $userId = $request->user->id;
            if ($userId != $record->user_id) {
                ReturnJson(false, '非法操作');
            }
            $is_default = $input['is_default'];
            $userId = $request->user->id;
            if ($is_default == 1) {
                //如果有设置默认地址，则将之前默认地址改为非默认
                $model->where('user_id', $userId)->update(['is_default' => 0]);
            }
            $rs = $record->update($input);
            if ($rs) {
                ReturnJson(true, '修改成功');
            } else {
                ReturnJson(false, '修改失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function delete(Request $request) {
        try {
            $model = new UserAddress();
            $record = $model->findOrFail($request->id);
            $userId = $request->user->id;
            if ($userId != $record->user_id) {
                ReturnJson(false, '非法操作');
            }
            $rs = $record->delete();
            if ($rs) {
                ReturnJson(true, '删除成功');
            } else {
                ReturnJson(false, '删除失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    public function setDefault(Request $request) {
        try {
            $model = new UserAddress();
            $record = $model->findOrFail($request->id);
            $userId = $request->user->id;
            if ($userId != $record->user_id) {
                ReturnJson(false, '非法操作');
            }
            //其他设置非默认
            $model->where('user_id', $userId)->update(['is_default' => 0]);
            //当前设置默认
            $rs = $record->update(['is_default' => 1]);
            if ($rs) {
                ReturnJson(true, '设置成功');
            } else {
                ReturnJson(false, '设置失败');
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }
}
