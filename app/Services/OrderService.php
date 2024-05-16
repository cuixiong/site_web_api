<?php
/**
 * OrderService.php UTF-8
 * 订单业务类
 *
 * @date    : 2024/5/16 13:40 下午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Services;

use App\Const\CommonConst;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\User;

class OrderService {
    /**
     * 校验优惠券
     *
     * @param $userId      int
     * @param $coupon_id   int
     *
     * @return array
     */
    public function checkCoupon($userId, $coupon_id) {
        //优惠券是否存在
        $coupon = Coupon::query()->where("id", $coupon_id)
                        ->where("status", CommonConst::CONST_NORMAL_STATUS)
                        ->first();
        if (empty($coupon)) {
            return [false, '优惠券不存在'];
        }
        //优惠券是否过期
        if ($coupon->time_end < time()) {
            return [false, '优惠券已过期'];
        }
        //优惠券起始时间
        if ($coupon->time_begin > time()) {
            return [false, '优惠券未到使用时间'];
        }
        //优惠券是否被领取
        $coupon_user = CouponUser::query()->where("user_id", $userId)
                                 ->where("coupon_id", $coupon_id)
                                 ->where("is_used", CouponUser::isUsedNO)
                                 ->first();
        if (empty($coupon_user)) {
            return [false, '该用户未领取优惠券,或已使用优惠券'];
        }

        return [$coupon_user, ''];
    }

    /**
     * 标记使用优惠券
     *
     * @param $userId
     * @param $coupon_id
     * @param $orderId
     *
     * @return array
     */
    public function useCouponByUser($userId, $coupon_id, $orderId) {
        list($coupon_user, $msg) = $this->checkCoupon($userId, $coupon_id);
        if (empty($coupon_user)) {
            return [false, $msg];
        }
        $coupon_user->is_used = CouponUser::isUsedYes;
        $coupon_user->use_time = time();
        $coupon_user->order_id = $orderId;
        $res = $coupon_user->save();
        if ($res > 0) {
            return [true, 'ok'];
        } else {
            return [false, '优惠券使用失败'];
        }
    }

    public function recoverCouponStatus($userId, $coupon_id, $orderId) {
        $coupon_user = CouponUser::query()->where("user_id", $userId)
                                 ->where("coupon_id", $coupon_id)
                                 ->where("order_id", $orderId)
                                 ->first();
        if (empty($coupon_user)) {
            return false;
        }
        $coupon_user->is_used = CouponUser::isUsedNO;
        $coupon_user->use_time = 0;
        $coupon_user->order_id = 0;
        $coupon_user->save();
    }
}
