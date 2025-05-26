<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>
    <style>
        @page {
            size: auto A4 landscape;
            /* a4大小 横向打印 */
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

        .grid-item-2 h4 a {
            color: #4573b1;
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
            page-break-after: always;
        }
    </style>
</head>

<body>

    <div class="main">
        <div class="grid-container">
            <div class="grid-item-1">
                <img style="width:98%;max-width:120px;" src="<?= $thumb ?>" alt="">
            </div>
            <div class="grid-item-2">
                <h4><a href="<?= $url ?>"><?= $product_name ?></a></h4>
            </div>
            <div class="grid-item-3">
                <!-- <div>
                    <?= $product_id ?>
                </div> -->

                <?php if (!empty($prices[0]['data']) && is_array($prices[0]['data'])) { ?>
                    <?php foreach ($prices[0]['data'] as $key => $price) { ?>
                        <?php if ($key == 0) { ?>
                            <div>
                                published date: <?= $published_date ?>
                            </div>
                            <div>

                            </div>
                        <?php } elseif ($key == 1) { ?>

                            <div>
                                category: <?= $category_name ?>
                            </div>
                            <div>
                                pages: <?= $pages ?> <!-- tables: <?= $tables ?> -->
                            </div>
                        <?php } elseif ($key == 2) { ?>

                            <div>
                                phone: <?= $phone ?>
                            </div>
                            <div>
                                email: <?= $email ?>
                            </div>
                        <?php } else { ?>
                            <div></div>
                            <div></div>
                        <?php } ?>


                        <div><?= $price['edition'] ?>: <span class="user-price">$<?= $price['price'] ?></span></div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <div class="description">
            <?php if (!empty($description)) { ?>
                <section>
                    <h4>Description</h4>
                    <pre><?= $description ?></pre>
                </section>
            <?php } ?>

            <?php if (!empty($table_of_content)) { ?>
                <section>
                    <h4>Table of Content</h4>
                    <pre><?= $table_of_content ?></pre>
                </section>
            <?php } ?>

            <?php if (!empty($tables_and_figures)) { ?>
                <section>
                    <h4>Tables and Figures</h4>
                    <pre><?= $tables_and_figures ?></pre>
                </section>
            <?php } ?>
            
            <?php if (!empty($companies_mentioned)) { ?>
                <section>
                    <h4>Companies Mentioned</h4>
                    <pre><?= $companies_mentioned ?></pre>
                </section>
            <?php } ?>

        </div>
    </div>
</body>

</html>