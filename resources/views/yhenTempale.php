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
          padding-top: 20px;
        }
        .headerW .logo_ {
          display: flex;
          justify-content: space-between;
          align-items: flex-end;
          margin-bottom: 20px;
        }
        .headerW .logo_ .logo {
          max-width: 178px;
          margin-left: 58px;
        }
        .headerW .logo_ .logo a {
          display: block;
          width: 100%;
        }
        .headerW .logo_ .logo a img {
          display: block;
          width: 100%;
        }
        .headerW .pic img {
          display: block;
          width: 100%;
        }
        .mainWrapper {
          padding-bottom: 100px;
          margin-top: 20px;
        }
        .mainWrapper .reports_info .reports_info_item {
          display: flex;
          align-items: center;
        }
        .mainWrapper .reports_info .reports_info_item .pic {
          width: 40%;
          max-width: 200px;
          margin-right: 30px;
          background: #F6F7FC;
        }
        .mainWrapper .reports_info .reports_info_item .pic img {
          display: block;
          max-width: 300px;
          margin-left: 15px;
          margin-right: 15px;
          margin-top: 15px;
          margin-bottom: 15px;
          width: 165px;
          height: 180px;
        }
        .mainWrapper .reports_info .reports_info_item .info_wrap {
          padding-bottom: 20px;
          padding-top: 10px;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
        }
        .mainWrapper .reports_info .reports_info_item .info_wrap h1 {
          margin-bottom: 20px;
          font-family: Roboto;
          font-weight: bold;
          font-size: 18px;
          color: #06348B;
          line-height: 30px;
        }
        /*.mainWrapper .reports_info .reports_info_item .info_wrap .xian {*/
        /*  width: 850px;*/
        /*  height: 1px;*/
        /*  background: #EEEEEE;*/
        /*  margin-bottom: 20px;*/
        /*}*/
        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box {
          display: flex;
          flex-wrap: wrap;
          justify-content: space-between;
        }
        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li {
          width: 48%;
          display: flex;
          margin-bottom: 15px;
        }
        /*.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li:nth-child(4) {*/
        /*  width: 100%;*/
        /*}*/
        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li p {
          font-size: 16px;
          font-family: Roboto;
          font-weight: 400;
          color: #666666;
        }
        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li .price {
          font-size: 16px;
          font-family: Roboto;
          font-weight: 400;
          color: #666666;
        }
        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li .pt {
          font-size: 16px;
          font-family: Roboto;
          font-weight: bold;
          color: #333333;
          line-height: 24px;
        }
        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li .prices {
          font-size: 16px;
          font-family: Roboto;
          font-weight: bold;
          color: #333333;
          line-height: 24px;
        }
        .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box .pay_methods {
          width: 66.5%;
        }
        @media screen and (max-width: 1000px) {
          .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li,
          .mainWrapper .reports_info .reports_info_item .info_wrap .ul_box .pay_methods {
            width: 50%;
          }
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
            font-family: Roboto;
            font-weight: 400;
            color: #323333;
          }
          .reports_info .m_ul_box .li_box ul li .price {
            font-size: 16px;
            font-family: Roboto;
            font-weight: 400;
            color: #333333;
          }
          .reports_info .m_ul_box .li_box ul li .prices {
            font-size: 16px;
            font-family: Roboto;
            font-weight: 400;
            color: #B92B22;
          }
        }
        .tableOfContents .cont_ p {
          font-size: 16px;
          font-family: Roboto;
          font-weight: 400;
          color: #323333;
          line-height: 30px;
          margin-left: 10px;
        }
        .tableOfContents .cont_ span {
          font-size: 18px;
          font-family: Roboto;
          font-weight: bold;
          color: #333333;
          line-height: 24px;
        }
        .tableOfContents .content {
          margin-top: 40px;

        }
        .content pre {
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;
            white-space: -o-pre-wrap;
            word-wrap: break-word;
        }
        .tableOfContents .content h2 {
          position: relative;
          margin-bottom: 10px;
        }
        .tableOfContents .content h2 span {
          position: absolute;
          top: 0;
          left: 55px;
          font-size: 18px;
          font-family: Roboto;
          font-weight: bold;
          color: #06348B;
        }
        .tableOfContents .content h3 {
          font-size: 18px;
          font-family: Roboto;
          font-weight: bold;
          color: #333333;
          line-height: 24px;
          margin-bottom: 10px;
        }
        .footers {
          display: flex;
          justify-content: space-between;
          margin: 0px 60px 0px 60px;
          margin-top: 30px;
        }
        /*@media screen and (max-width: 992px) {*/
        /*  margin-left: 240px;*/
        /*}*/

    </style>
    </head>
    <body>

    <div class="headerW w">
        <div class="logo_">
            <div class="logo">
                <a href="/">
                    <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/yhLogo.webp" alt="logo">
                </a>
            </div>
            <a class="price">https://www.yhresearch.com</a>
        </div>
        <img style="max-width: 1100px;" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/xian2.webp" alt="xian2">
        <div class="mainWrapper w">
            <div class="reports_info">
                <div class="reports_info_item">
                    <div class="pic">
                        <img src="<?= $thumb ?>" alt="product_thumb">
                    </div>
                    <div class="info_wrap">
                        <h1>
                            <a href="<?=$url?>"><?= $product_name ?></a>
                        </h1>
                        <!-- 线 -->
                        <!--<div class="xian"></div>-->
                        <div>
                            <ul class="ul_box">
                                <li>
                                    <p>Publication Date：</p>
                                    <div class="price"><?=$published_date?></div>
                                </li>
                                <li>
                                    <p>Report Id：</p>
                                    <div class="price"><?=$product_id?></div>
                                </li>
                                <li>
                                    <p>Industry：</p>
                                    <div class="price"><?=$category_name?></div>
                                </li>
                                <li>
                                    <p>Report Page：</p>
                                    <div class="price"><?=$pages?></div>
                                </li>
                                <li>
                                    <p class="pt">Single User Price：</p>
                                    <div class="prices">USD<?=$prices[0]['data'][0]['price']?></div>
                                </li>
                                <li>
                                    <p class="pt">Multi User Price：</p>
                                    <div class="prices">USD<?=$prices[0]['data'][0]['price']?></div>
                                </li>
                                <li>
                                    <p class="pt">Enterprise Price：</p>
                                    <div class="prices">USD<?=$prices[0]['data'][0]['price']?></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 内容 -->
            <div class="tableOfContents">
                <div class="content">
                    <h2 class="cont_">
                        <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/title.webp" alt="bg_icon">
                        <span>Description</span>
                    </h2>
                    <pre>
                        <?=$description?>
                    </pre>
                </div>

                <div class="content">
                    <h2 class="cont_">
                        <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/title.webp" alt="bg_icon">
                        <span>Table of Contents</span>
                    </h2>
                    <pre>
<?=$table_of_content?>
                    </pre>
                </div>

                <div class="content">
                    <h2 class="cont_">
                        <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/title.webp" alt="bg_icon">
                        <span>Tables and Figures</span>
                    </h2>
                    <pre>
<?=$tables_and_figures?>
                    </pre>
                </div>

                <div class="content">
                    <h2 class="cont_">
                        <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/title.webp" alt="bg_icon">
                        <span>Companies Mentioned</span>
                    </h2>
                    <pre>
<?=$companies_mentioned?>
                    </pre>
                </div>
            </div>
        </div>
        <img style="max-width: 1100px;" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/xian.webp" alt="xian2">
        <div class="mainWrapper w footers">
                <p><?=$url?></p>
        </div>
    </div>

    </body>
    </html>
