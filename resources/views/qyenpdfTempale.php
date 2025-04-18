<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>QYResearch</title>
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
    </style>
  </head>

  <body>
    <div class="PDF" style="margin: auto; width: 100%; max-width: 1200px">
      <!-- 头部 -->
      <div class="title" style="width: 100%; height: 83px; display: flex">
        <img
          src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/yeMei.webp"
          alt="yeMei"
          style="margin: 0 auto; display: block; width: 100%"
        />
      </div>
      <div
        class="top_box"
        style="
          display: flex;
          width: 90%;
          margin: 0 auto;
          margin: -32px auto 0;
          justify-content: space-between;
          align-items: flex-start;
        "
      >
        <img
          src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/QYRLogo.webp"
          alt="QYRLogo"
          style="width: 100%; max-width: 147px; height: 100%; max-height: 69px"
        />
        <div class="p_box">
          <p
            style="
              font-size: 12px;
              font-family: Roboto;
              font-weight: 400;
              color: #0c5cfe;
              line-height: 20px;
            "
          >
            <a href="" style="color: #0c5cfe; margin-right: 19px;text-decoration:none;"
              >Tel:
              <?= $adminPhone ?>
            </a>
            <a href="" style="color: #0c5cfe;text-decoration:none"
              >Email:
              <?= $adminEmail ?>
            </a>
          </p>
          <p
            style="
              font-size: 14px;
              font-family: Roboto;
              font-weight: bold;
              color: #0c5cfe;
              line-height: 20px;
              text-align: right;
              margin-top: 10px;
            "
          >
            GLOBAL LEADING MARKET REPORT PUBLISHER
          </p>
        </div>
      </div>
      <!-- 内容 -->
      <div class="content_box" style="width: 90%; margin: 20px auto">
        <div
          class="line"
          style="height: 4px; background: #e7effa; width: 100%"
        ></div>
        <div
          class="img_book"
          style="margin: 20px 0; display: flex; justify-content: center"
        >
          <img
            src="<?= $thumb ?>"
            alt="book"
            style="
              width: 100%;
              max-width: 125px;
              height: 100%;
              max-height: 218px;
              margin-right: 20px;
            "
          />
          <div
            class="left_box"
            style="
              display: flex;
              flex-direction: column;
              justify-content: space-between;
            "
          >
            <h1
              style="
                font-size: 18px;
                font-family: Roboto;
                font-weight: bold;
                color: #333333;
                line-height: 22px;
              "
            >
              <?= $product_name ?>
            </h1>
            <div
              class="xu_line"
              style="border: 1px dashed #e7effa; margin: 13px 0"
            ></div>
            <div
              class="p_span"
              style="display: flex; flex-wrap: wrap; align-items: center"
            >
              <p
                style="
                  font-size: 14px;
                  font-family: Roboto;
                  font-weight: bold;
                  color: #333333;
                  line-height: 24px;
                  margin-right: 20px;
                "
              >
                Industry:
                <span style="font-weight: 500; color: #666666"
                  ><?= $category_name ?></span
                >
              </p>
              <p
                style="
                  font-size: 14px;
                  font-family: Roboto;
                  font-weight: bold;
                  color: #333333;
                  line-height: 24px;
                  margin-right: 20px;
                "
              >
                Published Date:
                <span style="font-weight: 500; color: #666666"
                  ><?= $published_date ?></span
                >
              </p>
              <p
                style="
                  font-size: 14px;
                  font-family: Roboto;
                  font-weight: bold;
                  color: #333333;
                  line-height: 24px;
                  margin-right: 20px;
                "
              >
                Pages:
                <span style="font-weight: 500; color: #666666"
                  ><?= $pages ?>
                  Pages</span
                >
              </p>
              <p
                style="
                  font-size: 14px;
                  font-family: Roboto;
                  font-weight: bold;
                  color: #333333;
                  line-height: 24px;
                "
              >
                Report ld:<span style="font-weight: 500; color: #666666"
                  ><?= $product_id ?></span
                >
              </p>
            </div>
            <div class="prices_box" style="display: flex">
              <?php if(!empty($prices[0]['data']) && is_array($prices[0]['data'])){ ?>
                 <?php foreach($prices[0]['data'] as $price){ ?>
                      <div
                        class="box"
                        style="
                          text-align: center;
                          background: #e7effa;
                          padding: 10px 5px;
                          width: 32%;
                          margin-right: 3px;
                        "
                      >
                        <p
                          style="
                            font-size: 12px;
                            font-family: Roboto-Medium;
                            font-weight: bold;
                            color: #333333;
                          "
                        >
                             <?= $price['edition'] ?>: <br />
                             <span
                                style="
                                  font-family: Roboto;
                                  font-weight: 500;
                                  color: #cc2c25;
                                "
                             >USD
                            <?= $price['price'] ?></span
                            >
                        </p>
                      </div>
                 <?php } ?>
               <?php } ?>
            </div>
          </div>
        </div>
        <div
          class="line"
          style="height: 4px; background: #e7effa; width: 100%"
        ></div>
        <?php if (!empty($description)) { ?>
        <!-- 标题  Description-->
        <div
          class="biaoti_box"
          style="
            width: 100%;
            max-width: 243px;
            height: 34px;
            margin: 24px 0 18px;
            position: relative;
          "
        >
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTiKuang.webp"
            alt="biaoTiKuang"
            style="width: 100%; height: 100%"
          />
          <p
            style="
              font-size: 16px;
              font-family: Roboto;
              font-weight: bold;
              color: #0c5cfe;
              position: absolute;
              left: 20px;
              top: 50%;
              transform: translateY(-50%);
            "
          >
            Description
          </p>
        </div>
        <!-- 放Description下面的内容 -->
        <pre
          style="
            font-size: 15px;
            font-family: Roboto-Regular;
            font-weight: 400;
            color: #333333;
            line-height: 24px;
            white-space: pre-wrap;
            text-align: justify;
          "
        ><?= $description ?>
      </pre>
        <?php } ?>

        <?php if (!empty($table_of_content)) { ?>
        <!-- 标题 Table of Contents-->
        <div
          class="biaoti_box"
          style="
            width: 100%;
            max-width: 243px;
            height: 34px;
            margin: 24px 0 18px;
            position: relative;
          "
        >
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTiKuang.webp"
            alt="biaoTiKuang"
            style="width: 100%; height: 100%"
          />
          <p
            style="
              font-size: 16px;
              font-family: Roboto;
              font-weight: bold;
              color: #0c5cfe;
              position: absolute;
              left: 20px;
              top: 50%;
              transform: translateY(-50%);
            "
          >
            Table of Contents
          </p>
        </div>
        <!-- 放Table of Contents下面的内容 -->
        <pre
          style="
            font-size: 15px;
            font-family: Roboto-Regular;
            font-weight: 400;
            color: #333333;
            line-height: 24px;
            white-space: pre-wrap;
            text-align: justify;
          "
        ><?= $table_of_content ?>
        </pre>

        <?php } ?>

        <?php if (!empty($tables_and_figures)) { ?>
        <!-- 标题 Tables and Figures-->
        <div
          class="biaoti_box"
          style="
            width: 100%;
            max-width: 243px;
            height: 34px;
            margin: 24px 0 18px;
            position: relative;
          "
        >
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/biaoTiKuang.webp"
            alt="biaoTiKuang"
            style="width: 100%; height: 100%"
          />
          <p
            style="
              font-size: 16px;
              font-family: Roboto;
              font-weight: bold;
              color: #0c5cfe;
              position: absolute;
              left: 20px;
              top: 50%;
              transform: translateY(-50%);
            "
          >
            Tables and Figures
          </p>
        </div>
        <!-- 放Tables and Figures下面的内容 -->
        <pre
          style="
            font-size: 15px;
            font-family: Roboto-Regular;
            font-weight: 400;
            color: #333333;
            line-height: 24px;
            white-space: pre-wrap;
            text-align: justify;
          "
        >
        <?= $tables_and_figures ?>

      </pre>
        <?php } ?>


        <!-- https://www.qyresearch.com/index/detail -->

        <!--<a-->
        <!--  href="<?= $url ?>"-->
        <!--  style="-->
        <!--    font-size: 15px;-->
        <!--    font-family: Roboto;-->
        <!--    font-weight: 400;-->
        <!--    color: #333333;-->
        <!--    line-height: 24px;-->
        <!--    margin-top: 25px;-->
        <!--    display: block;-->
        <!--  "-->
        <!--  ><?= $url ?></a-->
        <!-->

      </div>
      <!-- 页脚 -->
      <!--<img-->
      <!--  src="<?= $homeUrl ?>/images/pdfTempale/yeJiao.webp"-->
      <!--  alt="yeJiao"-->
      <!--  style="display: block; width: 100%; height: 16px"-->
      <!--/>-->
    </div>
  </body>
</html>
