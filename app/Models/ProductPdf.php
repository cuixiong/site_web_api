<?php

namespace App\Models;

use App\Models\Base;
use Exception;

class ProductPdf extends Base {
    public $template     = null;
    public $templatePath = null;
    public $defaultTemplatePath;
    public $productId;

    public function __construct() {
        $this->defaultTemplatePath = resource_path('views').'/pdfTempale.php';
    }

    public function setProductId($id) {
        $this->productId = $id;

        return $this;
    }

    public function setTemplate($template) {
        $tempFile = tempnam(sys_get_temp_dir(), '');
        // $temp = fopen($tempFile, 'w');
        // fwrite($temp, $template);
        file_put_contents($tempFile, $template);
        $this->template = $template;
        $this->templatePath = $tempFile;

        return $this;
    }

    public function getTemplatePath() {
        $tempPath = resource_path('views').'/'.env('APP_NAME')."pdfTempale.php";
        if (file_exists($tempPath) && is_file($tempPath)) {
            $this->templatePath = $tempPath;
        }
        if ($this->templatePath === null) {
            $this->templatePath = $this->defaultTemplatePath;
            if (!is_file($this->templatePath)) {
                throw new Exception("Template is not exist", 1);
            }
        }

        return $this->templatePath;
    }

    public function saveTemplate($template = '') {
        if (empty($template)) {
            $template = $this->template;
        }
        file_put_contents($this->defaultTemplatePath, $template);

        return $this;
    }

    public function getTemplate() {
        if ($this->template === null) {
            $templatePath = $this->getTemplatePath();
            $this->template = file_get_contents($templatePath);
        }

        return $this->template;
    }

