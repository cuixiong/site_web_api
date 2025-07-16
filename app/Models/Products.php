<?php

namespace App\Models;

use App\Models\Base;
use App\Services\SphinxService;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Support\Facades\Redis;

class Products extends Base {
    protected $table = 'product_routine';

    /**
     * 获取缩略图 , 没有就取分类id的缩略图
     *
     * @return mixed
     */
    public function getThumbImgAttribute() {
        if (!empty($this->attributes['thumb'])) {
            return Common::cutoffSiteUploadPathPrefix($this->attributes['thumb']);
        } elseif ($this->attributes['category_id']) {
            $value = ProductsCategory::where('id', $this->attributes['category_id'])->value('thumb');
            if (!empty($value)) {
                return Common::cutoffSiteUploadPathPrefix($value);
            }
        }
        // 若报告图片为空，则使用系统设置的默认报告高清图
        $defaultImg = SystemValue::where('key', 'default_report_high_img')->value('value');

        return !empty($defaultImg) ? $defaultImg : '';
    }

    /**
     * 获取缩略图
     *
     * @param $product
     *
     * @return array|mixed|string|string[]
     */
    public static function getThumbImgUrl($product) {
        if (!empty($product['thumb'])) {
            return Common::cutoffSiteUploadPathPrefix($product['thumb']);
        } elseif ($product['category_id']) {
            $value = ProductsCategory::where('id', $product['category_id'])->value('thumb');
            if (!empty($value)) {
                return Common::cutoffSiteUploadPathPrefix($value);
            }
        }
        // 若报告图片为空，则使用系统设置的默认报告高清图
        $defaultImg = SystemValue::where('key', 'default_report_high_img')->value('value');

        return !empty($defaultImg) ? $defaultImg : '';
    }

    public function getCategotyTextAttribute() {
        $name = '';
        if (!empty($this->attributes['category_id'])) {
            $name = ProductsCategory::where('id', $this->attributes['category_id'])->value('name');
        }

        return $name;
    }

    public function getProShortDescAttribute() {
        $description = (new ProductDescription(
            date('Y', $this->attributes['published_date'])
        ))->where('product_id', $this->attributes['id'])->value('description');
        if (!empty($description)) {
            return mb_substr($description, 0, 100, 'UTF-8');
        }

        return '';
    }

    public function getPublishedDataAttributes($value) {
        return date('Y-m-d', $value);
    }

    /**
     * @param int            $priceEdition
     * @param Products|array $goods 必须要有这个字段 price
     *
     * @return int
     */
    public static function getPrice($priceEdition, $goods) {
        $priceEdition = PriceEditionValues::find($priceEdition);
        $price = 0;
        if (!empty($priceEdition)) {
            $priceRule = $priceEdition['rules'];
            $price = eval("return ".sprintf($priceRule, $goods['price']).";");
        }
        //        $priceRule = Redis::hget(PriceEditionValues::RedisKey, $priceEdition);
        //        $priceRule = json_decode($priceRule, true);
        //        $priceRule = $priceRule['rules'];
        //        $price = eval("return ".sprintf($priceRule, $goods['price']).";");
        return $price;
    }

    /**
     * 获取订单里的每个商品的原价格（不带折扣或带折扣）
     */
    public static function getPriceBy($price, $goods, $timestamp = null, $price_values_id = 0) {
        if ($timestamp !== null) {
            $timestamp = time();
        }
        $actuallyPaid = $price;
        $discount_time_begin = $goods['discount_time_begin'];
        $discount_time_end = $goods['discount_time_end'];
        if (
            $timestamp >= $discount_time_begin
            && ($timestamp <= $discount_time_end)
        ) {
            $goods_price_values = $goods['price_values'] ?? '';
            if (!empty($goods_price_values)) {
                //配置了价格版本, 没有匹配, 不使用优惠
                $goods_price_id_list = explode(',', $goods_price_values);
                if (!in_array($price_values_id, $goods_price_id_list)) {
                    return $actuallyPaid;
                }
            }
            // 如果队列不能把discount_time_begin和discount_time_end的值恢复成null，就不能要这句代码了
            if ($goods['discount_type'] == 1) {
                $actuallyPaid = common::getDiscountPrice($price, $goods['discount']);
            } else if ($goods['discount_type'] == 2) {
                $actuallyPaid = bcsub($price, $goods['discount_amount'], 2);
            }
        }

        return $actuallyPaid;
    }

