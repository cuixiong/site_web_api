<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/css/index.css" />
    <title></title>
</head>

<body>
    <div class="headerW">
        <div class="logo_">
            <div class="logo_l">
                <a href="/">
                    <img class="logo_img" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/logo.webp" alt="logo">
                </a>
            </div>
            <div class="logo_r">
                <p class="_r_p1"><?= $title ?></p>
            </div>
        </div>
        <div class="xian"></div>
    </div>

    <div class="title_content">
        <div class="_tit_l">
            <img class="_tit_l_img1" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/report.webp" alt="report">
        </div>
        <div class="_tit_r">
            <p class="t_r_p1">
                <a href="<?= $url ?>" style="text-decoration:none;color: #333333;">
                    <?= $product_name ?>
                </a>
            </p>

            <p class="t_r_p2">
                <a href="<?= $url ?>" style="text-decoration:none;color: #333333;">
                    <?= $product_english_name ?>
                </a>
            </p>
            <div class="_r_div1">
                <p style="width: 200px;">行业分类:<?= $category_name ?></p>|
                <p style="width: 160px;margin-left: 20px;">出版时间:<?= $published_date ?></p>|
                <p style="width: 160px;margin-left: 20px;">报告页数:<?= $pages ?></p>

            </div>
            <div class="_r_div1">
                <p style="width: 200px;">报告格式:<?= $serviceMethod ?></p>|
                <p style="width: 280px;margin-left: 20px;">交付方式:<?= $payMethod ?></p>
            </div>
        </div>
    </div>

    <div class="title_content1">
        <div class="tit_con_div1">
            <p style="width: 146px;height: 35px;background: #3958B9;margin-right: 4px;text-align: center;color: #FFFFFF;line-height: 35px;">
            </p>

            <?php
            $editionAll = isset($prices[0]) && is_array($prices[0]) ? array_column($prices[0], 'edition') : [];
            foreach ($editionAll as $index => $edition) {
                if ($index < count($editionAll) - 1) { ?>
                    <p style="width: 224px;height: 35px;background: #3958B9;margin-right: 4px;text-align: center;color: #FFFFFF;line-height: 35px;">
                        <?= $edition ?>
                    </p>
                <?php } else { ?>

                    <p style="width: 224px;height: 35px;background: #3958B9;text-align: center;color: #FFFFFF;line-height: 35px;">
                        <?= $edition ?>
                    </p>
                <?php } ?>
            <?php } ?>
        </div>

        <?php foreach ($prices as $language => $item) { ?>
            <div class="tit_con_div1">
                <p style="width: 146px;height: 35px;background: #F6FAFF;margin-right: 4px;padding-left: 25px;color: #333333;line-height: 35px;font-weight: bold;">
                    <?= $language ?>
                </p>

                <?php $i = 1;
                foreach ($item as $edition => $price) { ?>
                    <?php if ($i < count($item) - 1) { ?>
                        <p style="width: 224px;height: 35px;background: #F6FAFF;margin-right: 4px;text-align: center;color: #333333;line-height: 35px;">
                            <?= !empty($price) ? $price . '元' : '' ?>
                        </p>
                    <?php } else { ?>

                        <p style="width: 224px;height: 35px;background: #F6FAFF;text-align: center;color: #333333;line-height: 35px;">
                            <?= !empty($price) ? $price . '元' : '' ?>
                        </p>
                    <?php } ?>
                    <?php $i++; ?>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <img class="title_img1" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/xuXian.webp" alt="xuXian">

    <!-- 内容摘要 -->
    <div class="title_content2">
        <p class="tit_con2_p1">内容摘要</p>
        <img class="tit_con2_img1" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/zhuangShi.webp" alt="zhuangShi">
    </div>

    <div class="title_content3">
        <?= $description ?>
    </div>

    <!-- 报告目录 -->
    <div class="title_content2">
        <p class="tit_con2_p1">报告目录</p>
        <img class="tit_con2_img1" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/zhuangShi.webp" alt="zhuangShi">
    </div>

    <div class="title_content3">
        <?= $table_of_content ?>
    </div>

    <!-- 报告图表 -->
    <div class="title_content2">
        <p class="tit_con2_p1">报告图表</p>
        <img class="tit_con2_img1" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/zhuangShi.webp" alt="zhuangShi">
    </div>

    <div class="title_content3">
        <?= $tables_and_figures ?>
    </div>
    <!-- 提及的公司 -->
    <div class="title_content2">
        <p class="tit_con2_p1">相关企业</p>
        <img class="tit_con2_img1" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/zhuangShi.webp" alt="zhuangShi">
    </div>

    <div class="title_content3">
        <?= $tables_and_figures ?>
    </div>

    <div class="book_div4">
        <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/zhuangShi02.webp" alt="yeJiao">
        <p><a href="<?= $url ?>" style="text-decoration:none;color: #ffffff;"><?= $homepage ?></a></p>
    </div>
</body>

</html>