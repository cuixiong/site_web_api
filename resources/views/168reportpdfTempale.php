<!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <style>
        @page{
            size: auto A4 landscape; /* a4大小 横向打印 */
            /* margin: 3mm; 这个选项可以隐藏浏览器默认生成的页眉和页脚*/
        } 
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }
        /* // ============== */
        .table_form {
            width: 100%;
            margin: 20px 0 40px;
        }
        table {
            width: 100%;
        }
        /* // ================= */
        .line_img {
            width: 100%;
            display: block;
        }
        /* //=========== */
        .text_centen {
            margin-top: 60px;
        }
        .text_img {
            width: 100%;
            max-width: 17px;
            max-height: 23px;
            margin-right: 8px;
        }
        .text_h2 {
            font-size: 18px;
            font-weight: bold;
            color: #333333;
        }
        pre {
            overflow-wrap: break-word;
            width: 100%;
            white-space: pre-wrap;
            font-family: open sans;
            font-size: 16px;
            line-height: 26px;
            color: #666;
            margin-top: 20px;
        }
        /* //=========== */
        .footer {
        width: 100%;
        position: relative;
        height: 50px;
        background-color: #265fd1;
        }
        .fooImg {
        width: 100%;
        max-width: 2176px;
        }
        .foo_text {
        position: absolute;
        display: flex;
        justify-content: space-between;
        padding: 0 100px;
        width: 100%;
        align-items: center;
        top: 0%;
        height: 90%;           
        }    
        h2 {
        font-size: 26px;
        font-weight: 300;
        color: #FFFFFF;
        }
        .foo_p {
        font-size: 30px;
        font-weight: 500;
        color: #FFFFFF;
        }
        button {
        outline: none !important;
        border: none;
        background: transparent;
        }

        button:hover {
        cursor: pointer;
        }

        iframe {
        border: none !important;
        }

        .back_color_1 {
            font-size: 16px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #FFFFFF;
            /*background: #3079CE;*/
            border-radius: 20px 20px 0px 0px;
        }
        .back_color_2 {
        height: 14px;
        font-size: 14px;
        font-family: HarmonyOS Sans SC;
        font-weight: 400;
        /*background: #3079CE;*/
        border-radius: 20px 20px 0px 0px;
        }
        .back_color_3{
        height: 14px;
        font-size: 14px;
        font-family: HarmonyOS Sans SC;
        font-weight: 400;
        color: #333333;
        /*background-color: #EBF4FB;*/
        }
        .back_color_4{
            font-family: Source Han Sans CN;
            font-weight: bold;
            color: #333333;
            /*background: #EBF5FF;*/
            margin-right: 2px;
        }
        .back_color_5 {
            font-family: Source Han Sans CN;
            font-weight: bold;
            color: #333333;
            /*background: #F7F9FA;*/
        }

        .limiter {
        width: 100%;
        margin: 0 auto;
        }

        .container-table100 {
        width: 100%;
        min-height: 100vh;
        background: #c4d3f6;
        display: -webkit-box;
        display: -webkit-flex;
        display: -moz-box;
        display: -ms-flexbox;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        padding: 33px 30px;
        }

        .wrap-table100 {
        width: 100%;
        /*border-radius: 10px;*/
        overflow: hidden;
        }

        .table {
        width: 100%;
        display: table;
        margin: 0;
        }

        @media screen and (max-width: 768px) {
        .table {
        display: block;
        }
        }

        .row {
        display: table-row;
        background: #fff;
        }

        .row.header {
        color: #333333;
        }

        @media screen and (max-width: 768px) {
        .row {
        display: block;
        }

        .row.header {
        padding: 0;
        height: 0px;
        }

        .row.header .cell {
        display: none;
        }

        .row .cell:before {
        font-size: 20px;
        font-family: open sans;
        font-weight: bold;
        color: #333333;
        text-transform: uppercase;
        font-weight: unset !important;

        margin-bottom: 13px;
        content: attr(data-title);
        min-width: 98px;
        display: block;
        }
        }

        .cell {
        display: table-cell;
        }

        @media screen and (max-width: 768px) {
        .cell {
        display: block;
        }
        }

        .row .cell {
        font-family: open sans;
        font-size: 15px;
        color: #666666;
        line-height: 1.2;
        font-weight: unset !important;
        padding-top: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f2f2f2;
        }
        .row .row_left {
        font-size: 16px !important;
        font-family: open sans;
        font-weight: bold !important;
        color: #333333;
        width:25% !important;
        }
        .row.header .cell {
        font-family: open sans;
        font-size: 18px;
        color: #fff;
        font-weight: unset !important;
        padding-top: 15px;
        padding-bottom: 15px;
        }
        .row.header .header_size {
        font-size: 22px;
        font-family: open sans;
        font-weight: bold !important;
        color: #333333;
        text-align: center;
        }

        .row .cell:nth-child(1) {
        padding-left: 15px;
        /* border: 3px solid rgb(219 231 255); */
        width: 20% !important;
        }

        .row .cell:nth-child(2) {
        /* border: 3px solid rgb(219 231 255); */
        font-size: 16px;
        font-family: open sans;
        font-weight: 400;
        color: #fff;
        text-align: center;
        width: 20% !important;
        }

        .row .cell:nth-child(3) {
        /* border: 3px solid rgb(219 231 255); */
        font-size: 16px;
        font-family: open sans;
        font-weight: 400;
        color: #fff;
        text-align: center;
        width: 20% !important;
        }

        .row .cell:nth-child(4) {
        /* border: 3px solid rgb(219 231 255); */
        font-size: 16px;
        font-family: open sans;
        font-weight: 400;
        color: #fff;
        text-align: center;
        width: 20% !important;
        }


        .table, .row {
        width: 100% !important;
        }

        .row:hover {
        cursor: pointer;
        }

        @media (max-width: 768px) {
        .row {
        border-bottom: 1px solid #f2f2f2;
        padding-bottom: 18px;
        padding-top: 30px;
        padding-right: 15px;
        margin: 0;
        }

        .row .cell {
        border: none;
        }
        .row .cell:nth-child(1) {
        text-align: center;
        font-size: 26px;
        }

        .row .cell {
        font-family: open sans;
        font-size: 18px;
        color: #555555;
        line-height: 1.2;
        }

        .table, .row, .cell {
        width: 100% !important;
        }
        }
    </style>
    </head>
    <body>
        <div style="width: 1000px; margin: auto;">
            <div class="header" style="position: relative;">
            <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/zhuangShi.webp" style="position: absolute; top:71%; max-width: 1000px;">
            <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/logo.webp" style="width: 248px;">
            <div class="title" style="position: absolute;    max-width: 1000px; width: 100%;    bottom: -14px;font-size: 14px;font-weight: 500; color: #fff; text-align: right;padding: 0 0px 10px 0px;">www.168report.com</div>
            </div>
            <div class="body">
                <div class="body_h" style="margin-top: 20px; display: flex;">
                    <div class="img_left" style="width: 200px; height: 250px; border: 1px solid #ddd;" >
                    <img src="<?= $thumb ?>" alt="" style="width: 162px; margin:  26px 0 0 20px;">
                    </div>
                    <div class="img_right" style="margin-left: 30px; flex: 1;">
                        <div class="title" style="color: #333; font-size: 21px; font-weight: 700;"><?= $product_name ?></div>
                        <ul style="list-style: none; padding: 0; margin:  25px 0 0 0 ; display: flex;justify-content: flex-start;flex-wrap: wrap;max-width: 620px;">
                            <div style="width: 45%;">
                                <li>
                                    <div class="cont_left" style="font-size: 14px; padding: 0 10px 0 10px;margin-bottom: 30px;font-family: Source Han Sans CN;font-weight: 400;">报告编号: <?= $product_id ?></div>
                                </li>
                                <li>
                                    <div class="cont_left" style="font-size: 14px; padding: 0 10px 0 10px;margin-bottom: 30px;font-family: Source Han Sans CN;font-weight: 400;">报告页数: <?= $pages ?>; 图表数量：<?= $tables ?></div>
                                </li>
                                <li>
                                    <div class="cont_left" style="font-size: 14px; padding: 0 10px 0 10px;margin-bottom: 30px;font-family: Source Han Sans CN;font-weight: 400;">报告格式：电子版或纸质版</div>
                                </li>
                            </div>
                            <div style="width: 45%;">
                                <li>
                                    <div class="cont_left" style="font-size: 14px; padding: 0 10px 0 10px;margin-bottom: 30px;font-family: Source Han Sans CN;font-weight: 400;">出版时间：<?= $published_date ?></div>
                                </li>
                                <li>
                                    <div class="cont_left" style="font-size: 14px; padding: 0 10px 0 10px;margin-bottom: 30px;font-family: Source Han Sans CN;font-weight: 400;">行业分类：<?= $category_name ?></div>
                                </li>
                                <li>
                                    <div class="cont_left" style="font-size: 14px; padding: 0 10px 0 10px;margin-bottom: 30px;font-family: Source Han Sans CN;font-weight: 400;">支付方式：Email发送或顺丰快递</div>
                                </li>
                            </div>
                        </ul>
                    </div>
                </div>
                <!-- 版本 价格 -->
                <div class="version_box" style="margin-top: 40px;">
                    <div class="limiter">
                        <div class="wrap-table100">
                            <div class="table">
                                <div class="row header" style="margin-bottom: 20px;">
                                    <div class="cell header_size back_color_1" style='
                                    font-size: 16px;
                                    font-family: Source Han Sans CN;
                                    font-weight: bold;
                                    color: #888;'>
                                    </div>
                                    <?php if($prices[0]['data']){ ?>
                                        <?php foreach($prices[0]['data'] as $index=>$data){ ?>
                                            <div class="cell header_size back_color_2" style="font-size: 14px;
                                                font-size: 16px;
                                                font-family: Source Han Sans CN;
                                                font-weight: bold;
                                                color: #888;">   
                                                <?php echo $data['edition'];?>                           
                                            </div>
                                        <?php } ?>
                                    <?php }else{ ?>
                                        <div class="cell header_size back_color_1" style='
                                        font-size: 16px;
                                        font-family: Source Han Sans CN;
                                        font-weight: bold;
                                        color: #888;'>
                                        </div>
                                        <div class="cell header_size back_color_2" style="font-size: 14px;
                                            font-size: 16px;
                                            font-family: Source Han Sans CN;
                                            font-weight: bold;
                                            color: #888;">   
                                            PDF版                               
                                        </div>
                                        <div class="cell header_size back_color_2" style="font-size: 14px;
                                            font-size: 16px;
                                            font-family: Source Han Sans CN;
                                            font-weight: bold;
                                            color: #888;">   
                                            PDF+WORD版                               
                                        </div>
                                        <div class="cell header_size back_color_2" style="font-size: 14px;
                                            font-size: 16px;
                                            font-family: Source Han Sans CN;
                                            font-weight: bold;
                                            color: #888;">   
                                            PDF+纸质版                               
                                        </div>
                                        <div class="cell header_size back_color_2" style="font-size: 14px;
                                            font-size: 16px;
                                            font-family: Source Han Sans CN;
                                            font-weight: bold;
                                            color: #888;">   
                                            PDF+WORD+纸质版                               
                                        </div>
                                    <?php } ?>
                                </div>
                                
                                <?php if($prices){ ?>
                                    <?php foreach($prices as $index=>$price){ ?>
                                        <div class="row">
                                            <div class="cell row_left back_color_4" style="color: #333; text-align: center;" data-title="">
                                                [<?php echo $price['language'];?>]
                                            </div>
                                            <?php if($price['data']){ ?>
                                                <?php foreach($price['data'] as $key=>$value){ ?>
                                                    <div class="cell back_color_4"  style="color: #666666;text-align:center;font-weight:700;" data-title="<?=$value['edition']?>">
                                                        <?php echo $value['price'];?>元 
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end -->
                <div class="body_cont" style="margin-top: 40px;">
                    <!--内容摘要-->
                    <div class="cont_title" style="background-image: url(/uploads/pdf/biaoTi.webp);background-repeat: no-repeat;background-size: contain;width: 200;height: 35px;font-size: 14px;font-weight: 700;color: #999;line-height: 44px;padding-left: 28px;">
                        内容摘要
                    </div>
                    <div class="cont" style="font-size: 14px;margin-top: 20px;"> 
                     <pre style="color:#000"><?=$description?></pre>
                    </div>
                        <!--报告目录-->
                    <div class="cont_title" style="background-image: url(/uploads/pdf/biaoTi.webp);background-repeat: no-repeat;background-size: contain;width: 200;height: 35px;font-size: 14px;font-weight: 700;color: #999;line-height: 44px;padding-left: 28px;">
                        报告目录
                    </div>
                    <div class="cont" style="font-size: 14px;margin-top: 20px;"> 
                      <pre style="color:#000"><?=$table_of_content?></pre>
                    </div>
                    <!--报告图表-->
                    <div class="cont_title" style="background-image: url(/uploads/pdf/biaoTi.webp);background-repeat: no-repeat;background-size: contain;width: 200;height: 35px;font-size: 14px;font-weight: 700;color: #999;line-height: 44px;padding-left: 28px;">
                        报告图表
                    </div>
                    <div class="cont" style="font-size: 14px;margin-top: 20px;"> 
                      <pre style="color:#000"><?=$tables_and_figures?></pre>
                    </div>
                    <!--提及公司-->
                    <div class="cont_title" style="background-image: url(/uploads/pdf/biaoTi.webp);background-repeat: no-repeat;background-size: contain;width: 200;height: 35px;font-size: 14px;font-weight: 700;color: #999;line-height: 44px;padding-left: 28px;">
                        提及的公司
                    </div>
                    <div class="cont" style="font-size: 14px;margin-top: 20px;"> 
                        <pre style="color:#000"><?= $companies_mentioned ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>