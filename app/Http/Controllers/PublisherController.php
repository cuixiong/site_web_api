<?php

namespace App\Http\Controllers;

use App\Models\Publishers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublisherController extends Controller {
    public function getPulishIdByProductList() {
    }

    public function alphabeticSearch(Request $request) {
        $keyWord = $request->input('keyWord', '');
        $query = Publishers::query()->where("status", 1);
        if (!empty($keyWord)) {
            $query->where('name', 'LIKE', "%{$keyWord}%");
        }
        $publishers = $query->get()->toArray();
        $data = [];
        foreach ($publishers as $publisher) {
            // 截取首字符
            $firstChar = mb_substr($publisher['name'], 0, 1, 'UTF-8');
            // 将首字符转换为大写
            $word = mb_strtoupper($firstChar, 'UTF-8');
            $fordata['word'] = $word;
            $fordata['id'] = $publisher['id'];
            $data[] = $fordata;
        }
        array_unshift($data, [
            'id'   => '0',
            'word' => 'All',
        ]);
        ReturnJson(true, '请求成功', $data);
    }

    public function publishers(Request $request) {
        $input = $request->all();
        $query = Publishers::query()->where("status", 1);
        $id = $input['id'] ?? null;
        $publisher_id = $input['publisher_id'] ?? null;
        if (!empty($id)) {
            $query = $query->where("id", $id);
        }
        if (!empty($publisher_id)) {
            $query = $query->where("id", $publisher_id);
        }
        $publishers = $query->get()->toArray();
        $data = [];
        foreach ($publishers as $publisher) {
            $forData = [];
            $forData['id'] = $publisher['id'];
            // 截取首字符
            $firstChar = mb_substr($publisher['name'], 0, 1, 'UTF-8');
            // 将首字符转换为大写
            $word = mb_strtoupper($firstChar, 'UTF-8');
            $forData['word'] = $word;
            $forData['publishers'][] = $publisher;
            $data[$word] = $forData;
        }
        $data = array_values($data);
        ReturnJson(true, '请求成功', $data);
    }

    public function searchAuto(Request $request) {
        $keyWord = $request->input('keyWord', '');
        if (empty($keyWord)) {
            ReturnJson(false, '请输入搜索内容', []);
        }
        $query = Publishers::query()->where("status", 1);
        $publishers = $query->get()->toArray();
        $data = [];
        foreach ($publishers as $publisher) {
            // 截取首字符
            $firstChar = mb_substr($publisher['name'], 0, 1, 'UTF-8');
            // 将首字符转换为大写
            $word = mb_strtoupper($firstChar, 'UTF-8');
            $fordata = [];
            $fordata['word'] = $word;
            $fordata['name'] = $publisher['name'];
            $fordata['id'] = $publisher['id'];
            $data[] = $fordata;
        }
        $after_data = [];
        foreach ($data as $key => $value) {
            if (strtolower($value['word']) == strtolower($keyWord)) {
                $after_data[] = $value;
            }
        }
        ReturnJson(true, '请求成功', $after_data);
    }
}
