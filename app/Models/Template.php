<?php

namespace App\Models;

use App\Models\Base;

class Template extends Base {
    protected $table = 'template';
    

    public static $classificationSubCode = 'classificationSubRules';
    public static $applicationSubCode    = 'applicationSubRules';
    /**
     *
     * @param $template    模版数据
     * @param $product     报告数据
     * @param $desc        报告详情数据
     *
     * @return array|string|string[]
     */
    public static function templateWirteData($template, $product, $desc) {
        list($productArrData, $pdArrData) = self::handlerData($product, $desc);
        // TODO List 处理所有模板变量
        $tempContent = $template['content']??'';
        //过滤模版标签的换行
        $tempContent = preg_replace('/(<\/[a-zA-Z][a-zA-Z0-9]*>)\r?\n/', '$1', $tempContent);
        // 处理模板变量   {{year}}
        $tempContent = self::writeTempWord($tempContent, '{{year}}', date("Y"));
        // 处理模板变量   {{month}}
        $tempContent = self::writeTempWord($tempContent, '{{month}}', date("n"));
        // 处理模板变量   {{month_en}}
        $tempContent = self::writeTempWord($tempContent, '{{month_en}}', date("M"));
        // 处理模板变量   {{day}}
        $tempContent = self::writeTempWord($tempContent, '{{day}}', date("j"));
        // 处理模板变量   @@@@
        $tempContent = self::writeTempWord($tempContent, '@@@@', $productArrData['keywords']);
        // 处理模板变量   keywords 兼容@@@@
        $tempContent = self::writeTempWord($tempContent, '{{keywords}}', $productArrData['keywords']);
        // 处理模板变量   五种语言 keywords
        $tempContent = self::writeTempWord($tempContent, '{{keywords_cn}}', $product['keywords_cn']);
        $tempContent = self::writeTempWord($tempContent, '{{keywords_en}}', $product['keywords_en']);
        $tempContent = self::writeTempWord($tempContent, '{{keywords_jp}}', $product['keywords_jp']);
        $tempContent = self::writeTempWord($tempContent, '{{keywords_kr}}', $product['keywords_kr']);
        $tempContent = self::writeTempWord($tempContent, '{{keywords_de}}', $product['keywords_de']);
        //页数,图表
        $tempContent = self::writeTempWord($tempContent, '{{pages}}', $product['pages']);
        $tempContent = self::writeTempWord($tempContent, '{{tables}}', $product['tables']);
        //规模等数据
        $tempContent = self::writeTempWord($tempContent, '{{cagr}}', $product['cagr']);
        $tempContent = self::writeTempWord($tempContent, '{{last_year}}', $product['last_scale']);
        $tempContent = self::writeTempWord($tempContent, '{{this_year}}', $product['current_scale']);
        $tempContent = self::writeTempWord($tempContent, '{{six_year}}', $product['future_scale']);
        //跳转A标签(左右)
        $prourl = self::handlerUrl($product);
        $tempContent = self::writeTempWord(
            $tempContent, '{{link_tag_left}}', "<a href='{$prourl}' target='_blank'>"
        );
        $tempContent = self::writeTempWord($tempContent, '{{link_tag_right}}', "</a>");
        // //特殊站点独有标签
        // $scopeText = self::handlerSpecialLabels('{{scope}}', $desc['description_en']);
        // $tempContent = self::writeTempWord($tempContent, '{{scope}}', $scopeText);
        // $keyFeaturesText = self::handlerSpecialLabels('{{key_features}}', $desc['description_en']);
        // $tempContent = self::writeTempWord($tempContent, '{{key_features}}', $keyFeaturesText);
        //处理相关报告标签
        $productId = $product['id'];
        // $tempContent = self::handlerRelatedReport($tempContent, $product);
        // 处理模板变量  {{id}}
        $tempContent = self::writeTempWord($tempContent, '{{id}}', $productId);
        // 处理模板变量  {{title_en}}
        $tempContent = self::writeTempWord($tempContent, '{{title_en}}', $productArrData['english_name']);
        // 处理模板变量   {{seo_description}}
        $tempContent = self::writeTempWord($tempContent, '{{seo_description}}', $pdArrData['description_en']);
        // 处理模板变量   {{toc}}
        $tempContent = self::writeTempWord($tempContent, '{{toc}}', $pdArrData['table_of_content']);
        // 处理模板变量   {{tof}}
        $tempContent = self::writeTempWord($tempContent, '{{tof}}', $pdArrData['tables_and_figures']);
        // 处理模板变量   {{company}}   (换行)
        $replaceWords = $pdArrData['companies_mentioned'];
        $replaceWords = self::addChangeLineStr($replaceWords);
        $tempContent = self::writeTempWord($tempContent, '{{company}}', $replaceWords);
        // 处理模板变量  {{company_str}}  (不换行)
        $temp_companies_mentioned = self::handlerLineSymbol($pdArrData['companies_mentioned']);
        $tempContent = self::writeTempWord($tempContent, '{{company_str}}', $temp_companies_mentioned);
        // 处理模板变量  {{definition}}
        $tempContent = self::writeTempWord($tempContent, '{{definition}}', $pdArrData['definition']);
        // 处理模板变量  {{overview}}
        $tempContent = self::writeTempWord($tempContent, '{{overview}}', $pdArrData['overview']);
        // 处理模板变量  {{type}}   换行
        $replaceWords = $productArrData['classification'];
        $replaceWords = self::addChangeLineStr($replaceWords);
        $tempContent = self::writeTempWord($tempContent, '{{type}}', $replaceWords);
        // 处理模板变量  {{type_str}}  不换行
        $tempClassification = self::handlerLineSymbol($productArrData['classification']);
        $tempContent = self::writeTempWord($tempContent, '{{type_str}}', $tempClassification);
        // 处理模板变量  {{application}}   换行
        $replaceWords = $productArrData['application'];
        $replaceWords = self::addChangeLineStr($replaceWords);
        $tempContent = self::writeTempWord($tempContent, '{{application}}', $replaceWords);
        // 处理模板变量  {{application_str}} 不换行
        $tempApplication = self::handlerLineSymbol($productArrData['application']);
        $tempContent = self::writeTempWord($tempContent, '{{application_str}}', $tempApplication);
        // 处理模板变量  {{link}}
        $tempContent = self::writeTempWord($tempContent, '{{link}}', $productArrData['url']);
        // $tempContent = self::handlerMuchLine($tempContent);

        return $tempContent;
    }

