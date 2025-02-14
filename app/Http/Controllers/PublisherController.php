<?php

namespace App\Http\Controllers;

use App\Models\Publishers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublisherController extends Controller {
    public function alphabeticSearch(Request $request) {
        $keyWord = $request->input('keyWord', '');
        $query = Publishers::query()->where("status", 1);
        if (!empty($keyWord)) {
            $query->where('company', 'LIKE', "%{$keyWord}%");
        }
        $publishers = $query->get()->toArray();
        array_unshift($publishers, [
            'id' => '0',
            'word' => 'All',
        ]);
        ReturnJson(true, '请求成功', $publishers);
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
            if (in_array($publisher['company'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])
                && empty($publisher_id)) {
                continue;
            } else {
                $data[] = $publisher;
            }
        }
        ReturnJson(true, '请求成功', $data);
    }

    public function searchAuto(Request $request) {
        $keyWord = $request->query('keyWord', null);
        if ($keyWord === null) {
            ReturnJson(false, '请输入搜索内容', '');
        } else {
            $keyWords = explode(" ", $keyWord);
            $query = Publishers::query()->where("status", 1);
            foreach ($keyWords as $keyword) {
                $query->where('company', 'LIKE', '%'.$keyword.'%');
            }
            $publishers = $query->selectRaw('id , company as word')->get()->toArray();
            ReturnJson(true, '请求成功', $publishers);
        }
    }
}
