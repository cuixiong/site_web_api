<?php
/**
 * CaseShareController.php UTF-8
 * 案例分享控制器
 *
 * @date    : 2025/3/11 14:32 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Http\Controllers;

use App\Models\CaseShare;
use App\Models\Common;
use App\Models\ProductDescription;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\Questions;
use App\Models\SystemValue;

class CaseShareController extends Controller {
    public function list() {
        try {
            $case_share_list = Questions::query()->where('status', 1)
                                        ->orderBy('sort', 'asc')
                                        ->orderBy('id', 'desc')
                                        ->limit(6)
                                        ->get()->toArray();
            foreach ($case_share_list as $key => &$value) {
                $value['relevant'] = $this->getRelevantByProduct($value['product_id']);
            }
        } catch (\Exception $e) {
            \Log::error('未知错误:'.$e->getMessage().'  文件路径:'.__CLASS__.'  行号:'.__LINE__);
            ReturnJson(false, '未知错误');
        }
        ReturnJson(true, 'ok', $case_share_list);
    }

    /**
     *
     * @param mixed   $product_id
     * @param integer $relevant_products_size
     *
     * @return array
     */
    private function getRelevantByProduct(mixed $product_id, $relevant_products_size = 4) {
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
        $keywords = Products::query()->where('id', $product_id)->value('keywords');
        $products =  (new ProductController())->GetRelevantProductResult(
            $product_id,
            $keywords,
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
}
