<?php

namespace App\Models;
class Common extends Base {
    // 总控服务器放置的图片路径是[public/site/分站点/] 文件夹下 ,因此保存的路径带有站点标识
    // 由于前台网站用的图片是oss对象存储，因此要截掉该部分
    // 例如：数据库保存的值为 [/site/MMG_CN/products/1.jpg]
    // 后台正常引用 为 [域名 + /site/MMG_CN/products/1.jpg]
    // 前台正常引用 为 [oss域名 + /products/1.jpg]
    // 默认值我写在 App\Providers\AppServiceProvider boot()方法初始化
    public static $siteUploadPathPrefix;

    public static function cutoffSiteUploadPathPrefix($path = '') {
        return str_replace(self::$siteUploadPathPrefix, '', $path);
    }

    /**
     *
     * @param $price
     * @param $discount
     *
     * @return mixed
     */
    public static function getDiscountPrice($price, $discount) {
        $discountPrice = bcmul($price, $discount, 2);
        $actuallyPaid = bcdiv($discountPrice, 100, 2);

        return $actuallyPaid;
    }
}
