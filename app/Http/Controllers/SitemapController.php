<?php

namespace App\Http\Controllers;

use App\Models\Authority;
use App\Models\Comment;
use App\Models\Information;
use App\Models\Menu;
use App\Models\News;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\SiteMapConf;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SitemapController extends Controller {
    public $date              = '';
    public $dir               = '';
    public $tempDir           = ''; // 新增：临时目录
    public $domain            = '';
    public $sendUrl           = [];
    public $remain            = '';
    public $senNum            = '';
    public $sendMessage       = '';
    public $sendGoogleMessage = '';
    public $googleOpen        = 0;

    public function __construct() {
        $this->date = date('Y-m-d', time());
        $dir = base_path().'/public/sitemap';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }
        $this->dir = $dir;

        // 创建临时目录
        $this->tempDir = $dir . '/temp_' . time();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
            chmod($this->tempDir, 0777);
        }

        $this->domain = rtrim(env('APP_URL', ''), '/');
    }

    /**
     * 手动更新整个网站的siteMap文件 - Web接口
     */
    public function MakeSiteMap(Request $request) {
        $result = $this->generateSitemap();

        if ($result['success']) {
            ReturnJson(true, $result['message']);
        } else {
            ReturnJson(false, $result['message']);
        }
    }

    /**
     * 命令行更新整个网站的siteMap文件
     */
    public function CliMakeSiteMap() {
        $result = $this->generateSitemap();

        if ($result['success']) {
            echo $result['message'] . PHP_EOL;
        } else {
            echo $result['message'] . PHP_EOL;
        }
    }

    /**
     * 核心生成逻辑 - 提取公共业务逻辑
     */
    private function generateSitemap() {
        try {
            // 第一步：在临时目录生成所有文件
            $this
                ->sitemapMenus()
                ->sitemapNews()
                ->sitemapInformation()
                ->sitemapQuote()
                ->sitemapProducts()
                ->sitemapCategory()
                ->sitemapCustom()
                ->sitemapMain();

            // 第二步：验证所有文件是否生成成功
            if ($this->validateGeneratedFiles()) {
                // 第三步：原子性替换旧文件
                $this->atomicReplaceFiles();
                return ['success' => true, 'message' => '站点地图生成成功'];
            } else {
                throw new \Exception('部分sitemap文件生成失败');
            }
        } catch (\Exception $e) {
            // 清理临时目录
            $this->cleanupTempDir();
            return ['success' => false, 'message' => '站点地图生成失败：' . $e->getMessage()];
        }
    }

    public function getSiteMapInfoByCode($code) {
        $site_map_conf_info = SiteMapConf::query()->where('code', $code)->first();
        if (empty($site_map_conf_info)) {
            return false;
        }
        if ($site_map_conf_info->status != 1) {
            return false;
        }

        //对象转数组
        return $site_map_conf_info->toArray();
    }

    // backend\controllers\FrontMenusController.php FrontMenus
    public function sitemapMenus() {
        $code = 'menu';
        $siteMapInfo = $this->getSiteMapInfoByCode($code);
        if (empty($siteMapInfo)) {
            return $this;
        }
        $xml_file_name = $siteMapInfo['xml_name'];
        $locs = [];
        $FrontMenus = Menu::select(['id', 'link', 'parent_id as pid'])->where(['is_single' => 1])->get()->toArray();

        // 修复：移除不存在的toHeavy方法，改用array_unique去重
        $FrontMenus = $this->removeDuplicateMenus($FrontMenus);

        foreach ($FrontMenus as $value) {
            $locs[] = '/'.$value['link'];
        }
        $str = $this->createMap($locs);
        // 修改：写入临时目录
        file_put_contents($this->tempDir.'/'.$xml_file_name, $str);

        return $this;
    }

    /**
     * 去除重复的菜单项
     *
     * @param array $menus
     * @return array
     */
    private function removeDuplicateMenus($menus) {
        $uniqueMenus = [];
        $seenLinks = [];

        foreach ($menus as $menu) {
            // 如果link为空，跳过
            if (empty($menu['link'])) {
                continue;
            }

            // 如果link已经存在，跳过重复项
            if (!in_array($menu['link'], $seenLinks)) {
                $seenLinks[] = $menu['link'];
                $uniqueMenus[] = $menu;
            }
        }

        return $uniqueMenus;
    }

    public function sitemapNews() {
        $code = 'news';
        $siteMapInfo = $this->getSiteMapInfoByCode($code);
        if (empty($siteMapInfo)) {
            return $this;
        }
        $link = $siteMapInfo['loc'];
        $xml_file_name = $siteMapInfo['xml_name'];
        $news = News::select(['id', 'title', 'url', 'category_id'])
                    ->where('upload_at', '<=', time())
                    ->get()->toArray();
        $locs = [];
        foreach ($news as $new) {
            if (!empty($new['url'])) {
                $new['url'] = str_replace('&', '-', $new['url']);
                $locs[] = '/'.$link.'/'.$new['id'].'/'.$new['url'];
            } else {
                $new['title'] = str_replace(' ', '-', $new['title']);
                $new['title'] = strtolower($new['title']);
                $locs[] = '/'.$link.'/'.$new['id'].'/'.$new['title'];
            }
        }
        $str = $this->createMap($locs);
        // 修改：写入临时目录
        file_put_contents($this->tempDir.'/'.$xml_file_name, $str);

        return $this;
    }

    public function sitemapInformation() {
        $code = 'information';
        $siteMapInfo = $this->getSiteMapInfoByCode($code);
        if (empty($siteMapInfo)) {
            return $this;
        }
        $link = $siteMapInfo['loc'];
        $xml_file_name = $siteMapInfo['xml_name'];
        $news = Information::select(['id', 'title', 'url', 'category_id'])
                           ->where('upload_at', '<=', time())
                           ->get()->toArray();
        $locs = [];
        foreach ($news as $new) {
            if (!empty($new['url'])) {
                $locs[] = '/'.$link.'/'.$new['id'].'/'.$new['url'];
            } else {
                $new['title'] = str_replace(' ', '-', $new['title']);
                $new['title'] = strtolower($new['title']);
                $locs[] = '/'.$link.'/'.$new['id'].'/'.$new['title'];
            }
        }
        $str = $this->createMap($locs);
        // 修改：写入临时目录
        file_put_contents($this->tempDir.'/'.$xml_file_name, $str);

        return $this;
    }

    public function sitemapQuote() {
        $code = 'quote';
        $siteMapInfo = $this->getSiteMapInfoByCode($code);
        if (empty($siteMapInfo)) {
            return $this;
        }
        $link = $siteMapInfo['loc'];
        $xml_file_name = $siteMapInfo['xml_name'];
        $news = Authority::query()->selectRaw('id, name as title , link as url,category_id')
                         ->get()->toArray();
        $locs = [];
        foreach ($news as $new) {
            $locs[] = '/'.$link.'/'.$new['id'];
        }
        $str = $this->createMap($locs);
        // 修改：写入临时目录
        file_put_contents($this->tempDir.'/'.$xml_file_name, $str);

        return $this;
    }

    public function sitemapProducts() {
        ini_set('memory_limit', '-1');
        if (empty($number)) {
            $number = 1000;
        }
        $APP_NAME = env('APP_NAME', '');
        if ($APP_NAME == 'qyen') {
            $number = 24000;
        }elseif(in_array($APP_NAME, ['mrrs', 'yhen' , 'mmgen' ,'giren' ,'lpien', 'qykr'])){
            $number = 10000;
        }
        $code = 'reports';
        $siteMapInfo = $this->getSiteMapInfoByCode($code);
        if (empty($siteMapInfo)) {
            return $this;
        }
        $link = $siteMapInfo['loc'];
        $xml_file_name = $siteMapInfo['xml_name'];
        $categories = ProductsCategory::select(['id', 'link'])->get()->toArray();
        foreach ($categories as $key => $category) {
            $categories[$key]['products'] = Products::select(['id', 'url', 'category_id'])->where(
                ['category_id' => $category['id']]
            )->get()->toArray();
            $file_number = 1;
            $product_count = count($categories[$key]['products']);
            for ($offset = 0; $offset < $product_count; $offset += $number) {
                $locs = [];
                foreach (array_slice($categories[$key]['products'], $offset, $number) as $product) {
                    $locs[] = '/'.$link.'/'.$product['id'].'/'.$product['url'];
                }
                $str = $this->createMap($locs);
                // 修改：写入临时目录
                file_put_contents($this->tempDir.'/sitemap_'.$category["link"].$file_number.'.xml', $str);
                $file_number += 1;
            }
        }

        return $this;
    }

    public function sitemapMain() {
        // 修改：从临时目录读取文件列表（排除主sitemap文件自身，避免自引用）
        $files = array_filter(glob($this->tempDir.'/*.xml'), function ($item) {
            return basename($item) !== 'sitemap.xml';
        });
        $locs = array_map(function ($item) {
            if (php_sapi_name() == 'cli') {
                chmod($item, 0777);
            }
            return '/'.basename($item);
        }, $files);

        $str = $this->createMap($locs);
        // 修改：写入临时目录
        file_put_contents($this->tempDir.'/sitemap.xml', $str);
        if (php_sapi_name() == 'cli') {
            chmod($this->tempDir.'/sitemap.xml', 0777);
        }

        return $this;
    }

    public function sitemapCategory() {
        $code = 'report-categories';
        $siteMapInfo = $this->getSiteMapInfoByCode($code);
        if (empty($siteMapInfo)) {
            return $this;
        }
        $link = $siteMapInfo['loc'];
        $xml_file_name = $siteMapInfo['xml_name'];
        $categoryData = ProductsCategory::select(['id'])->where(['status' => 1])->get()->toArray();
        $locs = [];
        foreach ($categoryData as $item) {
            $locs[] = "/".$link.'/'.$item['id'];
        }
        $str = $this->createMap($locs);
        // 修改：写入临时目录
        file_put_contents($this->tempDir.'/'.$xml_file_name, $str);

        return $this;
    }

    public function sitemapCustom() {
        $code = 'customer-reviews';
        $siteMapInfo = $this->getSiteMapInfoByCode($code);
        if (empty($siteMapInfo)) {
            return $this;
        }
        $link = $siteMapInfo['loc'];
        $xml_file_name = $siteMapInfo['xml_name'];
        $categoryData = Comment::select(['id'])->where(['status' => 1])->get()->toArray();
        $locs = [];
        foreach ($categoryData as $item) {
            $locs[] = '/'.$link.'/'.$item['id'];
        }
        $str = $this->createMap($locs);
        // 修改：写入临时目录
        file_put_contents($this->tempDir.'/'.$xml_file_name, $str);

        return $this;
    }

    /**
     * 验证生成的文件是否完整
     */
    private function validateGeneratedFiles() {
        $tempFiles = glob($this->tempDir.'/*.xml');
        if (empty($tempFiles)) {
            return false;
        }

        // 检查每个文件是否有效
        foreach ($tempFiles as $file) {
            if (filesize($file) == 0) {
                return false;
            }

            // 简单的XML格式验证
            $content = file_get_contents($file);
            if (strpos($content, '<?xml') === false || strpos($content, '</urlset>') === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 原子性替换文件
     */
    private function atomicReplaceFiles() {
        // 1. 备份当前文件（可选）
        $backupDir = $this->dir . '/backup_' . time();
        if (is_dir($this->dir) && !empty(glob($this->dir.'/*.xml'))) {
            mkdir($backupDir, 0777, true);
            foreach (glob($this->dir.'/*.xml') as $oldFile) {
                copy($oldFile, $backupDir . '/' . basename($oldFile));
            }
        }

        // 2. 清除旧文件
        array_map('unlink', glob($this->dir.'/*.xml'));

        // 3. 移动新文件到正式目录（排除主sitemap.xml，稍后单独处理）
        foreach (glob($this->tempDir.'/*.xml') as $tempFile) {
            $base = basename($tempFile);
            if ($base === 'sitemap.xml') {
                continue;
            }
            $newFile = $this->dir . '/' . $base;
            rename($tempFile, $newFile);
            if (php_sapi_name() == 'cli') {
                chmod($newFile, 0777);
            }
        }

        // 4. 特殊处理主sitemap文件：原子替换根目录文件，并复制一份到目录下
        $tempMain = $this->tempDir . '/sitemap.xml';
        if (file_exists($tempMain)) {
            $mainSitemapPath = base_path() . '/public/sitemap.xml';
            // 原子替换根目录的 sitemap.xml
            rename($tempMain, $mainSitemapPath);
            if (php_sapi_name() == 'cli') {
                chmod($mainSitemapPath, 0777);
            }
            // 同步一份到 /public/sitemap/sitemap.xml
            $dirMain = $this->dir . '/sitemap.xml';
            copy($mainSitemapPath, $dirMain);
            if (php_sapi_name() == 'cli') {
                chmod($dirMain, 0777);
            }
        }

        // 5. 清理临时目录
        $this->cleanupTempDir();

        // 6. 清理过期备份（保留最近3个）
        $this->cleanupOldBackups();
    }

    /**
     * 清理临时目录
     */
    private function cleanupTempDir() {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir.'/*'));
            rmdir($this->tempDir);
        }
    }

    /**
     * 清理过期备份
     */
    private function cleanupOldBackups() {
        $backupDirs = glob($this->dir . '/backup_*');
        if (count($backupDirs) > 3) {
            // 按时间排序，删除最旧的
            usort($backupDirs, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            $toDelete = array_slice($backupDirs, 0, -3);
            foreach ($toDelete as $dir) {
                array_map('unlink', glob($dir.'/*'));
                rmdir($dir);
            }
        }
    }

    public function createMap($map) {
        // 权重(暂时注释)
        // $priority = (Setting::find()->select('value')
        //     ->where(['key' => 'priority'])
        //     ->andWhere(['status' => 1])
        //     ->indexBy('key')->scalar()) ?? 0;
        if (empty($priority)) {
            $priority = 0.5;
        }
        $srep = "\n";
        $str = '<?xml version="1.0" encoding="UTF-8"?>'.$srep;
        $str .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$srep;
        foreach ($map as $loc) {
            $str .= '<url>';
            //            $str .= '<loc><![CDATA[' . $this->domain . $loc . ']]></loc>'; // 网页地址
            $str .= '<loc>'.$this->domain.$loc.'</loc>'; // 网页地址
            $str .= '<lastmod>'.$this->date.'</lastmod>'; // 最后修改时间
            $str .= '<changefreq>daily</changefreq>'; // 更新频率，这里是每天更新
            $str .= '<priority>'.$priority.'</priority>'; // 权重
            $str .= '</url>';
            $str .= $srep;
        }
        $str .= '</urlset>';

        return $str;
    }

    public function getUpateTime() {
        // $model = \backend\models\Setting::findOne(['key' => 'lastUpdate']);
        // $filename = Yii::getAlias('@frontend') . '/web/sitemap.xml';
        $filename = base_path().'/public/sitemap.xml';
        if (empty($model)) {
            return ['data' => date('Y-m-d H:i:s', filemtime($filename)), 'message' => 'success'];
        }
        if (file_exists($filename)) {
            // $model->value = date('Y-m-d H:i:s', time());
            // $model->save(false);
            return ['data' => date('Y-m-d H:i:s', filemtime($filename)), 'message' => 'success'];
        } else {
            return '获取更新时间失败，网站地图.xml不存在';
        }
    }

    // ... 其余方法保持不变 ...

    /**
     * 推送到google
     *
     * @return $this
     */
    public function autoUpdateSitemap() {
        //判斷google推送是否開啟
        // $googleSetting = Setting::findOne(['key' => 'googleSend']);
        // $this->googleOpen = $googleSetting->status;
        $this->googleOpen = 0;
        // if ($googleSetting->status) {
        if (0) {
            $ym = $this->domain;
            $ping = 'http://www.google.com/ping?sitemap=';
            //循环读取sitemap文件名
            $apiFilename = base_path().'/public/sitemap/';
            $dir = $apiFilename; //../
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' & $file != '..') {
                            //拼接链接
                            $url = $ping.$ym.'/sitemap/'.$file;
                            //发送请求
                            $res = $this->sendCurl($url);
                            if ($res === 0) {
                                break;
                            }
                        }
                    }
                    closedir($dh);
                }
            }
            $url = $ping.$ym.'/sitemap.xml';
            $res = $this->sendCurl($url);
            $this->sendGoogleMessage = ($res == 200) ? '谷歌推送成功' : '谷歌推送失败';
        }

        return $this;
    }

    /**
     * 发送curl
     *
     * @param $url
     *
     * @return mixed
     */
    public function sendCurl($url) {
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //连接超时时间
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        //超时时间
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        //执行命令
        $data = curl_exec($curl);
        $res = curl_getinfo($curl, CURLINFO_HTTP_CODE); //输出请求状态码
        //关闭URL请求
        curl_close($curl);

        //显示获得的数据
        return $res;
    }

    /**
     * 上传文件时推送url
     *
     * @param $urls
     *
     * @return bool|string
     */
    public static function sendBaiduUrl($urls) {
        // $baiduToken = Setting::find()->select(['value'])->where(['key' => 'baiduToken'])->one();
        // $token = $baiduToken->value;
        $token = "";
        $url = env('APP_URL');
        $api = 'http://data.zz.baidu.com/urls?site='.$url.'&token='.$token;
        $ch = curl_init();
        $options = array(
            CURLOPT_URL            => $api,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => implode("\n", $urls),
            CURLOPT_HTTPHEADER     => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);

        return $result;
    }

    // ... 其余方法保持不变，但删除重复的方法定义 ...
}
