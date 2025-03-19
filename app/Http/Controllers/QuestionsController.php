<?php
/**
 * QuestionsController.php UTF-8
 * 问答控制器
 *
 * @date    : 2025/3/12 15:29 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers;

use App\Models\Answers;
use App\Models\Common;
use App\Models\ProductDescription;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\Questions;
use App\Models\SystemValue;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;

class QuestionsController extends Controller {
    public function list(Request $request) {
        try {
            $page = $request->page ?? 1;
            $pageSize = $request->pageSize ?? 10;
            $type = $request->type ?? 'all';  //unanswered
            if ($type == 'unanswered') {
                $question_id = Answers::query()->where('status', 1)
                       ->groupBy('question_id')
                       ->pluck('question_id')->toArray();
                $query = Questions::query()->where('status', 1)
                                  ->where('answer_count', 0)
                                  ->orderBy('sort', 'asc')
                                  ->orderBy('id', 'desc');
            } else {
                $query = Questions::query()->where('status', 1)
                                  ->orderBy('sort', 'asc')
                                  ->orderBy('id', 'desc');
            }
            $count = $query->count();
            $question_list = $query->offset(($page - 1) * $pageSize)
                                   ->limit($pageSize)
                                   ->get()->toArray();
            foreach ($question_list as $key => &$value) {
                //展示最新的一条回答
                $value['answer'] = Answers::query()->where('question_id', $value['id'])
                                          ->orderBy('sort', 'asc')
                                          ->orderBy('id', 'desc')
                                          ->first();
            }
        } catch (\Exception $e) {
            \Log::error('未知错误:'.$e->getMessage().'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            ReturnJson(false, '未知错误');
        }
        $teamMemList = $this->getTeamMemList($request);
        $data = [
            "data"      => $question_list,
            "page"      => $page,
            "pageSize"  => $pageSize,
            'pageCount' => ceil($count / $pageSize),
            "count"     => intval($count),
            'team_list' => $teamMemList,
        ];
        ReturnJson(true, 'ok', $data);
    }

    public function detail(Request $request) {
        try {
            $id = $request->id ?? 0;
            if (empty($id)) {
                ReturnJson(false, '参数错误');
            }
            $data = [];
            $question_info = Questions::query()->where('status', 1)
                                      ->where('id', $id)->first();
            if (empty($question_info)) {
                ReturnJson(false, '问题不存在!');
            }
            $data['question'] = $question_info;
            //查询回答列表
            $data['answer'] = Answers::query()->where('question_id', $question_info['id'])
                                     ->orderBy('sort', 'asc')
                                     ->orderBy('id', 'desc')
                                     ->get()->toArray();
            $data['team_list'] = $this->getTeamMemList($request);


            $keywords = $question_info['keywords'];
            $keyword_list = explode(',',$keywords);
            $data['relevant'] = $this->getRelevantByProduct($keyword_list , 8);

            ReturnJson(true, 'ok', $data);
        } catch (\Exception $e) {
            \Log::error('未知错误:'.$e->getMessage().'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            ReturnJson(false, '未知错误');
        }
    }


    /**
     *
     * @param mixed   $keyword_list
     * @param integer $relevant_products_size
     *
     * @return array
     */
    private function getRelevantByProduct($keyword_list, $relevant_products_size = 4) {
        $select = [
            'id',
            'name',
            'english_name',
            'keywords',
            'url',
            'price',
            'publisher_id',
            'published_date',
            'thumb',
            'category_id',
        ];
        $products =  (new ProductController())->GetRelevantProductResult(
            -1,
            $keyword_list,
            1,
            $relevant_products_size,
            'keywords',
            $select
        );
        $data = [];
        if ($products) {
            // 分类信息
            $categoryIds = array_column($products, 'category_id');
            $categoryData = ProductsCategory::select(['id', 'name', 'thumb'])->whereIn('id', $categoryIds)->get()
                                            ->toArray();
            $categoryData = array_column($categoryData, null, 'id');
            // 默认图片
            // 若报告图片为空，则使用系统设置的默认报告高清图
            $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
            foreach ($products as $index => $product) {
                //每个报告加上分类信息
                $tempCategoryId = $product['category_id'];
                $product['category_name'] = isset($categoryData[$tempCategoryId])
                                            && isset($categoryData[$tempCategoryId]['name'])
                    ? $categoryData[$tempCategoryId]['name'] : '';
                $product['category_thumb'] = isset($categoryData[$tempCategoryId])
                                             && isset($categoryData[$tempCategoryId]['thumb'])
                    ? $categoryData[$tempCategoryId]['thumb'] : '';
                // 图片获取
                $tempThumb = '';
                if (!empty($product['thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($product['thumb']);
                } elseif (!empty($product['category_thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($product['category_thumb']);
                } else {
                    // 如果报告图片、分类图片为空，使用系统默认图片
                    $tempThumb = !empty($defaultImg) ? $defaultImg : '';
                }
                $data[$index]['thumb'] = $tempThumb;
                $data[$index]['name'] = $product['name'];
                $data[$index]['keywords'] = $product['keywords'];
                $data[$index]['english_name'] = $product['english_name'];
                $suffix = date('Y', strtotime($product['published_date']));
                $data[$index]['description'] = (new ProductDescription($suffix))->where('product_id', $product['id'])
                                                                                ->value('description');
                $data[$index]['description'] = $data[$index]['description'] ? $data[$index]['description'] : '';
                $data[$index]['description'] = mb_substr($data[$index]['description'], 0, 100, 'UTF-8');
                $data[$index]['id'] = $product['id'];
                $data[$index]['url'] = $product['url'];
                $data[$index]['category_name'] = $product['category_name'];
                $data[$index]['published_date'] = $product['published_date'] ? date(
                    'Y-m-d',
                    strtotime($product['published_date'])
                ) : '';
                //$data[$index]['prices'] = Products::CountPrice($product['price'], $product['publisher_id']);
            }
        }

        return $data;
    }

    public function answer(Request $request) {
        try {
            $input = $request->input();
            $content = $input['content'] ?? '';
            //判断问题是否存在
            $id = $input['question_id'] ?? 0;
            if (empty($id) || empty($content)) {
                ReturnJson(false, '参数错误');
            }
            $question_info = Questions::query()->where('status', 1)
                                      ->where('id', $id)->first();
            if (empty($question_info)) {
                ReturnJson(false, '问题不存在!');
            }
            //判断是否登陆
            if (empty(User::IsLogin())) {
                ReturnJson(false, '请先登录!');
            }
            $user = User::IsLogin();
            $data = [
                'question_id' => $id,
                'user_id'     => $user->id,
                'content'     => $content,
                'sort'        => 100,
                'status'      => 1,
                'answer_at'   => time()
            ];
            $answer_id = Answers::query()->insertGetId($data);
            ReturnJson(true, '回答成功');
        } catch (\Exception $e) {
            \Log::error('未知错误:'.$e->getMessage().'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            ReturnJson(false, '未知错误');
        }
    }

    public function getTeamMemList(Request $request) {
        //分析师关注榜
        return TeamMember::query()->where("status", 1)
                         ->where("attention_level", ">", 0)
                         ->orderBy('attention_level', 'desc')
                         ->limit(6)
                         ->get()->toArray();
    }

}
