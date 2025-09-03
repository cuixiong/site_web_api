<!--
 * @Author: Angry 1556191665@qq.com
 * @Date: 2022-09-28 10:30:58
 * @LastEditors: Angry 1556191665@qq.com
 * @LastEditTime: 2022-09-30 10:18:49
 * @FilePath: \168_PDF(0823) - 副本\index.html
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
-->
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF</title>
    <style>
        .pdf {
            max-width: 1000px;
            width: 100%;
        }

        .pdf .title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
        }

        .pdf .title .ti_l {
            display: flex;
        }

        .pdf .title .ti_l .l_img {
            max-width: 110px;
            width: 100%;
            margin-right: 10px;
        }

        .pdf .title .ti_l .l_p {
            font-size: 12px;
            font-family: HarmonyOS Sans SC;
            font-weight: 500;
            color: #2B4C89;
            margin: 0px;
            padding-top: 48px;
        }

        .pdf .title .ti_r .r_img {
            max-width: 313px;
            width: 100%;
        }

        .pdf .title .ti_r .r_p {
            font-size: 12px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #FFFFFF;
            margin-top: -22px;
            text-align: center;
        }

        .pdf .title_c {
            font-size: 18px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #2E65D4;
            line-height: 25px;
            margin-bottom: 20px;
        }

        .pdf .ti_1 {
            display: flex;
            margin-bottom: 20px;
        }

        .pdf .ti_1 .ti_i {
            max-width: 26px;
            width: 100%;
            max-height: 20px;
            height: 100%;
            margin-right: 5px;
        }

        .pdf .ti_1 .ti_p {
            font-size: 16px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #333333;
            line-height: 25px;
            margin: -2px 0px 0px 0px;
            word-break: break-all;
            word-wrap: break-word;
        }

        .pdf .ti_2 {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .pdf .ti_2 ._div1 .ti_2p1 {
            max-width: 185px;
            width: 100%;
            height: 25px;
            background: linear-gradient(-90deg, #295ECA, #5C7FEF);
            border-top-left-radius: 25px;
            border-bottom-left-radius: 25px;
            border-right: 2px solid #FFFFFF;
            font-size: 14px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #FFFFFF;
            line-height: 25px;
            text-align: center;
            margin-top: -29px;
        }

        .pdf .ti_2 ._div1 ._21_i {
            max-width: 185px;
            width: 100%;
            max-height: 25px;
            height: 100%;
            padding-right: 2px;
            margin-top: 14px;
        }

        .pdf .ti_2 ._div2 .ti_2p2 {
            max-width: 185px;
            width: 100%;
            height: 25px;
            background: linear-gradient(-90deg, #295ECA, #5C7FEF);
            border-right: 2px solid #FFFFFF;
            font-size: 14px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #FFFFFF;
            line-height: 25px;
            text-align: center;
            margin-top: -29px;
            padding-right: 2px;
        }

        .pdf .ti_2 ._div2 ._21_i2 {
            max-width: 185px;
            width: 100%;
            max-height: 25px;
            height: 100%;
            padding-right: 2px;
            padding-left: 2px;
            margin-top: 14px;
        }

        .pdf .ti_2 ._div3 .ti_2p3 {
            max-width: 185px;
            width: 100%;
            height: 25px;
            background: linear-gradient(-90deg, #295ECA, #5C7FEF);
            border-top-right-radius: 25px;
            border-bottom-right-radius: 25px;
            border-right: 2px solid #FFFFFF;
            font-size: 14px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #FFFFFF;
            line-height: 25px;
            text-align: center;
            margin-top: -29px;
        }

        .pdf .ti_2 ._div3 ._21_i3 {
            max-width: 185px;
            width: 100%;
            max-height: 25px;
            height: 100%;
            margin-top: 14px;
            padding-right: 2px;
        }

        .pdf .ti_3 {
            display: flex;
            margin-bottom: 30px;
        }

        .pdf .ti_3 ._3_l ._3_l_p {
            width: 120px;
            height: 25px;
            background: #FF9A71;
            border-radius: 12px;
            font-size: 12px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #FFFFFF;
            text-align: center;
            line-height: 26px;
            margin-top: -30px;
            margin-bottom: 15px;
        }

        .pdf .ti_3 ._3_l ._31_i {
            margin-top: 12px;
        }

        .pdf .ti_3 ._3_l ._3_l_p2 {
            width: 120px;
            height: 25px;
            background: #5C7FEF;
            border-radius: 12px;
            font-size: 12px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #FFFFFF;
            text-align: center;
            line-height: 26px;
            margin-top: -30px;
        }

        .pdf .ti_3 ._3_l ._31_i2 {
            margin-top: 12px;
        }

        .pdf .ti_3 ._3_r .ull1 {
            padding: 0px;
            margin-left: 28px;
        }

        .pdf .ti_3 ._3_r .ull1 .ul_li {
            display: flex;
        }

        .pdf .ti_3 ._3_r .ull1 .ul_li .ul_p {
            width: 31%;
            margin: 0px;
            font-size: 12px;
            margin-left: 8px;
            margin-right: 10px;
        }

        .pdf .ti_3 ._3_r .ull1 .ul_li .ul_p1 {
            margin: 0px;
            font-size: 12px;
        }

        .pdf .ti_3 ._3_r .ull2 {
            padding: 0px;
            margin-left: 28px;
            margin-top: 22px;
        }

        .pdf .ti_3 ._3_r .ull2 .ul_li {
            display: flex;
        }

        .pdf .ti_3 ._3_r .ull2 .ul_li .ul_p {
            width: 31%;
            margin: 0px;
            font-size: 12px;
            margin-left: 8px;
            margin-right: 8px;
        }

        .pdf .ti_3 ._3_r .ull2 .ul_li .ul_p1 {
            margin: 0px;
            font-size: 12px;
        }

        .pdf .ti_3 ._3_r .ull3 {
            padding: 0px;
            margin-left: 28px;
            margin-top: 22px;
        }

        .pdf .ti_3 ._3_r .ull3 .ul_li {
            display: flex;
        }

        .pdf .ti_3 ._3_r .ull3 .ul_li .ul_p {
            width: 31%;
            margin: 0px;
            font-size: 12px;
            margin-left: 8px;
            margin-right: 10px;
            width: 170px;
        }

        .pdf .ti_3 ._3_r .ull3 .ul_li .ul_p1 {
            margin: 0px;
            font-size: 12px;
        }

        .pdf .ti_4 ._4_p {
            margin-top: -40px;
            margin-bottom: 40px;
            font-size: 20px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #2E65D4;
            line-height: 25px;
            margin-left: 45px;
        }

        .pdf .ti_6 {
            text-align: right;
            margin-top: -120px;
        }

        .pdf .ti_7 {
            margin-top: 10px;
        }
        
        pre {
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;
            white-space: -o-pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="pdf">
        <div class="title">
            <div class="ti_l">
                <img class="l_img" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/logo.webp" alt="">
                <p class="l_p">YHリサーチは、お客様の実際のビジネス ニーズに応じたフレキシブルなサービス を提供し、グロ一バルな産業分野に対し てタイムリ</p>
            </div>
            <div class="ti_r">
                <img class="r_img" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/zhuangShi02.webp" alt="">
                <p class="r_p"><?= $email ?></p>
            </div>

        </div>

        <p class="title_c">
            <a href="<?= $url ?>" style="text-decoration:none;color: #2E65D4;">
                <?= $product_name ?>
            </a>
        </p>
        <div class="ti_1">
            <img class="ti_i" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/lan.webp" alt="">
            <p class="ti_p">
                <a href="<?= $url ?>" style="text-decoration:none;color: #333333;">
                    <?= $product_english_name ?>
                </a>
            </p>
        </div>

        <div class="ti_2">
            <?php foreach ($prices[0]['data'] as $key => $item) { ?>
                <div class="_div<?= ($key + 1) ?>">
                    <img class="_21_i<?= ($key != 0) ? ($key + 1) : '' ?>" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/edtion<?= ($key + 1) ?>.png" alt="">
                    <p class="ti_2p<?= ($key + 1) ?>"><?= $item['edition'] ?></p>
                </div>
            <?php } ?>
            <!-- <div class="_div2">
                <img class="_21_i2" src="/uploads/pdf/images/4.png" alt="">
                <p class="ti_2p2">マルチユーザー版</p>
            </div>
            <div class="_div3">
                <img class="_21_i3" src="/uploads/pdf/images/5.png" alt="">
                <p class="ti_2p3">企業版</p>
            </div> -->
        </div>
        <div class="ti_3">
            <div class="_3_l">

                <?php foreach ($prices as $key => $group) { ?>
                    <img class="_31_i<?= ($key != 0) ? 1 : '' ?>" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/group<?= ($key + 1) % 2 ?>.png" alt="">
                    <p class="_3_l_p<?= ($key + 1) % 2 == 0 ? '' : 2 ?>">【<?= $group['language'] ?>】</p>
                <?php } ?>

                <!-- <img class="_31_i" src="/uploads/pdf/images/1 (2).png" alt="">
                <p class="_3_l_p">【英語版】</p>

                <img class="_31_i1" src="/uploads/pdf/images/2.png" alt="">
                <p class="_3_l_p2">【日本語版】</p>

                <img class="_31_i1" src="/uploads/pdf/images/1 (2).png" alt="">
                <p class="_3_l_p">【英語と日本語版】</p> -->

            </div>
            <div class="_3_r">
                <?php foreach ($prices as $key => $group) { ?>

                    <ul class="ull<?= ($key + 1) ?>">
                        <li class="ul_li">
                            <?php foreach ($group['data'] as $key2 => $priceItem) { ?>
                                <p class="ul_p"><?= 'USD' . $priceItem['usd_price'] ?>換算<?= $priceItem['jpy_price'] . '円' ?></p>
                            <?php } ?>
                        </li>
                    </ul>
                <?php } ?>
                <!-- <ul class="ull1">
                    <li class="ul_li">
                        <p class="ul_p">USD3350換算386,791.00円</p>
                        <p class="ul_p">USD3350換算386,7.00円</p>
                        <p class="ul_p">USD3350換算386,791.00円</p>
                    </li>
                </ul>
                <ul class="ull2">
                    <li class="ul_li">
                        <p class="ul_p">USD3350換算386,791.00円</p>
                        <p class="ul_p">USD3350換算386,7.00円</p>
                        <p class="ul_p">USD3350換算386,791.00円</p>
                    </li>
                </ul>
                <ul class="ull3">
                    <li class="ul_li">
                        <p class="ul_p">USD3350換算386,791.00円</p>
                        <p class="ul_p">USD3350換算386,791.00円</p>
                        <p class="ul_p">USD3350換算386,791.00円</p>
                    </li>
                </ul> -->
            </div>
        </div>

        <div class="ti_4">
            <img class="_4_i" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/02zhuangshi.png" alt="">
            <p class="_4_p">概要</p>
        </div>

        <div class="ti_5">
            <p>
                <pre><?=$description?></pre>
            </p>
        </div>
        
        <div class="ti_4">
            <img class="_4_i" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/02zhuangshi.png" alt="">
            <p class="_4_p">総目録</p>
        </div>

        <div class="ti_5">
            <p>
                <pre><?=$table_of_content?></pre>
            </p>
        </div>
        
        <div class="ti_4">
            <img class="_4_i" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/02zhuangshi.png" alt="">
            <p class="_4_p">表と図のリスト</p>
        </div>

        <div class="ti_5">
            <p>
                <pre><?=$tables_and_figures?></pre>
            </p>
        </div>

        <div class="ti_6">
            <img style="max-width: 205px; width: 100%;text-align: right;" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/logo02.webp" alt="">
        </div>
        <div class="ti_7">
            <img style="max-width: 1200px; width: 100%;text-align: right;" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/images/yejiao.png" alt="">
        </div>
    </div>
</body>

</html>