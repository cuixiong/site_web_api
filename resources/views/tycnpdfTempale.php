<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title><?= $title ?></title>
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

      ul {
        list-style: none; /* 移除小点 */
      }
      .none_child:last-child {
        border-right: none !important;
      }
    </style>
  </head>

  <body>
    <div class="ty_pdf" style="margin: auto; width: 100%; max-width: 800px">
      <!-- 头部 -->
      <div
        class="title_box"
        style="
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 40px 0;
        "
      >
        <div style="width: 100%">
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/left.webp"
            alt="左侧图标"
            style="width: 100%; height: 25px"
          />
        </div>
        <img
          src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/TY-logo.webp"
          alt="TY_logo"
          style="
            width: 100%;
            max-width: 155px;
            height: 100%;
            max-height: 62px;
            margin: 0 10px;
          "
        />
        <div style="width: 100%">
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/right.webp"
            alt="右侧图标"
            style="width: 100%; height: 25px"
          />
        </div>
      </div>
      <div
        class="ty_width_95"
        style="width: 95%; margin: 0 auto; max-width: 800px"
      >
        <!-- 书籍内容 -->
        <div class="ty_book_box" style="width: 100%; display: flex">
          <!-- 图片 -->
          <div
            class="img_box"
            style="
              margin-right: 15px;
              padding: 15px 20px;
              background: linear-gradient(0deg, #f5faff, #e4f1ff);
            "
          >
            <img
              src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/report.webp"
              alt="书"
              style="max-width: 108px; width: 100%"
            />
          </div>
          <!-- 右侧内容 -->
          <div class="right_box">
            <h1
              style="
                font-size: 20px;
                font-family: AlibabaPuHuiTi_2_105_Heavy;
                font-weight: 105 Heavy;
                color: #333333;
              "
            >
              <?= $product_name ?>
            </h1>
            <div
              class="xian"
              style="
                width: 100%;
                height: 1px;
                background: #e2e8ec;
                margin: 20px 0;
              "
            ></div>
            <ul style="display: flex; flex-wrap: wrap">
              <li
                style="
                  font-size: 14px;
                  font-family: AlibabaPuHuiTi_2_55_Regular;
                  font-weight: 55 Regular;
                  color: #555555;
                  width: 32%;
                  text-align: left;
                  margin-bottom: 20px;
                "
              >
                报告编码：<?= $product_id ?>
              </li>
              <li
                style="
                  font-size: 14px;
                  font-family: AlibabaPuHuiTi_2_55_Regular;
                  font-weight: 55 Regular;
                  color: #555555;
                  width: 32%;
                  text-align: left;
                  margin-bottom: 20px;
                "
              >
                出版日期：<?= $published_date ?>
              </li>
              <li
                style="
                  font-size: 14px;
                  font-family: AlibabaPuHuiTi_2_55_Regular;
                  font-weight: 55 Regular;
                  color: #555555;
                  width: 32%;
                  text-align: left;
                  margin-bottom: 20px;
                "
              >
                报告页码：<?= $pages ?>页
              </li>
              <li
                style="
                  font-size: 14px;
                  font-family: AlibabaPuHuiTi_2_55_Regular;
                  font-weight: 55 Regular;
                  color: #555555;
                  width: 32%;
                  text-align: left;
                  margin-bottom: 10px;
                "
              >
                报告图表：<?= $tables ?>个
              </li>
              <li
                style="
                  font-size: 14px;
                  font-family: AlibabaPuHuiTi_2_55_Regular;
                  font-weight: 55 Regular;
                  color: #555555;
                  width: 32%;
                  text-align: left;
                  margin-bottom: 10px;
                "
              >
                服务形式：电子版或纸质版
              </li>
            </ul>
          </div>
        </div>
        <!-- 语言，版本，价格 -->
        <ul style="margin: 30px 0">
          <!-- 黑色 头部 -->
          <li
            style="
              height: 40px;
              background: #1d262d;
              display: flex;
              align-items: center;
              border-radius: 16px 16px 0px 0px;
            "
          >
            <div
              style="
                width: 20%;
                font-size: 13px;
                font-family: AlibabaPuHuiTi_2_55_Regular;
                font-weight: 55 Regular;
                color: #fff;
                text-align: center;
                line-height: 40px;
                border-right: 2px solid #bec4ca;
                height: 100%;
              "
            >
              语言/版本
            </div>
            <?php foreach ($prices[0]['data'] as $edition) { ?>
            <div
              class="none_child"
              style="
                width: 20%;
                font-size: 13px;
                font-family: AlibabaPuHuiTi_2_55_Regular;
                font-weight: 55 Regular;
                color: #fff;
                text-align: center;
                line-height: 40px;
                border-right: 2px solid #bec4ca;
                height: 100%;
              "
            >
              <?= $edition['edition'] ?>
            </div>
            <?php } ?>
          </li>
          <!-- 语言及价钱 -->
          <!-- 中文 -->
          <?php $num = 0; ?>
          <?php foreach ($prices as $item) { ?>
          <?php $num += 1; ?>
          <?php if ($num % 2 == 0) { ?>
          <li
            style="
              height: 40px;
              background: #e5f1ff;
              display: flex;
              align-items: center;
            "
          >
            <?php } else { ?>
          </li>

          <li
            style="
              height: 40px;
              background: #f5faff;
              display: flex;
              align-items: center;
            "
          >
            <?php } ?>

            <div
              style="
                width: 20%;
                font-size: 13px;
                font-family: AlibabaPuHuiTi_2_55_Regular;
                font-weight: 55 Regular;
                color: #333;
                text-align: center;
                line-height: 40px;
                border-right: 2px solid #bec4ca;
                height: 100%;
              "
            >
              <?= $item['language'] ?>
            </div>
             <?php foreach ($item['data'] as $edition) { ?>
            <div
              class="none_child"
              style="
                width: 20%;
                font-size: 13px;
                font-family: AlibabaPuHuiTi_2_55_Regular;
                font-weight: 55 Regular;
                color: #d21010;
                text-align: center;
                line-height: 40px;
                border-right: 2px solid #bec4ca;
                height: 100%;
              "
            >
              <?= '￥' . $edition['price'] ?>
            </div>
            <?php } ?>
          </li>
          <?php } ?>
        </ul>
        <!-- 内容摘要 -->
        <div
          class="ty_title"
          style="
            font-size: 16px;
            font-family: AlibabaPuHuiTi_2_95_ExtraBold;
            font-weight: bold;
            color: #1960c4;
            display: flex;
            align-items: center;
          "
        >
          内容摘要
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/zhuangshi.webp"
            alt="biaoTi"
            style="width: 20px; height: 20px"
          />
        </div>
        <!-- 内容摘要 下的内容 -->
        <pre
          style="
            font-size: 16px;
            font-family: AlibabaPuHuiTi_2_55_Regular;
            font-weight: 55 Regular;
            color: #333333;
            line-height: 34px;
            white-space: pre-wrap;
            margin: 10px 0;
          "
        >
          <?= $description ?>
        </pre>

        <!-- 报告目录 -->
        <div
          class="ty_title"
          style="
            font-size: 16px;
            font-family: AlibabaPuHuiTi_2_95_ExtraBold;
            font-weight: bold;
            color: #1960c4;
            display: flex;
            align-items: center;
          "
        >
          报告目录
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/zhuangshi.webp"
            alt="biaoTi"
            style="width: 20px; height: 20px"
          />
        </div>

        <div
          class="cont"
          style="
            font-size: 16px;
            font-family: AlibabaPuHuiTi_2_55_Regular;
            margin-top: 20px;
            line-height: 34px;
          "
        >
          <pre><?=$table_of_content?></pre>
        </div>

        <!-- 报告图表 -->
        <div
          class="ty_title"
          style="
            font-size: 16px;
            font-family: AlibabaPuHuiTi_2_95_ExtraBold;
            font-weight: bold;
            color: #1960c4;
            display: flex;
            align-items: center;
            margin-top: 20px;
          "
        >
          报告图表
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/zhuangshi.webp"
            alt="biaoTi"
            style="width: 20px; height: 20px"
          />
        </div>
        <!-- 报告图表 下的内容 -->
        <div
          class="cont"
          style="
            font-size: 16px;
            font-family: AlibabaPuHuiTi_2_55_Regular;
            margin-top: 20px;
            line-height: 34px;
          "
        >
          <pre><?=$tables_and_figures?></pre>
        </div>
      </div>
      <!-- 底部 -->
      <div
        class="ty_bottom"
        style=" background: #1d262d; position: relative;padding-left: 15px; height: 28px;margin-top: 30px;"
      >
        <div
          class="img_bottom"
          style="width: 100%; height: 30px; "
        >
          <img
            src="<?=env('IMAGE_URL');?>/site/<?=env('APP_NAME');?>/pdf/bottoms.webp"
            alt="底部图案"
            style="width: 100%; height: 30px"
          />
          <p
            style="
              font-size: 13px;
              font-family: AlibabaPuHuiTi_2_45_Light;
              font-weight: 45 Light;
              color: #666666;
              line-height: 30px;
              position: absolute;
              top: 0;
              left: 50px;
            "
          >
            <?= $url ?>
          </p>
        </div>
      </div>
    </div>
  </body>
</html>
