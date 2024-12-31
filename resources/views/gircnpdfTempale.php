<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="./css/index.css" /> -->
    <title></title>
    <style>
        @page {
            size: auto A4 landscape;
            /* a4大小 横向打印 */
            /* margin: 3mm; 这个选项可以隐藏浏览器默认生成的页眉和页脚*/
        }

        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        p {
            padding: 0;
            margin: 0;
        }

        ul {
            list-style: none;
        }

        a {
            text-decoration: none;
        }

        .mb20 {
            margin-bottom: 20px;
        }

        .pl20 {
            padding-left: 20px;
        }

        .w {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .colorFD3C01 {
            color: #FD3C01 !important;
        }

        .color999 {
            color: #999999 !important;
        }

        .headerW {
            /* background: url("/uploads/pdf/yeMei.webp"); */
            background-size: 100% 100%;
        }

        .headerW .logo_ {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: -40px;
        }

        .headerW .logo_ .logo {
            max-width: 178px;
            margin-left: 6px;
            margin-bottom: 4px;
        }

        .headerW .logo_ .logo a {
            display: block;
            width: 70%;
        }

        .headerW .logo_ .logo a img {
            display: block;
            width: 100%;
            padding-bottom: 10px;
            padding-top: 10px;
            margin-left: 32px;
        }

        .headerW .logo_ .price_ {
            font-size: 20px;
            font-family: Poppins;
            font-weight: 500;
            color: #FFFFFF;
            line-height: 34px;
            margin-bottom: 24px;
            margin-right: 24px;
        }

        .headerW .pic img {
            display: block;
            width: 100%;
        }

        .mainWrapper {
            padding-bottom: 100px;
        }

        .mainWrapper .reports_info .reports_info_item {
            display: flex;
            align-items: center;
        }

        .mainWrapper .reports_info .reports_info_item .pic {
            width: 22%;
            max-width: 350px;
            margin-right: 20px;
        }

        .mainWrapper .reports_info .reports_info_item .pic img {
            display: block;
            /* max-width: 300px; */
            margin-left: 3px;
            width: 100%;
            /* width: 253px;
            height: 265px; */
            min-width: 190px;
            padding-right: 30px;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap {
            padding-bottom: 20px;
            margin-top: -10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            zoom: 0.7;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap h1 {
            margin-bottom: 20px;
            font-size: 18px;
            font-family: Source Han Sans CN;
            font-weight: bold;
            color: #333333;
            line-height: 44px;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li {
            width: 48%;
            display: flex;
            margin-bottom: 8px;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li p {
            font-size: 16px;
            font-family: Source Han Sans CN;
            font-weight: 400;
            color: #323333;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li .price {
            font-size: 16px;
            font-family: Source Han Sans CN;
            font-weight: 400;
            color: #333333;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li .prices {
            font-size: 16px;
            font-family: Source Han Sans CN;
            font-weight: 400;
            color: #B92B22;
        }

        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box .pay_methods {
            width: 66.5%;
        }

        @media screen and (max-width: 1100px) {

            .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li,
            .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box .pay_methods {
                width: 50%;
            }
        }

        .mainWrapper .reports_info .reports_info_item .xian {
            height: 1px;
            border: 1px solid #CCCCCC;
        }

        .mainWrapper .reports_info .reports_info_item .price_ {
            margin-top: 20px;
        }

        .mainWrapper .reports_info .reports_info_item .price_ ul {
            height: 40px;
            background: #F8F8F8;
            display: flex;
        }

        .mainWrapper .reports_info .reports_info_item .price_ ul li p {
            font-size: 16px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #333333;
            text-align: center;
            line-height: 40px;
        }

        .mainWrapper .reports_info .reports_info_item .price_ ul li p span {
            color: #DE4616;
        }

        .mainWrapper .m_ul_box {
            display: none;
        }

        @media screen and (max-width: 992px) {
            .reports_info .m_ul_box {
                display: block;
                margin-top: 20px;
            }

            .reports_info .m_ul_box .li_box {
                margin-bottom: 20px;
            }

            .reports_info .m_ul_box .li_box ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }

            .reports_info .m_ul_box .li_box ul li {
                display: flex;
                margin-right: 5px;
                margin-bottom: 8px;
            }

            .reports_info .m_ul_box .li_box ul li:last-child {
                margin-right: 0;
            }

            .reports_info .m_ul_box .li_box ul li p {
                font-size: 16px;
                font-family: Source Han Sans CN;
                font-weight: 400;
                color: #323333;
            }

            .reports_info .m_ul_box .li_box ul li .price {
                font-size: 16px;
                font-family: Source Han Sans CN;
                font-weight: 400;
                color: #333333;
            }

            .reports_info .m_ul_box .li_box ul li .prices {
                font-size: 16px;
                font-family: Source Han Sans CN;
                font-weight: 400;
                color: #B92B22;
            }
        }

        .tableOfContents .content {
            margin-top: 20px;
        }

        .tableOfContents .content h2 {
            position: relative;
            /* padding-bottom: 20px; */
        }

        .tableOfContents .content h2 span {
            position: absolute;
            top: 0;
            left: -8px;
            font-size: 18px;
            font-family: Source Han Sans CN;
            font-weight: bold;
            /* background: url(/uploads/pdf/biaoTiKuang_n.png); */
            background-size: 100% 100%;
            min-width: 143px;
            min-height: 30px;
            text-align: center;
            color: #fff;
        }

        .tableOfContents .cont_ p,
        .tableOfContents .cont_ .title,
        .tableOfContents .cont_ ul li {
            font-size: 16px;
            font-family: Source Han Sans CN;
            font-weight: 400;
            color: #323333;
            line-height: 30px;
            margin-left: 55px;
        }

        .tableOfContents .cont_ ul {
            padding-left: 30px;
        }

        .footers {
            background: url(/uploads/pdf/foot_n.png);
            background-size: 100%;
            height: 30px;
        }

        .footers p {
            margin-left: 60px;
            font-size: 14px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #FFFFFF;
            line-height: 30px;
        }

        /* @media screen and (max-width: 992px) {
            margin-left: 240px;
        } */
    </style>
</head>

<body>
    <div class="headerW w">
        <div class="logo_">
            <div class="logo">
                <a href="/">
                    <img style="width: 1200px;height: 100px;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/yeMei.webp" alt="logo">
                    <img style="margin-left:60px;margin-top: -92px;margin-bottom: 92px;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/logo.webp" alt="logo">
                </a>
            </div>
            <a style="z-index: 100; margin-top: -118px;margin-bottom: 118px;margin-right: -24px;" class="price_">www.globalinforesearch.com.cn</a>
        </div>
    </div>
    <div class="mainWrapper w">
        <div class="reports_info">
            <div class="reports_info_item">
                <div class="pic">
                    <img src="<?= $thumb ?>" alt="">
                </div>
                <div class="info_wrap">
                    <h1>
                        <?= $product_name ?>
                    </h1>
                    <div>
                        <ul class="ul_box">
                            <li>
                                <p>报告编号：</p>
                                <div class="price">GIR<?= $product_id ?></div>
                            </li>
                            <li>
                                <p>出版时间：</p>
                                <div class="price"><?= $published_date ?></div>
                            </li>
                            <li>
                                <p>行业类别：</p>
                                <div class="price"><?= $category_name ?></div>
                            </li>
                            <li>
                                <p>报告页数：</p>
                                <div class="price"><?= $pages ?></div>
                            </li>
                            <li>
                                <p>报告格式：</p>
                                <div class="price">
                                    <a href="#"><?= $serviceMethod ?></a>
                                </div>
                            </li>
                            <li>
                                <p>交付方式：</p>
                                <div class="price">
                                    <a href="#"><?= $payMethod ?></a>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="xian"></div>
                    <div class="price_">
                        <ul>
                            <?
                            $priceWidth = ['25', '30', '45'];
                            if ($prices && isset($prices[0]['data'])) {

                                foreach ($prices[0]['data'] as $priceIndex => $priceItem) { ?>
                                    <li style="width:<?= $priceWidth[$priceIndex] ?>%">
                                        <p><?= $priceItem['edition'] ?>: <span>￥<?= $priceItem['price'] ?></span></p>
                                    </li>
                            <?
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- 内容 -->
        <div class="tableOfContents">
            <div class="content">
                <h2 class="cont_ w">
                    <span style="z-index: 120;">内容摘要</span>
                    <img style="margin-top: -2px;z-index: 1;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/biaoTiKuang_n.png" alt="logo">
                </h2>
                <br>
                <div class="cont_">
                    <?
                    // foreach ($description as $text) {
                    # code...
                    ?>
                    <pre><?= $description ?></pre>
                    <?
                    // }
                    ?>
                </div>

            </div>
            <div class="content">
                <h2 class="cont_ w">
                    <span>报告目录</span>
                    <img style="margin-top: -2px;z-index: 1;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/biaoTiKuang_n.png" alt="logo">
                </h2>
                <br>
                <div class="cont_">
                    <pre style="color:#000"><?= $table_of_content ?></pre>
                    <?
                    if (1 == 2) {
                        foreach ($table_of_content as $key => $item) {
                            foreach ($item as $index => $row) {
                    ?>
                                <p>
                                    <?
                                    if ($index == 0) {
                                    ?>
                                        <img style="width: 20px;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/biaoTi2.png" alt="">

                                        <b><?= $row ?></b>
                                    <?

                                    } else {
                                        echo $row;
                                    }
                                    ?>

                                </p>
                    <?
                            }
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="content">
                <h2 class="cont_ w">
                    <span>报告图表</span>
                    <img style="margin-top: -2px;z-index: 1;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/biaoTiKuang_n.png" alt="logo">
                </h2>
                <br>
                <div class="cont_">
                    <pre style="color:#000"><?= $table_of_content ?></pre>
                    <?
                    if (1 == 2) {
                        foreach ($tables_and_figures as $text) { ?>
                            <p><?= $text ?></p>
                    <? }
                    }  ?>
                </div>
            </div>
            <div class="content">
                <h2 class="cont_w">
                    <span>提及的公司</span>
                    <img style="margin-top: -2px;z-index: 1;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/biaoTiKuang_n.png" alt="logo">
                </h2>
                <br>
                <div class="cont_">
                    <pre style="color:#000"><?= $companies_mentioned ?></pre>
                    <? if (1 == 2) {
                        foreach ($companies_mentioned as $text) { ?>
                            <p><?= $text ?></p>
                    <? }
                    }  ?>
                </div>
            </div>
        </div>
        <br>
    </div>
</body>

</html>