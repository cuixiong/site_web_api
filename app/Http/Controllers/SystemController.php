<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\SystemValue;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    // 通过父级ID获取到网站设置的子级全部数据
    public function GetChildren(Request $request)
    {
        $id = $request->id;
        if(empty($id)){
            ReturnJson(false,'ID不允许为空');
        }
        $data = SystemValue::where('parent_id',$id)
                ->where('status',1)
                ->select(['name','key','value'])
                ->get()
                ->toArray();
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['key']] = [
                'name' => $value['name'],
                'value' => $value['value']
            ];
        }
        ReturnJson(true,'',$result);
    }
}