    /**
     * 通过价格和价格版本进行计算价格
     */
    public static function CountPrice(
        $price,
        $publisherId,
        $languages = null,
        $priceEditionsValue = null,
        $priceEditionsPid = null,
        $currencyData = []
    ) {
        // eval感觉不太安全
        // $evaluator = new \Matex\Evaluator();
        // 这里的代码可以复用 开始
        $prices = [];
        // 计算报告价格（当前语言是放在站点端的，但是后台的语言是放在总控端的，接手的小伙伴自己改）
        $languages = $languages ? $languages : Languages::GetList();
        if ($languages) {
            foreach ($languages as $index => $language) {
                $priceEditions = PriceEditionValues::GetList(
                    $language['id'],
                    $publisherId,
                    $priceEditionsValue,
                    $priceEditionsPid
                );
                if ($priceEditions) {
                    $prices[$index]['language'] = $language['name'];
                    foreach ($priceEditions as $keyPriceEdition => $priceEdition) {
                        $prices[$index]['data'][$keyPriceEdition]['id'] = $priceEdition['id'];
                        $prices[$index]['data'][$keyPriceEdition]['edition'] = $priceEdition['name'];
                        $prices[$index]['data'][$keyPriceEdition]['is_logistics'] = $priceEdition['is_logistics'];
                        $prices[$index]['data'][$keyPriceEdition]['notice'] = $priceEdition['notice'];
                        $prices[$index]['data'][$keyPriceEdition]['sort'] = $priceEdition['sort'];
                        $prices[$index]['data'][$keyPriceEdition]['price'] = eval(
                            "return ".sprintf(
                                $priceEdition['rules'],
                                $price
                            ).";"
                        );
                        // $prices[$index]['data'][$keyPriceEdition]['price'] = $evaluator->execute(sprintf($priceEdition['rules'], $price));
                        // 给每个版本添加多种货币的价格
                        if ($currencyData && count($currencyData)) {
                            foreach ($currencyData as $currencyItem) {
                                $currencyKey = strtolower($currencyItem['code']).'_price';
                                $prices[$index]['data'][$keyPriceEdition][$currencyKey]
                                    = $prices[$index]['data'][$keyPriceEdition]['price']
                                      * $currencyItem['exchange_rate'];
                            }
                        }
                    }
                }
            }
        }
        $prices = array_values($prices);

        return $prices;
        // 这里的代码可以复用 结束
    }

    /**
     * 获取价格版本
     *
     * @param array|int $publisherIds
     */
    public static function getPriceEdition(
        $publisherIds,
        $languages = null,
        $priceEditionsValue = null,
        $priceEditionsPid = null
    ) {
        // 这里的代码可以复用 开始
        $priceEditionList = [];
        $languages = $languages ? $languages : Languages::GetList();
        if ($languages) {
            $priceEditionAll = PriceEditions::query()->where("is_deleted", 1)->where("status", 1)->get()->toArray();
            if (!empty($publisherIds) && !is_array($publisherIds)) {
                $publisherIds = [$publisherIds];
            }
            $editionIdList = [];
            foreach ($publisherIds as $publisherId) {
                foreach ($priceEditionAll as $key => $priceEditionItem) {
                    //Db数据库查询
                    $priceEditionItem['publisher_id'] = explode(',', $priceEditionItem['publisher_id']);
                    if (in_array($publisherId, $priceEditionItem['publisher_id'])) {
                        if (!isset($editionIdList[$publisherId])) {
                            $editionIdList[$publisherId] = [];
                        }
                        $editionIdList[$publisherId][] = $priceEditionItem['id'];
                    }
                }
            }
            $languagesIds = array_column($languages, 'id');
            $languagesNames = array_column($languages, 'name', 'id');
            // return $editionIdList;
            $priceEditionList = [];
            foreach ($editionIdList as $publisherIdKey => $editionIds) {
                $rData = [];
                $editionsValues = PriceEditionValues::query()->select(
                    "id", "name", "notice", "sort", "is_logistics", "rules", "language_id"
                )
                                                    ->where("status", 1)
                                                    ->where("is_deleted", 1)
                                                    ->whereIn("language_id", $languagesIds)
                                                    ->whereIn("edition_id", $editionIds)
                                                    ->orderBy("sort", "asc")
                                                    ->get()
                                                    ->toArray();
                foreach ($editionsValues as $key => $priceEditionsItem) {
                    $languageId = $priceEditionsItem['language_id'];
                    $languageName = $languagesNames[$languageId];
                    if (!isset($rData[$languageId])) {
                        $rData[$languageId] = [];
                        $rData[$languageId]['language'] = '';
                        $rData[$languageId]['data'] = [];
                    }
                    $rData[$languageId]['language'] = $languageName;
                    $rData[$languageId]['data'][] = [
                        'id'           => $priceEditionsItem['id'],
                        'edition'      => $priceEditionsItem['name'],
                        'is_logistics' => $priceEditionsItem['is_logistics'],
                        'notice'       => $priceEditionsItem['notice'],
                        'sort'         => $priceEditionsItem['sort'],
                        'rules'        => $priceEditionsItem['rules'],
                    ];
                }
                $priceEditionList[$publisherIdKey] = array_values($rData);
            }
        }

        return $priceEditionList;
        // 这里的代码可以复用 结束
    }

