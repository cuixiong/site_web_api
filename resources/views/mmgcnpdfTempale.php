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
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }
        .other-ele {
            display: none;
        }

        .pdf-title {
            display: block;
        }

        .panel-sm {
            margin: 0;
            border: 1px solid #bce8f1;
        }
.PDF {
    width: 100%;
    min-width: 1123px;
    font-family: open sans;
}
    .Top {
        position: relative;
    }
    .img_1 {

         max-width: 205px;
        width: 100%;
        margin-left: 55px;
        margin-top: -30px;
    }
    .img_2 {
        width: 100%;
    }
    .top_p {
        position: absolute;
font-size: 16px;
font-family: HarmonyOS Sans SC;
font-weight: bold;
color: #197AAD;
        right: 10%;
        top: 60%;
    }
    @media screen and (max-width:1322px){
        .list {
            flex-direction: column;
            align-items: center;
        }
    }
    @media screen and(min-width:768px) and (max-width:900px) {
        .content{
            padding: 82px 30px !important;
        }
    }
    .content {
        padding: 50px 50px;
        border-top: 2px solid #A8B9C6;
    }
    /* .list {
        display: flex;
        align-items: center;
    } */
    .list {
         display: flex;
        flex-direction: column;
        justify-content: space-around;
        /*flex-direction: row;*/
        /*display: grid;*/

    }

    .img_book {
        max-width: 110px;
        max-height: 150px;
        width: 100%;
        margin-right: 20px;
    }
    @media screen and (max-width:1322px) {
        .en_text {
            margin: 20px 0;
        }
    }
    .list_text {
        margin-top: 20px;
        display: flex;
        /*flex-direction: column;*/
        justify-content: space-around;
        justify-content: flex-start;
        flex-direction: row;
    }
    /* .grid-item-1 {*/
    /*        grid-row: 1 / 3;*/
    /*    }*/
    /*.grid-item-2 {*/
    /*    grid-column: 2 / 3;*/
    /*}*/
    .list_h1 {
        font-size: 26px;
        font-weight: bold;
        color: #333;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        margin: 0;
    }
    .en_text {
        display: flex;
        margin: 5px 0;
        align-items: center;
    }
    .img_en {
        width: 100%;
        max-width: 30px;
        max-height: 30px;
        margin-right: 8px;
    }
    .en_p {
        font-size: 20px;
        font-weight: 500;
        color: #666666;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .form_text {
      display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    flex-direction: column;
    justify-content: center;
    /*align-items: center;*/
    }
    .fen {
    display: flex;
    /*justify-content: space-between;*/
    justify-content: flex-start;
    flex-direction: row;

    margin: 10px 0 10px 0;
}
span.fen_text {
    width: 220px;
    display: block;
}
    .fen_text {
        font-size: 16px;

        font-weight: 400;
        color: #333;
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
    .title {
        display: flex;
        align-items: center;
        margin-top: 40px;
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
/*line-height: 43px;*/
    background-color: #025BA0;
}
.back_color_2 {
height: 14px;
font-size: 14px;
font-family: HarmonyOS Sans SC;
font-weight: 400;
color: #333333;

    background-color: #D9ECFB;
}
.back_color_3{
    height: 14px;
font-size: 14px;
font-family: HarmonyOS Sans SC;
font-weight: 400;
color: #333333;
    background-color: #EBF4FB;
}
.back_color_4{
    background-color: #F5fbff;
    color: #fff;
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
    <div class="PDF">
        <div class="Top">
            <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/yeMei.webp" alt="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/yeMei.webp" class="img_2">
            <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/logo.webp" alt="qyrLogo" class="img_1">
            <p class="top_p">专注为企业提供资深报告，预测未来行业发展趋势</p>
        </div>
        <div class="content">
            <div class="list">
                <div class="grid-item-1">
                    <h1 class="list_h1"><?=$product_name;?></h1>
                        <div class="en_text">
                            <p class="en_p"><?=$product_english_name?></p>
                        </div>

                </div>
                <div class="grid-item-2">
                    <div class="list_text">
                        <img src="<?=$thumb;?>" alt="<?=$product_name?>" class="img_book">
                        <div class="form_text">
                            <div class="fen">
                                <div><span class="fen_text">报告编码: <?=$product_id?></span></div>
                                <div><span class="fen_text">出版时间: <?=$published_date?></span></div>
                                <div><span class="fen_text">行业分类: <?=$category_name?></span></div>

                            </div>
                            <div class="fen">
                                <div><span class="fen_text">服务方式：电子版或pdf版</span></div>
                              <div><span class="fen_text">报告页码: <?=$pages?></span></div>
                              <div><span class="fen_text">图表: <?=$tables?></span></div>
                        </div>
                        <div class="fen">
                            <div><span class="fen_text" style='white-space: nowrap;'>电话咨询:<span style='font-size:18px;font-weight:700;color:#e05a38;white-space: nowrap;'><?=$phone?></span></span></div>
                            <div><span class="fen_text" style="width:385px;margin-left: 30px;">电子邮件：<span style='font-size:18px;font-weight:700;color:#e05a38'><?=$email?></span></span></div>
                        </div>
                        </div>
                    </div>
                </div>
             </div>
            <div class="table_form">
                <div class="limiter">
                    <div class="wrap-table100">
                        <div class="table">
                            <div class="row header">
                                <div class="cell header_size back_color_1" style='
                                font-size: 16px;
                                font-family: HarmonyOS Sans SC;
                                font-weight: 400;
                                color: #fff;'>
                                    报告价格
                                </div>
                                <?php // echo '<pre>';print_r($prices);exit; ?>
                                <?php if(!empty($prices[0]['data']) && is_array($prices[0]['data'])){ ?>
                                    <?php foreach($prices[0]['data'] as $price){ ?>
                                        <div class="cell header_size back_color_2" style="font-size: 14px;
                                          font-family: HarmonyOS Sans SC;
                                          font-weight: 400;
                                          color: #333333;">
                                          <?=$price['edition']?>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>

                            <?php foreach($prices as $price){ ?>
                                <div class="row">
                                    <div class="cell row_left back_color_4" style="color: #333; text-align: center;" data-title="">
                                        <?=$price['language']?>
                                    </div>

                                    <?php foreach($price['data'] as $value){ ?>
                                        <div class="cell"  style="color: #025ba0;text-align:center;font-weight:700;" data-title="<?=$value['edition']?>">
                                            ￥ <?=$value['price']?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text_centen">
                <div class="title">
                    <img
                        src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTi.webp"
                        alt="biaoTi"
                        class="text_img"
                    />
                    <h2 class="text_h2">内容摘要</h2>
                </div>
                <pre><?=$description?></pre>

                <div class="title">
                    <img
                        src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTi.webp"
                        alt="biaoTi"
                        class="text_img"
                    />
                    <h2 class="text_h2">报告目录</h2>
                </div>
                <pre><?=$table_of_content?></pre>

                <div class="title">
                    <img
                        src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTi.webp"
                        alt="biaoTi"
                        class="text_img"
                    />
                    <h2 class="text_h2">报告图表</h2>
                </div>
                <pre><?=$tables_and_figures?></pre>
            </div>
        </div>
    </div>

    </body>
</html>
