<?php

namespace App\Models;

use App\Models\Base;
use CommonConst;

class Order extends Base {
    const PAY_UNPAID  = 1;
    const PAY_SUCCESS = 2;
    const PAY_CANCEL  = 3;
    const PAY_FINISH  = 4;
    const PAY_STATUS_TYPE
                      = [
            self::PAY_UNPAID  => '未支付',
            self::PAY_SUCCESS => '已支付',
            self::PAY_CANCEL  => '已取消',
            self::PAY_FINISH  => '已完成',
        ];
    protected $table       = 'orders';
    protected $appends     = ['is_pay_text', 'create_date', 'is_invoice', 'order_product'];
    protected $addressInfo = [];

    public static function payType(): array {
        static $payType = null;
        if ($payType === null) {
            $payType = Pay::query()->pluck('code', 'id')->toArray();
        }

        return $payType;
    }

    public function getIsInvoiceAttribute() {
        $cnt = Invoices::query()->where("order_id", $this->attributes['id'])->count();
        if ($cnt > 0) {
            return CommonConst::CONST_IS_EXIST;
        } else {
            return CommonConst::CONST_IS_NO_EXIST;
        }
    }

    public function getPayTypeTextAttribute() {
        $payType = Pay::get()->pluck('name', 'id')->toArray();

        return $payType[$this->attributes['pay_type']] ?? '';
    }

    public function getIsPayTextAttribute() {
        return self::PAY_STATUS_TYPE[$this->attributes['is_pay']] ?? '';
    }

    public function getCreateDateAttribute() {
        return date("Y-m-d", $this->attributes['created_at']);
    }

    public function getProductNameAttribute() {
        $text = '';
        if (isset($this->attributes['id']) && !empty($this->attributes['id'])) {
            $orderGoodsIdList = OrderGoods::where('order_id', $this->attributes['id'])->pluck('goods_id')->toArray();
            if (!empty($orderGoodsIdList)) {
                $productNames = Products::whereIn('id', $orderGoodsIdList)->pluck('name')->toArray();
                $text = ($productNames && count($productNames)) ? implode("\n", $productNames) : '';
            }
        }

        return $text ?? '';
    }

    /**
     * 查找订单第一个商品信息
     *
     * @return mixed
     */
    public function getOrderProductAttribute() {
        $goodsInfo = [];
        if (isset($this->attributes['id']) && !empty($this->attributes['id'])) {
            $goods_id = OrderGoods::where('order_id', $this->attributes['id'])->value('goods_id');
            if (!empty($goods_id)) {
                $goodsInfo = Products::query()->where('id', $goods_id)
                                     ->select(['id', 'name', 'url'])
                                     ->first();
                if (!empty($goodsInfo)) {
                    $goodsInfo = $goodsInfo->toArray();
                }
            }
        }

        return $goodsInfo ?? [];
    }

    public function getAddressInfoAttribute() {
        $rdata = [
            'username' => $this->attributes['username'] ?? '',
            'company'  => $this->attributes['company'] ?? '',
            'phone'    => $this->attributes['phone'] ?? '',
            'email'    => $this->attributes['email'] ?? '',
            'address'  => $this->attributes['address'] ?? '',
        ];

        // TODO: cuizhixiong 2024/4/30   province_id  city_id
        return $rdata;
    }
}