    /**
     * 通过价格和getPriceEdition的价格版本进行计算价格
     */
    public static function countPriceEditionPrice($priceEditions, $price, $currencyData = []) {
        foreach ($priceEditions as $index1 => $priceEditionItem) {
            $priceEditionValues = $priceEditionItem['data'];
            foreach ($priceEditionValues as $index2 => $priceEditionValueItem) {
                $priceEditions[$index1]['data'][$index2]['price'] = eval(
                    "return ".sprintf(
                        $priceEditionValueItem['rules'],
                        $price
                    ).";"
                );
                unset($priceEditions[$index1]['data'][$index2]['rules']);
                // 给每个版本添加多种货币的价格
                if ($currencyData && count($currencyData)) {
                    foreach ($currencyData as $currencyItem) {
                        $currencyKey = strtolower($currencyItem['code']).'_price';
                        $priceEditions[$index1]['data'][$index2][$currencyKey]
                            = $priceEditions[$index1]['data'][$index2]['price'] * $currencyItem['exchange_rate'];
                    }
                }
            }
        }

        return $priceEditions;
    }

    /**
     * 返回相关产品数据-重定向/相关报告
     */
    public static function GetRelevantProductResult(
        $id, $keyword, $page = 1, $pageSize = 1, $searchField = 'url', $selectField = '*'
    ) {
        try {
            $hidden = SystemValue::where('key', 'sphinx')->value('hidden');
            if ($hidden == 1) {
                return self::SearchRelevantForSphinx($id, $keyword, $page, $pageSize, $searchField, $selectField);
            } else {
                return self::SearchRelevantForMysql($id, $keyword, $page, $pageSize, $searchField, $selectField);
            }
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
            \Log::error('应用端查询失败,异常信息为:'.json_encode([$e->getMessage()]));
            ReturnJson(false, '请求失败,请稍后再试');
            // return [];
        }
    }

    public static function SearchRelevantForSphinx($id, $keyword, $page, $pageSize, $searchField, $selectField) {
        if (empty($id) || empty($keyword)) {
            return [];
        }
        $sphinxSrevice = new SphinxService();
        $conn = $sphinxSrevice->getConnection();
        //报告昵称,英文昵称匹配查询
        $query = (new SphinxQL($conn))->select('id')
                                      ->from('products_rt')
                                      ->orderBy('sort', 'asc')
                                      ->orderBy('published_date', 'desc')
                                      ->orderBy('id', 'desc');
        $query = $query->where('status', '=', 1);
        $query = $query->where("published_date", "<=", time());
        // 排除本报告
        $query = $query->where('id', '<>', intval($id));
        // 精确查询
        if (!empty($keyword)) {
            $val = addslashes($keyword);
            $query->where($searchField, '=', $val);
        }
        //查询结果分页
        $offset = ($page - 1) * $pageSize;
        $query->limit($offset, $pageSize);
        // $query->option('max_matches', $offset + $pageSize);
        // $query->setSelect($selectField);
        // $result = $query->execute();
        // $products = $result->fetchAllAssoc();
        // 因为有些字段sphinx没有，所以sphinx查出id后再去mysql查询
        $query->setSelect('id');
        $result = $query->execute();
        $productsIds = $result->fetchAllAssoc();
        if (!empty($productsIds) && count($productsIds) > 0) {
            $productsIds = array_column($productsIds, 'id');
            $products = Products::select($selectField)
                                ->whereIn("id", $productsIds)
                                ->get()->toArray();
        }

        //
        return $products ?? [];
    }

    public static function SearchRelevantForMysql($id, $keyword, $page, $pageSize, $searchField, $selectField) {
        $products = Products::select($selectField)
                            ->where([$searchField => $keyword, 'status' => 1])
                            ->where("id", "<>", $id)
                            ->limit($pageSize, ($page - 1) * $pageSize)
                            ->orderBy('published_date', 'desc')
                            ->orderBy('id', 'desc')
                            ->get()->toArray();

        return $products;
    }
}
