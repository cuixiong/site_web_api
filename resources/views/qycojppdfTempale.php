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
            <div class="logo">
                <img class="logo_img" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/yeMei.webp" alt="">
                <p style="margin-top: -59px;margin-bottom: 34px;margin-left:108px;color: #FFFFFF;">
                    <img style="width: 100px;" src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/logo.webp" alt="gir">
                </p>
            </div>
            <p style="
            padding-bottom: 45px;
            padding-right: 145px;
            font-size: 18px;
            font-family: Yu Gothic;
            font-weight: bold;
            color: #FFFFFF;
            line-height: 24px;">
                産業情報サービスを提供する世界有力企業です。
            </p>
        </div>
    </div>
    <div class="title_div">
        <p class="t_p1">
            <a href="<?= $url ?>" style="color: #000000;">
                <?= $product_name ?>
            </a>
        </p>
    </div>
    <div class="title_div2">
        <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/EN.webp" alt="EN">
        <p>
            <a href="<?= $url ?>" style="color: #000000;">
                <?= $product_english_name ?>
            </a>
        </p>
    </div>
    <div class="book_div">
        <div class="book_content">
            <div class="book_l">
                <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/cover.webp" alt="cover">
            </div>
            <div class="book_r">
                <ul class="r_ul" style="margin-top: 26px;">
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">レポートID:<?= $product_id ?></p>
                    </li>
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">発表時期:<?= $published_date ?> </p>
                    </li>
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">レポート言語:英語、日本語</p>
                    </li>
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">グラフ数:<?= $tables ?> </p>
                    </li>
                </ul>
                <ul class="r_ul">
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">レポートカテゴリ:</p>
                        <span style="font-size: 16px;
                        font-family: Yu Gothic;
                        font-weight: 500;
                        color: #666666;"><?= $category_name ?></span>
                    </li>
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">ページ数:<?= $pages ?></p>
                    </li>
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">レポート形式:PDF</p>
                    </li>
                    <li class="r_li">
                        <div class="dian"></div>
                        <p class="d_p1">訪問回数:<?= $hits ?></p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="book_div2">
        <p class="p_title">販売価格（消費税別）</p>
        <?php foreach ($prices as $key => $group) { ?>
            <div class="p_div2">
                <p class="p_p1">【<?= $group['language'] ?>】</p>
                <ul>
                    <?php foreach ($group['data'] as $index => $item) { ?>
                        <li>
                            <?php if ($index == 0) { ?>
                                <p style="text-align: left;"><?= $item['edition'] ?>:USD<?= $item['usd_price'] ?>⇒換算<span style="color: #B74343"><?= $item['jpy_price'] ?>円</span></p>
                            <?php } elseif ($index == count($group['data']) - 1) { ?>

                                <p style="text-align: right;"><?= $item['edition'] ?>:USD<?= $item['usd_price'] ?>⇒換算<span style="color: #B74343"><?= $item['jpy_price'] ?>円</span></p>
                            <?php } else { ?>

                                <p style="text-align: center;"><?= $item['edition'] ?>:USD<?= $item['usd_price'] ?>⇒換算<span style="color: #B74343"><?= $item['jpy_price'] ?>円</span></p>

                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <div class="p_div3">
            <!--<p>
                 ※米ドル表示価格+10％消費税<br />
                ※納期：原則としてご注文を受けてから３営業日<br />
                ※支払方法：銀行振込、クレジットカード決済、モバイル支払い<br />
                &nbsp;&nbsp;&nbsp;個人版 貴社内で1名様のみ閲覧可能。<br />
                &nbsp;&nbsp;&nbsp;マルチユーザー版 貴社内で5名様まで閲覧可能 。<br />
                &nbsp;&nbsp;&nbsp;企業版 貴社及び関連会社で人数制限なしに閲覧可能。<br /> 
            </p>-->
            <?= $first_consideration ?>
        </div>
    </div>

    <div class="book_div3">
        <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/biaoTiKuang.webp" alt="biaoTiKuang">
        <p>概要</p>
        <pre style="white-space: pre-wrap;
                    line-height: 25px;
                    white-space: -moz-pre-wrap;
                    white-space: -pre-wrap;
                    white-space: -o-pre-wrap;
                    word-wrap: break-word;
                    font-weight: 500;
                    font-size: 15px;
                    color: #333333;
                    font-family: 游ゴシック Medium;">
            <?= $description ?>
        </pre>
    </div>
    
    <div class="book_div3">
        <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/biaoTiKuang.webp" alt="biaoTiKuang">
        <p>概要</p>
        <pre style="white-space: pre-wrap;
                    line-height: 25px;
                    white-space: -moz-pre-wrap;
                    white-space: -pre-wrap;
                    white-space: -o-pre-wrap;
                    word-wrap: break-word;
                    font-weight: 500;
                    font-size: 15px;
                    color: #333333;
                    font-family: 游ゴシック Medium;">
            <?= $description ?>
        </pre>
    </div>
    
    <div class="book_div3">
        <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/biaoTiKuang.webp" alt="biaoTiKuang">
        <p>総目録</p>
        <pre style="white-space: pre-wrap;
                    line-height: 25px;
                    white-space: -moz-pre-wrap;
                    white-space: -pre-wrap;
                    white-space: -o-pre-wrap;
                    word-wrap: break-word;
                    font-weight: 500;
                    font-size: 15px;
                    color: #333333;
                    font-family: 游ゴシック Medium;">
            <?= $table_of_content ?>
        </pre>
    </div>
    
    <div class="book_div3">
        <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/biaoTiKuang.webp" alt="biaoTiKuang">
        <p>表と図のリスト</p>
        <pre style="white-space: pre-wrap;
                    line-height: 25px;
                    white-space: -moz-pre-wrap;
                    white-space: -pre-wrap;
                    white-space: -o-pre-wrap;
                    word-wrap: break-word;
                    font-weight: 500;
                    font-size: 15px;
                    color: #333333;
                    font-family: 游ゴシック Medium;">
            <?= $tables_and_figures ?>
        </pre>
    </div>

    <div class="book_div4">
        <img src="<?= env('IMAGE_URL'); ?>/site/<?= env('APP_NAME'); ?>/pdf/images/yeJiao.webp" alt="yeJiao">
        <p><?= $url ?></p>
    </div>
</body>

</html>