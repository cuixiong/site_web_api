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
    </style>
    </head>
    <body>

    <div class="main">
        <div class="grid-container">
            <div class="grid-item-1">
                <img style="width:98%;max-width:120px;" src="<?=$thumb?>" alt="<?=$product_name?>">
            </div>
            <div class="grid-item-2">
                <h4><a href="<?=$url?>"><?=$product_name?></a></h4>
            </div>
            <div class="grid-item-3">
                <div>
                    published date: <?=$published_date?>
                </div>

                <div>report number: MMG<?=$product_id?></div>

                <div><?=$prices[0]['data'][0]['edition']?>: <span class="user-price">$<?=$prices[0]['data'][0]['price']?></span></div>

                <div>
                    category: <?=$category_name?>
                </div>

                <div>
                    pages: <?=$pages?> <!-- tables: <?=$tables?> -->
                </div>

                <div><?=$prices[0]['data'][1]['edition']?>: <span class="user-price">$<?=$prices[0]['data'][1]['price']?></span></div>

                <div>
                    phone: <?=$phone?>
                </div>

                <div>
                    email: <?=$email?>
                </div>

                <div><?=$prices[0]['data'][2]['edition']?>: <span class="user-price">$<?=$prices[0]['data'][2]['price']?></span></div>
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
    </body>
    </html>
