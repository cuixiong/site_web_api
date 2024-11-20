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
            width: 95%;
            max-width: 1755px;
            margin: 0 auto;
        }
        .colorFD3C01 {
            color: #FD3C01 !important;
        }
        .color999 {
            color: #999999 !important;
        }
        .headerW {
            padding-top: 113px;
            margin-bottom: 30px;
            background: url('<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/top_bg.webp') no-repeat;
            /* background: url('/pdf_title.webp') no-repeat; */
            background-position: 0 bottom;
            background-size: contain;
        }
        .headerW .logo_title {
            width: 100%;
            position: relative;
        }
        .headerW .logo_title img {
            width: 100%;
            display: block;
        }
        .headerW .logo_title .logo {
            width: 20%;
            max-width: 330px;
            margin: 0 auto;
            position: absolute;
            top: -50px;
            left: 39%;
        }
        .headerW .logo_title .logo a {
            display: block;
            width: 100%;
        }
        .headerW .logo_title .logo a img {
            display: block;
            width: 100%;
        }
        .headerW .email_ {
            position: absolute;
            bottom: 50px;
            left: 4%;
            font-size: 20px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #333333;
        }
        .mainWrapper {
            padding-bottom: 100px;
        }
        .mainWrapper .reports_info {
            /* display: flex; */
            /* align-items: center;
            justify-content: space-between; */
        }
        .mainWrapper .reports_info h1 {
            margin-bottom: 20px;
            font-size: 26px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #333333;
            line-height: 35px;
        }
        .mainWrapper .reports_info .box_ {
            display: flex;
            justify-content: space-between;
        }
        .mainWrapper .reports_info .pic {
            width: 30%;
            max-width: 288px;
            margin-right: 40px;
        }
        .mainWrapper .reports_info .pic img {
            display: block;
            width: 100%;
            min-width: 188px;
        }
        .mainWrapper .reports_info .info_wrap {
          width: 100%;
            padding-bottom: 20px;
            /*padding-top: 10px;*/
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        /* .mainWrapper .reports_info .info_wrap h1 {
            margin-bottom: 20px;
            font-size: 30px;
            font-family: HarmonyOS Sans SC;
            font-weight: bold;
            color: #333333;
            line-height: 44px;
        } */
        .mainWrapper .reports_info .info_wrap .info_ p {
            margin-bottom: 20px;
        }
        .mainWrapper .reports_info .info_wrap .info_ p span {
            font-size: 16px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #333333;
            line-height: 20px;
        }
        .mainWrapper .reports_info .info_wrap .info_ .tit {
            margin-bottom: 5px;
            font-size: 16px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #333333;
            line-height: 20px;
        }
        .mainWrapper .reports_info .edition_  {
          width: 100%;
          margin-top: 10px;
        }
        .mainWrapper .reports_info .edition_  .p_title {
          font-size: 14px;
          font-family: HarmonyOS Sans SC;
          font-weight: 400;
          line-height: 22px;
        }
        .mainWrapper .reports_info .edition_  ul {
            width: 100%;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .mainWrapper .reports_info .edition_ ul li {
            /* width: 24%; */
            display: flex;
            font-size: 12px;
            font-family: HarmonyOS Sans SC;
            line-height: 22px;
            margin-right: 3px;
        }
        .mainWrapper .reports_info .edition_ ul li div {
          white-space: nowrap;
        }
        .mainWrapper .reports_info .edition_  ul li div:first-child {
          /* margin-right: 5px; */
        }
        .mainWrapper .reports_info .edition_ ul li div span {
            font-size: 12px;
        }
        .mainWrapper .tableOfContents {
            width: 100%;
            margin-top: 40px;
        }
        .mainWrapper .tableOfContents div pre {
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;
            white-space: -o-pre-wrap;
            word-wrap: break-word;
        }
        .mainWrapper .tableOfContents h2 {
            margin-bottom: 10px;
            font-size: 24px;
            font-family: HarmonyOS Sans SC;
            font-weight: 400;
            color: #384ED7;
        }
        .mainWrapper .tableOfContents .numList {
            margin-bottom: 20px;
        }
        .mainWrapper .tableOfContents .numList .fistT,
        .mainWrapper .tableOfContents .numList .secondT,
        .mainWrapper .tableOfContents .numList .thirdT li {
            font-size: 14px;
            font-family: Poppins;
            font-weight: 400;
            color: #333333;
            line-height: 28px;
        }
        .mainWrapper .tableOfContents .numList .secondT {
            padding-left: 20px;
        }
        .mainWrapper .tableOfContents .numList .thirdT li {
            padding-left: 40px;
        }
        pre {font-size: 14px; line-height: 30px; font-family: HarmonyOS Sans SC;}
    </style>
    
    </head>
    <body>
    
    <div class="headerW">
        <div class="logo_title">
            <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/pdf_title.webp" alt="logo"> 
            <div class="logo">
                <a href="/">
                     <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/logo.webp" alt="logo"> 
                </a>
            </div>
        </div>

    </div>
    <div class="mainWrapper w">
        <div class="reports_info">
            <h1>
                <?=$product_name?>
            </h1>
            <div class="box_">
                <div class="pic">
                    <img src="<?=$thumb?>" alt="研究报告">
                </div>
                <div class="info_wrap">
                    
                    <div class="info_">
                        <p>
                            <span>出版时间 :</span>
                            <span><?=$published_date?></span>
                        </p>
                        <p>
                            <span>行业 :</span>
                            <span><?=$category_name?></span>
                        </p>
                        <p>
                            <span>服务形式 :</span>
                            <span><?=$serviceMethod?></span>
                        </p>
                        <p>
                            <span>客户服务专线 :</span>
                            <span class="colorFD3C01"><?=$phone?></span>
                        </p>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($prices) && is_array($prices)){ ?>
                <?php foreach($prices as $index=>$price){ ?>
                    <div class="edition_">
                      <p class="p_title">[<?=$price['language'];?>版]:</p>
                      <ul>
                        <?php if(!empty($price['data']) && is_array($price['data'])){ ?>
                            <?php foreach($price['data'] as $key=>$value){ ?>
                                <li>
                                    <div>
                                        <?=$value['edition'];?>:RMB
                                        <span class="colorFD3C01"><?=bcsub($value['price']*$discount/100,$discount_amount,2)?></span>
                                    </div>
                                    <?php if($discount!=100 || $discount_amount!=0){ ?>
                                        <div>
                                            原价：
                                            <span class="color999">￥<?=$value['price'];?></span>
                                        </div>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        <?php } ?>
                      </ul>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
        <div class="tableOfContents">
            <div class="content">
                <h2>内容摘要</h2>
                <pre><?=$description?></pre>
            </div>
            
            <div class="content">
                <h2>报告目录</h2>
                <pre><?=$table_of_content?></pre>
            </div>

            <div class="content">
                <h2>报告图表</h2>
                <pre><?=$tables_and_figures?></pre>
            </div>
            
            <div class="content">
                <h2>提及的公司</h2>
                <pre><?=$companies_mentioned?></pre>
            </div>
        </div>
    </div>
    </body>
    </html>