    /**
     *
     * @param $sourceContent  string 源串
     * @param $templateVar    string 模板变量
     * @param $replaceWords   string 变量的值
     *
     * @return array|string|string[]|null
     */
    private static function writeTempWord($sourceContent, $templateVar, $replaceWords) {
        $pattern = '/'.preg_quote($templateVar).'/';
        if (!isset($replaceWords)) {
            $replaceWords = '';
        }
        if (in_array($templateVar, ['@@@@', '{{keywords}}', '{{keywords_cn}}', '{{keywords_jp}}', '{{keywords_en}}',
                                    '{{keywords_kr}}', '{{keywords_de}}'])) {
            if (empty($replaceWords)) {
                return str_replace($templateVar, '', $sourceContent);
            }
        }

        return preg_replace($pattern, $replaceWords, $sourceContent);
    }

    
    private static function handlerData($product, $desc) {
        $productArrData = [];
        //英文标题
        if (isset($product['english_name'])) {
            $productArrData['english_name'] = $product['english_name'];
        } else {
            $productArrData['english_name'] = '';
        }
        //关键字
        if (isset($product['keywords'])) {
            $productArrData['keywords'] = $product['keywords'];
        } else {
            $productArrData['keywords'] = '';
        }
        //关键字(中)
        if (isset($product['keywords_cn'])) {
            $productArrData['keywords_cn'] = $product['keywords_cn'];
        } else {
            $productArrData['keywords_cn'] = '';
        }
        //关键字(英)
        if (isset($product['keywords_en'])) {
            $productArrData['keywords_en'] = $product['keywords_en'];
        } else {
            $productArrData['keywords_en'] = '';
        }
        //关键字(日)
        if (isset($product['keywords_jp'])) {
            $productArrData['keywords_jp'] = $product['keywords_jp'];
        } else {
            $productArrData['keywords_jp'] = '';
        }
        //关键字(韩)
        if (isset($product['keywords_kr'])) {
            $productArrData['keywords_kr'] = $product['keywords_kr'];
        } else {
            $productArrData['keywords_kr'] = '';
        }
        //关键字(德)
        if (isset($product['keywords_de'])) {
            $productArrData['keywords_de'] = $product['keywords_de'];
        } else {
            $productArrData['keywords_de'] = '';
        }
        //类型
        if (!empty($product['classification'])) {
            $productArrData['classification'] = $product['classification'];
        } else {
            $productArrData['classification'] = self::handlerSubRules(
                $desc['description_en'], self::$classificationSubCode
            );
        }
        //应用
        if (!empty($product['application'])) {
            $productArrData['application'] = $product['application'];
        } else {
            $productArrData['application'] = self::handlerSubRules($desc['description'], self::$applicationSubCode);
        }
        //访问url
        $productArrData['url'] = self::getReportUrl($product);
        $pdArrData = [];
        //描述第一段
        if (!empty($desc['description_en'])) {
            $replaceWords = $desc['description'];
            //取描述第一段 ,  如果没有\n换行符就取一整段
            $strIndex = strpos($replaceWords, "\n");
            if ($strIndex !== false) {
                // 使用 substr() 函数获取第一个段落
                $pdArrData['description_en'] = substr($replaceWords, 0, $strIndex);
            } else {
                $pdArrData['description_en'] = $desc['description_en'];
            }
        } else {
            $pdArrData['description_en'] = '';
        }
        //目录
        if (isset($desc['table_of_content'])) {
            $pdArrData['table_of_content'] = self::autoIndent($desc['table_of_content']);
        } else {
            $pdArrData['table_of_content'] = '';
        }
        //企业
        if (isset($desc['companies_mentioned'])) {
            $pdArrData['companies_mentioned'] = $desc['companies_mentioned'];
        } else {
            $pdArrData['companies_mentioned'] = '';
        }
        //定义
        if (isset($desc['definition'])) {
            $pdArrData['definition'] = $desc['definition'];
        } else {
            $pdArrData['definition'] = '';
        }
        //概况
        if (isset($desc['overview'])) {
            $pdArrData['overview'] = $desc['overview'];
        } else {
            $pdArrData['overview'] = '';
        }
        //报告图表
        if (isset($desc['tables_and_figures'])) {
            $pdArrData['tables_and_figures'] = $desc['tables_and_figures'];
        } else {
            $pdArrData['tables_and_figures'] = '';
        }

        return [$productArrData, $pdArrData];
    }

