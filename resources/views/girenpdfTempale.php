<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
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
.headerW .logo_ {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}
.headerW .logo_ .logo_img {
  z-index: -1;
  width: 780px;
}
.headerW .logo_ .logo {
  max-width: 178px;
  margin-left: 45px;
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
  width: 40%;
  max-width: 350px;
  margin-right: 40px;
}
.mainWrapper .reports_info .reports_info_item .pic img {
  display: block;
  max-width: 170px;
  margin-left: 15px;
  width: 100%;
  height: 100%;
}
.mainWrapper .reports_info .reports_info_item .info_wrap {
  padding-bottom: 20px;
  margin-top: 5px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.mainWrapper .reports_info .reports_info_item .info_wrap h1 {
  margin-top: 10px;
  margin-bottom: 20px;
  font-size: 16px;
  font-family: Roboto;
  font-weight: bold;
  color: #333333;
  line-height: 20px;
}
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li {
  width: 32%;
  display: flex;
  margin-bottom: 8px;
}
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li p {
  font-size: 12px;
  font-family: Roboto;
  font-weight: 400;
  color: #333333;
}
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box li .price {
  font-size: 12px;
  font-family: Roboto;
  font-weight: 400;
  color: #666666;
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
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 {
  display: flex;
}
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p1,
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p2,
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p3 {
  font-size: 12px;
  font-family: Roboto;
  font-weight: bold;
  color: #333333;
}
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p1 span,
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p2 span,
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p3 span {
  font-size: 12px;
  font-family: Roboto;
  font-weight: bold;
  color: #EAA902;
}
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p1,
.mainWrapper .reports_info .reports_info_item .info_wrap .ul_box2 .x2_p2 {
  margin-right: 8px;
}
.mainWrapper .m_ul_box {
  display: none;
}
.mainWrapper .jiage {
  margin-top: 20px;
  margin-bottom: 30px;
}
.mainWrapper .jiage ul {
  display: flex;
}
.mainWrapper .jiage ul li {
  margin-right: 40px;
}
.mainWrapper .jiage ul li img {
  width: 100%;
}
.mainWrapper .jiage ul li:nth-child(4) {
  margin-right: 0px;
}
.mainWrapper .jiage ul li .dd {
  display: flex;
  margin-top: -25px;
  margin-bottom: 25px;
  justify-content: center;
}
.mainWrapper .jiage ul li .dd p {
  font-size: 13px;
  margin-right: -15px;
}
.mainWrapper .jiage ul li .dd span {
  font-size: 13px;
  margin-left: 20px;
  color: #FE5100;
  font-family: HarmonyOS Sans SC;
  font-weight: bold;
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
    font-family: HarmonyOS Sans SC;
    font-weight: 400;
    color: #323333;
  }
  .reports_info .m_ul_box .li_box ul li .price {
    font-size: 16px;
    font-family: HarmonyOS Sans SC;
    font-weight: 400;
    color: #333333;
  }
  .reports_info .m_ul_box .li_box ul li .prices {
    font-size: 16px;
    font-family: HarmonyOS Sans SC;
    font-weight: 400;
    color: #B92B22;
  }
}
.tableOfContents {
  margin-top: 30px;
}
.tableOfContents .content h2 {
  position: relative;
  margin-left: 12px;
}
.tableOfContents .content h2 span {
  position: absolute;
  top: 7px;
  left: 40px;
  font-size: 14px;
  font-family: HarmonyOS Sans SC;
  font-weight: bold;
}
.tableOfContents .cont_ pre,
.tableOfContents .cont_ .title,
.tableOfContents .cont_ ul li {
  font-size: 18px;
  font-family: HarmonyOS Sans SC;
  font-weight: 400;
  color: #323333;
  line-height: 30px;
  margin-left: 22px;
  white-space:pre-wrap;
}
.tableOfContents .cont_ ul {
  padding-left: 30px;
}
.footers {
  margin-top: 30px;
}
@media screen and (max-width: 992px) {
  margin-left: 240px;
}

</style>
</head>

<body>
    <div class="headerW">
        <div class="logo_">
            <div class="logo">
                <img class="logo_img" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/yeMei.webp" alt="">
                <p style="margin-top: -34px;margin-bottom: 34px;margin-left:55px;color: #FFFFFF;">
                    <a style="color:#ffffff;" href="https://www.globalinforesearch.com">https://www.globalinforesearch.com</a>
                </p>
            </div>
            <p style="padding-bottom: 26px;padding-right: 40px;">
                <img style="width: 95px;" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/logo.png" alt="gir">
            </p>
        </div>
    </div>
    <div class="mainWrapper w">
        <div class="reports_info">
            <div class="reports_info_item">
                <div class="pic">
                    <img src="<?= $thumb ?>" alt="研究报告">
                </div>
                <div class="info_wrap">
                    <h1>
                        <a style="color:#333333;" href="<?= $url ?>"><?= $product_name ?></a>
                    </h1>
                    <div>
                        <ul class="ul_box">
                            <li style="width: 22%;">
                                <p>Page:</p>&nbsp;
                                <div class="price"><?= $pages ?></div>
                            </li>
                            <li style="width: 37%;">
                                <p>Roboto-Regular:</p>&nbsp;
                                <div class="price"><?= $published_date ?></div>
                            </li>
                            <li style="width: 37%;">
                                <p>Category:</p>&nbsp;
                                <div class="price"><?= $category_name ?></div>
                            </li>
                            <li style="width: 22%;">
                                <p>Report No.:</p>&nbsp;
                                <div class="price"><?= $product_id ?></div>
                            </li>
                            <li style="width: 37%;">
                                <p>Phone:</p>&nbsp;
                                <div class="price"> <?= $phone ?></div>
                            </li>
                            <li style="width: 37%;">
                                <p>Email:</p>&nbsp;
                                <div class="price"><?= $email ?></div>
                            </li>
                        </ul>
                    </div>
                    <div style="width: 100%;height: 1px;border: 1px solid #D1E0EE;margin-top: 10px;margin-bottom: 15px;"></div>
                    <div class="ul_box2">
                        <?php if(!empty($prices[0]['data']) && is_array($prices[0]['data'])){ ?>
                             <?php foreach($prices[0]['data'] as $price){ ?>
                                <p class="x2_p1"><?=$price['edition']?>:
                                    <span>$<?=$price['price']?></span>
                                </p>
                             <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 内容 -->
        <? if(!empty($description)){?>
        <div class="tableOfContents">
            <div class="content">
                <h2 class="cont_ w">
                    <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTiKuang.webp" alt="">
                    <span style="color: #006ABB;">Description</span>
                </h2>
                <br>
                <div class="cont_">
                    <pre><?= $description ?></pre>
                </div>
            </div>
        </div>
        <br>
        <? } ?>

        <? if(!empty($table_of_content)){?>
        <div class="tableOfContents">
            <div class="content">
                <h2 class="cont_ w">
                    <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTiKuang.webp" alt="">
                    <span style="color: #006ABB;">Table of Content</span>
                </h2>
                <br>
                <div class="cont_">
                    <pre><?= $table_of_content ?></pre>
                </div>
            </div>
        </div>
        <br>
        <? } ?>

        <? if(!empty($tables_and_figures)){?>
        <div class="tableOfContents">
            <div class="content">
                <h2 class="cont_ w">
                    <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTiKuang.webp" alt="">
                    <span style="color: #006ABB;">Tables and Figures</span>
                </h2>
                <br>
                <div class="cont_">
                    <pre><?= $tables_and_figures ?></pre>
                </div>
            </div>
        </div>
        <br>
        <? } ?>

        <? if(!empty($companies_mentioned)){?>
        <div class="tableOfContents">
            <div class="content">
                <h2 class="cont_ w">
                    <img src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTiKuang.webp" alt="">
                    <span style="color: #006ABB;">Companies Mentioned</span>
                </h2>
                <br>
                <div class="cont_">
                    <pre><?= $companies_mentioned ?></pre>
                </div>
            </div>
        </div>
        <br>
        <? } ?>
        <div>
            <div class="footers">
                <img style="max-width: 1200px;width:100%;" src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/yeJiao.webp" alt="">
                <p style="margin-top: -40px;margin-left:5px;color: #333333;font-size: 14px;">
                    <a style="color:#333333;" href="https://www.globalinforesearch.com">
                       https://www.globalinforesearch.com
                    </a>
                </p>
                <p style="margin-top: -5px;margin-right:50px;color: #FFFFFF;font-size: 14px;text-align: right;"></p>
            </div>
        </div>
    </div>
</body>

</html>