    public function getViewData($productId) {
        $query = Products::from('product_routine as product')
                         ->select([
                                      'product.id',
                                      'product.name',
                                      'product.url',
                                      'product.category_id',
                                      'product.published_date',
                                      'product.pages',
                                      'product.thumb',
                                      'product.tables',
                                      'product.price',
                                      'product.discount',
                                      'product.discount_type',
                                      'product.discount_amount',
                                      'product.publisher_id',
                                      'product.english_name',
                                      'product.hits',
                                      'category.name as category_name',
                                      'category.thumb as category_thumb',
                                  ])
                         ->leftJoin('product_category as category', 'product.category_id', '=', 'category.id');
        if ($productId !== null) {
            $query->where('product.id', $productId);
        }
        $product = $query->first()->toArray();
        //目录
        $year = date('Y', strtotime($product['published_date']));
        $description = (new ProductDescription($year))->select([
                                                                   'description',
                                                                   'table_of_content',
                                                                   'tables_and_figures',
                                                                   'companies_mentioned',
                                                                   'description_en',
                                                                   'table_of_content_en',
                                                                   'tables_and_figures_en',
                                                               ])->where('product_id', $productId)->first()->toArray();
        $product = array_merge($product, $description ?? []);
        // return $product;
        $adminEmail = SystemValue::where('key', 'siteEmail')->value('value');
        $adminPhone = SystemValue::where('key', 'sitePhone')->orderBy('id', 'desc')->value('value');
        $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
        // 服务方式/报告格式 文本
        $serviceMethod = SystemValue::where(['key' => 'Service', 'status' => 1])->value('value');
        // 支付方式/交付方式 文本
        $payMethod = SystemValue::where(['key' => 'PayMethod', 'status' => 1])->value('value');
        if (!empty($product['thumb'])) {
            $productThumb = $product['thumb'];
        } else {
            $productThumb = $product['category_thumb'] ?: $defaultImg;
        }
        $productThumb = env('IMAGE_URL').Common::cutoffSiteUploadPathPrefix($productThumb);
        $product_id = $product['id'] ?? '';
        $product_url = $product['url'] ?? '';
        header('Content-type:text/html;charset = utf-8');
        // echo '<pre>';print_r($prices);exit;
        $viewData = [
            'title'                 => env('APP_NAME'),
            'url'                   => env('APP_URL').'/reports/'.$product_id.'/'.$product_url,
            'product_id'            => $product_id,
            'product_name'          => $product['name'] ?? '',
            'product_english_name'  => $product['english_name'] ?? '',
            'product_url'           => $product_url,
            'category_id'           => $product['category_id'] ?? '',
            'published_date'        => isset($product['published_date']) ? date(
                'Y-m-d', strtotime($product['published_date'])
            ) : '',
            'pages'                 => $product['pages'] ?? '',
            'tables'                => $product['tables'] ?? '',
            'hits'                  => $product['hits'] ?? '',
            'price'                 => $product['price'] ?? '',
            'discount'              => $product['discount'] ?? '',
            'discount_type'         => $product['discount_type'] ?? '',
            'discount_amount'       => $product['discount_amount'] ?? '',
            'prices'                => Products::CountPrice($product['price'], $product['publisher_id']),
            'description'           => isset($product['description']) ? trim($product['description']) : '',
            'table_of_content'      => isset($product['table_of_content']) ? trim($product['table_of_content']) : '',
            'tables_and_figures'    => isset($product['tables_and_figures']) ? trim($product['tables_and_figures'])
                : '',
            'companies_mentioned'   => isset($product['companies_mentioned']) ? trim($product['companies_mentioned'])
                : '',
            'description_en'        => isset($product['description_en']) ? trim($product['description_en']) : '',
            'table_of_content_en'   => isset($product['table_of_content_en']) ? trim($product['table_of_content_en'])
                : '',
            'tables_and_figures_en' => isset($product['tables_and_figures_en']) ? trim(
                $product['tables_and_figures_en']
            ) : '',
            'category_name'         => $product['category_name'] ?? '',
            'thumb'                 => $productThumb,
            'email'                 => $adminEmail ?? '',
            'phone'                 => $adminPhone ?? '',
            'serviceMethod'         => !empty($serviceMethod) ? $serviceMethod : '',
            'payMethod'             => !empty($payMethod) ? $payMethod : '',
            'homeUrl'               => env('APP_URL'),
            // 'homepage' => parse_url(Yii::$app->params['frontend_domain'])['host'],
        ];
        if (checkSiteAccessData(['qycojp'])) {
            // 一些动态文本
            $firstConsiderationMenuId = Menu::where('link', 'first-consideration')->value('id');
            $firstConsiderationContent = Page::where('page_id', $firstConsiderationMenuId)->value('content');
            $viewData['first_consideration'] = $firstConsiderationContent;

            $currencyData = CurrencyConfig::query()->select(['id', 'code', 'is_first', 'exchange_rate', 'tax_rate'])
                ->get()?->toArray() ?? [];
            $viewData['prices'] = Products::CountPrice($product['price'], $product['publisher_id'], null, null, null, $currencyData);
        }
        if (checkSiteAccessData(['qyen'])) {
            //取当前报告的不同版本
            $languages = Languages::GetList();
            $priceEdition = Products::getPriceEdition([$product['publisher_id']], $languages);
            $prices_list = Products::countPriceEditionPrice($priceEdition[$product['publisher_id']], $product['price']);

            $viewData['siteName'] = SystemValue::where('key', 'siteName')->value('value');
            $viewData['adminPhone'] = $adminPhone;
            $viewData['adminEmail'] = $adminEmail;
            $viewData['pricesList'] = $prices_list;
        }

        return $viewData;
    }

    /**
     * 输出 html
     */
    public function baseRender($viewData) {
        $viewFullPath = $this->getTemplatePath();
        try {
            ob_start();
            (function () use ($viewFullPath, $viewData) {
                extract($viewData);
                include $viewFullPath;
            })();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return $output;
    }

    /**
     * 前端生成 pdf
     */
    public function frontBuild() {
        $viewData = $this->getViewData($this->productId);
        // return $viewData;
        $html = $this->baseRender($viewData);

        // $html .= '<script>window.print();if (alert("page will close"))window.close();</script>';
        return $html;
    }

    // 后端渲染 直接输出 pdf
    // 后端渲染 按文件下载 pdf
}
