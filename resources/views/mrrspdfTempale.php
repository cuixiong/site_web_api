<!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <title><?=$title?></title>
    <style>
        @page{
            size: auto A4 landscape; /* a4大小 横向打印 */
            /* margin: 3mm; 这个选项可以隐藏浏览器默认生成的页眉和页脚*/
        }
        .main {
            width: 98%;
            min-width: 1123px;
            font-family: open sans;
        }
        .grid-container {
            display: grid;
            grid-auto-flow: column;
            grid-template-columns: 120px auto;
            grid-template-rows: 25% 75%;
            place-items: center start;
            /* justify-content: space-between; */
            grid-column-gap: 20px;
        }
        .grid-item-1 {
            grid-row: 1 / 3;
        }
        .grid-item-2 {
            grid-column: 2 / 3;
        }
        .grid-item-3 {
            width: 98%;
            display: grid;
            grid-template-columns: 34% 33% 33%;
            grid-template-rows: 25% 25% 25% 25%;
            grid-column-gap: 5px;
            grid-row-gap: 6px;
        }
        .user-price {
            color: red;
        }
        .description pre {
            white-space: pre-wrap;
            hyphens: none;
            overflow-wrap: break-word;
            word-break: break-word;
            /* text-align: justify;
            text-justify: none; */
            font-family: open sans;
            font-size: 13px;
        }
        section {
            page-break-after:always;
        }

        /* 上面的css代码没用到，而是用下面的css */
        .PDF .content .main .headerWrapper {
            margin-top: 40px;
            margin-bottom: 36px;
            position: relative;
            height: 52px;
            background: url('<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/head.png') no-repeat;
        }
        .PDF .content .main .headerWrapper h1 {
            position: absolute;
            top: -68%;
            left: 0%;
        }
        .PDF .content .main .headerWrapper .contact {
                position: absolute;
            bottom: 6px;
            right: 20px;
            font-size: 16px;
            font-family: Poppins;
            font-weight: bold;
            color: #FFFFFF;
        }
        .PDF .content .main .contentWrapper .report {
            height: auto;
            margin-bottom: 40px;
            display: flex;
        }
        .PDF .content .main .contentWrapper .report .reportItem {
            width: 80%;
        }
        .PDF .content .main .contentWrapper .report .pic {width:20%;}
        .PDF .content .main .contentWrapper .report .reportItem h2 {
            font-size: 18px;
            font-family: Poppins;
            font-weight: bold;
            color: #853318;
        }
        .PDF .content .main .contentWrapper .report .reportItem .Publisher {
            font-size: 14px;
            font-family: Poppins;
            font-weight: 400;
            color: #303030;
            line-height: 24px;
        }
        .PDF .content .main .contentWrapper .report .reportItem .info {
            display: flex;
            font-family: Poppins;
            font-weight: 400;
            color: #303030;
            line-height: 24px;
        }
        .PDF .content .main .contentWrapper .report .reportItem .info li {
            margin-right: 40px;
        }
        .PDF .content .main .contentWrapper .report .reportItem .info li:last-child {
            margin: 0;
        }
        .PDF .content .main .contentWrapper .report .reportItem .Report_version h3 {
            font-size: 14px;
            font-family: Poppins;
            font-weight: bold;
            color: #303030;
            line-height: 24px;
        }
        .PDF .content .main .contentWrapper .report .reportItem .Report_version ul {
            display: flex;
        }
        .PDF .content .main .contentWrapper .report .reportItem .Report_version ul li {
            margin-right: 40px;
            font-size: 14px;
            font-family: Poppins;
            font-weight: 400;
            color: #2F283D;
        }
        .PDF .content .main .contentWrapper .report .reportItem .Report_version ul li:last-child {
            margin: 0;
        }
        .PDF .content .main .contentWrapper .report .reportItem .Report_version ul li span {
            margin-left: 5px;
            font-weight: bold;
            color: #853318;
            line-height: 20px;
        }
        .PDF .content .main .contentWrapper .Description h2 {
            width: 120px;
            height: 30px;
            margin-bottom: 10px;
            background: #853318;
            font-size: 18px;
            font-family: Poppins;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 30px;
            text-align: center;
        }
        .PDF .content .main .contentWrapper .Description ._content {
            font-size: 14px;
            font-family: Poppins;
            font-weight: 400;
            color: #303030;
            line-height: 24px;
        }
        .PDF .content .main .contentWrapper .Description ._content .mb40 {
            margin-bottom: 40px;
        }
        .PDF .content .main .contentWrapper .Description ._content ul {
            padding-left: 20px;
        }
        ul {list-style: none; padding: 0;}
    </style>

    </head>
    <body>
        <div class="PDF">
            <div class="content">
                <div class="main">
                    <div class="headerWrapper">
                        <h1>
                            <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/logo.jpg" alt="logo" width='30%'>
                        </h1>
                    </div>
                    <div class="contentWrapper">
                        <div class="report">
                            <div class="reportItem">
                                <h2>
                                    <?=$product_name?>
                                </h2>
                                <div class="Publisher">
                                    Publisher: Market Research Report Store
                                </div>
                                <ul class="info">
                                    <li>Pages: <?=$pages?></li>
                                    <li>Published Date: <?=$published_date?></li>
                                    <li>Category: <?=$category_name?></li>
                                </ul>
                                <div class="Report_version">
                                    <h3>Report version:</h3>
                                    <ul>
                                        <?php foreach($prices[0]['data'] as $index=>$price){ ?>
                                        <li><?=$price['edition'];?>: <span>$<?=number_format($price['price']);?></span> </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="pic">
                                <img src="<?php echo $thumb;?>" alt="product thumb" width='70%' style='float:right;'>
                            </div>
                        </div>

                        <div class="description">
                            <section>
                                <h4>Description</h4>
                                <pre><?=$description?></pre>
                            </section>
                            <section>
                                <h4>Table of Content</h4>
                                <pre><?=$table_of_content?></pre>
                            </section>
                            <section>
                                <h4>Tables and Figures</h4>
                                <pre><?=$tables_and_figures?></pre>
                            </section>
                            <section>
                                <h4>Companies Mentioned</h4>
                                <pre><?=$companies_mentioned?></pre>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
