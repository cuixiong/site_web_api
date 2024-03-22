<?php
namespace App\Models;
use App\Models\Base;
class ProductPdf extends Base
{
    public string $defaultTemplate = <<<EOF
        <!DOCTYPE html>
            <html>
                <head>
                <meta charset="utf-8">
                <title><?=\$title?></title>
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
                            <img style="width:98%;max-width:120px;" src="<?=\$thumb?>" alt="">
                        </div>
                        <div class="grid-item-2">
                            <h4><a href="<?=\$url?>"><?=\$product_name?></a></h4>
                        </div>
                        <div class="grid-item-3">
                            <!-- <div>
                                <?=\$product_id?>
                            </div> -->
                            <div>
                                报告编码:GIR<?=\$product_id?>
                            </div>
                            
                            <div>出版时间:<?=\$published_date?></div>
                            
                            <div>
                                行业类别:<?=\$category_name?>
                            </div>
                            
                            <div>报告页码:<?=\$pages?></div>
                            
                            <div>报告格式:电子版或纸质版</div>
                            
                            <div>交付方式:Email发送或EMS快递</div>
                            
                            <div>电话咨询:<?=\$phone?></div>
                            
                            <div>电子邮件:<?=\$email?></div>
                            
                            <!-- <div>图表:<?=\$tables?></div> -->
                        </div>
                    </div>
                    <div class="description">
                        <section>
                            <h4>Description</h4>
                            <pre><?=\$description?></pre>
                        </section>
                        <section>
                            <h4>Table of Content</h4>
                            <pre><?=\$table_of_content?></pre>
                        </section>
                        <section>
                            <h4>Tables and Figures</h4>
                            <pre><?=\$tables_and_figures?></pre>
                        </section>
                        <section>
                            <h4>Companies Mentioned</h4>
                            <pre><?=\$companies_mentioned?></pre>
                        </section>
                    </div>
                </div>
            </body>
        </html>
    EOF;

    public $template = null;
    public $templatePath = null;
    public $defaultTemplatePath;
    public $productId;

    public function __construct()
    {
        $this->defaultTemplatePath = resource_path('views').'/pdfTempale.php';
    }

    public function setProductId($id)
    {
        $this->productId = $id;
        return $this;
    }

    public function setTemplate($template)
    {
        $tempFile = tempnam(sys_get_temp_dir(), '');
        // $temp = fopen($tempFile, 'w');
        // fwrite($temp, $template);
        file_put_contents($tempFile, $template);
        $this->template = $template;
        $this->templatePath = $tempFile;

        return $this;
    }

    public function getTemplatePath()
    {
        if ($this->templatePath === null) {
            $this->templatePath = $this->defaultTemplatePath;
            if (!is_file($this->templatePath)) {
                file_put_contents($this->templatePath, $this->defaultTemplate);
            }
        }

        return $this->templatePath;
    }

    public function saveTemplate($template = '')
    {
        if (empty($template)) {
            $template = $this->template;
        }
        file_put_contents($this->defaultTemplatePath, $template);

        return $this;
    }

    public function getTemplate()
    {
        if ($this->template === null) {
            $templatePath = $this->getTemplatePath();
            $this->template = file_get_contents($templatePath);
        }

        return $this->template;
    }

    public function getViewData($productId)
    {
        $query = Products::from('product_routine as product')
            ->select([
                'product.id',
                'product.name',
                'product.url',
                'product.category_id',
                'product.published_date',
                'product.pages',
                'product.tables',
                'product.price',
                'product.discount',
                'product.discount_type',
                'product.discount_amount',
                'product.publisher_id',
                'product.english_name',
                'category.name as category_name',
                'category.thumb',
            ])
            ->leftJoin('product_category as category','product.category_id','=','category.id');
        if ($productId !== null) {
            $query->where('product.id',$productId);
        }
        $product = $query->first()->toArray();

        
        //目录
        $year = date('Y', strtotime($product['published_date']));
        $description = (new ProductDescription($year))->select([
            'description',
            'table_of_content',
            'tables_and_figures',
            'companies_mentioned',
        ])->where('product_id',$productId)->first()->toArray();
        $product = array_merge($product, $description ?? []);

        $adminEmail = SystemValue::where('key','siteEmail')->value('value');
        $adminPhone = SystemValue::where('key','sitePhone')->value('value');

        $product_id = $product['id'] ?? '';
        $product_url = $product['url'] ?? '';
        header('Content-type:text/html;charset = utf-8');
        // echo '<pre>';print_r($prices);exit;
        
        return [
            'title' => 'MMG-CN',
            'url' => env('APP_URL').'/reports/'.$product_id.'/'.$product_url,
            'product_id' => $product_id,
            'product_name' => $product['name'] ?? '',
            'product_english_name' => $product['english_name'] ?? '',
            'product_url' => $product_url,
            'category_id' => $product['category_id'] ?? '',
            'published_date' => isset($product['published_date']) ? date('Y-m-d', strtotime($product['published_date'])) : '',
            'pages' => $product['pages'] ?? '',
            'tables' => $product['tables'] ?? '',
            'price' => $product['price'] ?? '',
            'discount' => $product['discount'] ?? '',
            'discount_type' => $product['discount_type'] ?? '',
            'discount_amount' => $product['discount_amount'] ?? '',
            'prices' => Products::CountPrice($product['price'],$product['publisher_id']),
            'description' => isset($product['description']) ? trim($product['description']) : '',
            'table_of_content' => isset($product['table_of_content']) ? trim($product['table_of_content']) : '',
            'tables_and_figures' => isset($product['tables_and_figures']) ? trim($product['tables_and_figures']) : '',
            'companies_mentioned' => isset($product['companies_mentioned']) ?trim($product['companies_mentioned']) : '',
            'category_name' => $product['category_name'] ?? '',
            'thumb' => env('IMAGE_URL').$product['thumb'] ?? '',
            'email' => $adminEmail ?? '',
            'phone' => $adminPhone ?? '',
            

            'homeUrl' => env('APP_URL'),
            // 'homepage' => parse_url(Yii::$app->params['frontend_domain'])['host'],
        ];
    }

    /**
     * 输出 html
     */
    public function baseRender($viewData)
    {
        $viewFullPath = $this->getTemplatePath();
        try {
            ob_start();

            (function () use ($viewFullPath, $viewData) {
                extract($viewData);
                include $viewFullPath;
            })();

            $output = ob_get_clean();
        } catch(\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return $output;
    }

    /**
     * 前端生成 pdf
     */
    public function frontBuild()
    {
        $viewData = $this->getViewData($this->productId);
        $html = $this->baseRender($viewData);
        // $html .= '<script>window.print();if (alert("page will close"))window.close();</script>';

        return $html;
    }

    // 后端渲染 直接输出 pdf
    // 后端渲染 按文件下载 pdf
}