    private static function handlerSubRules($description, $subRulesCode) {
        if (empty($description) || empty($subRulesCode)) {
            return '';
        }
        $systemId = System::query()->where("alias", $subRulesCode)->value("id");
        if (empty($systemId)) {
            return '';
        }
        $applicton = '';
        $rulesList = SystemValue::query()->where("parent_id", $systemId)
                                ->where("hidden", 1)
                                ->pluck("value")->toArray();
        foreach ($rulesList as $forRule) {
            $pattern = '/'.$forRule.'[\r\n]+((?:(?:\s+[^\r\n]*[\r\n]+))*)/';
            if (preg_match($pattern, $description, $matches)) {
                // 打印提取的部分
                $applicton = $matches[1];
                break;
            }
            $pattern2 = '/'.$forRule.'.*?\r?\n([\s\S]*?)(?:\r?\n\S|$)/';
            if (preg_match($pattern2, $description, $matches)) {
                // 打印提取的部分
                $applicton = $matches[1];
                break;
            }
        }

        return $applicton;
    }

    private static function autoIndent($text) {
        // 分割换行
        $lines = explode("\n", $text);
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // 匹配 ( "1.1 ", "1.2.1 ")
            if (preg_match('/^(\d+(\.\d+)*)(.*)$/', $line, $matches)) {
                $indentLevel = substr_count($matches[1], '.');
                $indentedLine = str_repeat("  ", $indentLevel).$line;
                $result[] = $indentedLine;
            } else {
                $result[] = $line; // No change if the line does not match
            }
        }

        return implode("\n", $result);
    }
    
    private static function getReportUrl($product) {
        $url = self::handlerUrl($product);
        $reportUrl = <<<EOF
<a style="word-wrap:break-word;word-break:break-all;" href="{$url}" target="_blank" rel="noopener noreferrer nofollow">{$url}</a>
EOF;

        return $reportUrl;
    }
    private static function handlerUrl($product) {
        $domain = env('DOMAIN_URL');
        if (!empty($product['url'])) {
            $url = $domain.'/reports/'.$product['id'].'/'.$product['url'];
        } else {
            $url = $domain.'/reports/'.$product['id'];
        }

        return $url;
    }

    /**
     * 添加换行符
     *
     * @param $sorceStr
     *
     * @return string
     */
    private static function addChangeLineStr($sorceStr) {
        return $sorceStr." <br/>";
    }
    

    /**
     *  处理换行符(处理为1行)
     */
    private static function handlerLineSymbol($lineStr) {
        return str_replace("\n", "、 ", $lineStr);
    }

    /**
     * 处理多行
     *
     * @param $sourceStr
     *
     * @return array|string|string[]
     */
    private static function handlerMuchLine($sourceStr) {
        return str_replace("\n", "<br/>", $sourceStr);
    }

}
