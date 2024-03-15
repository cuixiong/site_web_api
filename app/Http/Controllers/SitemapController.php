<?php
namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\News;
use App\Models\Products;
use App\Models\ProductsCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SitemapController extends Controller
{
    public $date = '';
    public $dir = '';
    public $domain = '';
    public $sendUrl = [];
    public $remain = ''; //百度推送剩余次数
    public $senNum = ''; //已发送数
    public $sendMessage = '';
    public $sendGoogleMessage = '';
    public $googleOpen = 0;

    public function __construct()
    {
        $this->date = date('Y-m-d', time());
        $dir = base_path() . '/public/sitemap';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }
        $this->dir = $dir;
        $this->domain = env('APP_URL','');
    }

    /**
     * 手动更新整个网站的siteMap文件
     */
    public function MakeSiteMap(Request $request)
    {
        $this
            ->clearMap()
            ->sitemapMenus()
            ->sitemapNews()
            ->sitemapHotInfo()
            ->sitemapProducts()
            ->sitemapMain()
            ->sitemapCategory();
            // ->autoUpdateSitemap() // 谷歌推送
            // ->BaiduPushSitemap(); // 百度推送
        ReturnJson(true);
    }

    // backend\controllers\FrontMenusController.php FrontMenus
    public function sitemapMenus()
    {
        $locs = [];
        $FrontMenus = Menu::select(['id', 'link', 'parent_id as pid'])->where(['is_single' => 1])->get()->toArray();
        //数组去重
        $FrontMenus = $this->toHeavy($FrontMenus);
        foreach ($FrontMenus as $value) {
            $locs[] = '/' . $value['link'];
        }
        //        echo '<pre>';print_r($locs);die;
        $str = $this->createMap($locs);

        file_put_contents($this->dir . '/' . 'page.xml', $str);

        return $this;
    }

    /*
     * 去重
     */
    public function toHeavy($data)
    {
        $i = [];
        $arr = array_column($data, 'link', 'id');
        $sear = array_search('/', $arr);

        foreach ($data as $key => &$value) {
            if (!empty($sear)) {
                if (empty($value['link'])) {
                    unset($data[$key]);
                }
            }
            if (in_array($value['link'], $i)) {
                unset($data[$key]);
            } else {
                $i[] = $value['link'];
            }
        }

        return $data;
    }

    // backend\controllers\NewsController.php sitemapNews
    public function sitemapNews()
    {
        $news = News::select(['id', 'title', 'url', 'category_id'])
            ->where('upload_at','<=',time())
            // ->where('category_id',1)
            ->get()->toArray(); //获取所有
        $locs = [];
        foreach ($news as $new) {
            if (!empty($new['url'])) {
                $locs[] = '/news' . '/' . $new['id'] . '/' . $new['url'];
            } else {
                $new['title'] = str_replace(' ', '-', $new['title']); // 把关键词里的空格转换成中划线“-”，
                $new['title'] = strtolower($new['title']);            // 再转化成小写，就是我们要的url（自定义链接）
                $locs[] = '/news' . '/' . $new['id'] . '/' . $new['title'];
            }
        }

        $str = $this->createMap($locs);

        file_put_contents($this->dir . '/' . 'news.xml', $str);

        return $this;
    }

    public function sitemapHotInfo()
    {
        $news = News::select(['id', 'title', 'url', 'category_id'])
            ->where('upload_at','<=',time())
            // ->where('category_id',1)
            ->get()->toArray(); //获取所有
        $locs = [];
        foreach ($news as $new) {
            if (!empty($new['url'])) {
                $locs[] = '/information' . '/' . $new['url'] . '/' . $new['id'];
            } else {
                $new['title'] = str_replace(' ', '-', $new['title']); // 把关键词里的空格转换成中划线“-”，
                $new['title'] = strtolower($new['title']);            // 再转化成小写，就是我们要的url（自定义链接）
                $locs[] = '/information' . '/' . $new['title'] . '/' . $new['id'];
            }
        }

        $str = $this->createMap($locs);

        file_put_contents($this->dir . '/' . 'information.xml', $str);

        return $this;
    }

    public function sitemapProducts()
    {
        ini_set('memory_limit', '-1');

        // 每次存进一个xml文件的条数(暂时注释)
        // $number = (Setting::find()->select('value')
        //     ->where(['key' => 'urlCount'])
        //     ->andWhere(['status' => 1])
        //     ->indexBy('key')->scalar()) ?? 0;

        if (empty($number)) {
            $number = 1000;
        }
        $categories = ProductsCategory::select(['id', 'link'])->get()->toArray();
        foreach ($categories as $key => $category) {
            $categories[$key]['products'] = Products::select(['id', 'url', 'category_id'])->where(['category_id' => $category['id']])->get()->toArray();
            // $number = 1000; // 每次存进一个xml文件的条数
            $file_number = 1;
            $product_count = count($categories[$key]['products']);
            // $file_number = ceil(count($categories[$key]['products'])/$number);
            for ($offset = 0; $offset < $product_count; $offset += $number) {
                $locs = [];
                foreach (array_slice($categories[$key]['products'], $offset, $number) as $product) {
                    $locs[] = '/reports/' . $product['id'] . '/' . $product['url'];
                }

                $str = $this->createMap($locs);

                file_put_contents($this->dir . '/' . $category["link"] . $file_number . '.xml', $str);
                //收取分类最新的数据提交到百度start
                $aa = ceil(count($categories[$key]['products']) / $number);
                if ($aa == $file_number) {
                    $this->sendUrl[] = $category["link"] . $file_number . '.xml';
                }
                //收取分类最新的数据提交到百度end
                $file_number += 1;
            }
        }

        return $this;
    }

    public function sitemapMain()
    {
        // $dir = Yii::getAlias('@frontend') . '/public';
        $dir = base_path() . '/public';
        $locs = array_map(function ($item) {
            if (php_sapi_name() == 'cli') {
                chmod($item, 0777);
            }
            //            return '/sitemap/' . basename($item);
            return '/' . basename($item);
        }, glob($dir . '/sitemap/*.xml'));

        $str = $this->createMap($locs);

        file_put_contents($dir . '/sitemap.xml', $str);

        if (php_sapi_name() == 'cli') {
            chmod($dir . '/sitemap.xml', 0777);
        }
        return $this;
    }

    public function createMap($map)
    {
        // 权重(暂时注释)
        // $priority = (Setting::find()->select('value')
        //     ->where(['key' => 'priority'])
        //     ->andWhere(['status' => 1])
        //     ->indexBy('key')->scalar()) ?? 0;

        if (empty($priority)) {
            $priority = 0.5;
        }
        $srep = "\n";
        $str = '<?xml version="1.0" encoding="UTF-8"?>' . $srep;
        $str .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . $srep;
        foreach ($map as $loc) {
            $str .= '<url>';
            //            $str .= '<loc><![CDATA[' . $this->domain . $loc . ']]></loc>'; // 网页地址
            $str .= '<loc>' . $this->domain . $loc . '</loc>'; // 网页地址
            $str .= '<lastmod>' . $this->date . '</lastmod>'; // 最后修改时间
            $str .= '<changefreq>daily</changefreq>'; // 更新频率，这里是每天更新
            $str .= '<priority>' . $priority . '</priority>'; // 权重
            $str .= '</url>';
            $str .= $srep;
        }
        $str .= '</urlset>';

        return $str;
    }

    public function getUpateTime()
    {
        // $model = \backend\models\Setting::findOne(['key' => 'lastUpdate']);
        // $filename = Yii::getAlias('@frontend') . '/web/sitemap.xml';
        $filename = base_path() . '/public/sitemap.xml';
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

    public function clearMap()
    {
        array_map('unlink', glob($this->dir . '/*.xml'));
        return $this;
    }

    public function sitemapCategory()
    {
        $categoryData = ProductsCategory::select(['id'])->where(['status' => 1])->get()->toArray();
        $locs = [];

        foreach ($categoryData as $item) {
            $locs[] = '/report-categories' . '/' . $item['id'];
        }

        $str = $this->createMap($locs);

        file_put_contents($this->dir . '/sitemap_' . 'category.xml', $str);

        return $this;
    }

    /**
     * 推送到google
     * @return $this
     */
    public function autoUpdateSitemap()
    {
        //判斷google推送是否開啟
        // $googleSetting = Setting::findOne(['key' => 'googleSend']);
        // $this->googleOpen = $googleSetting->status;
        $this->googleOpen = 0;

        // if ($googleSetting->status) {
        if (0) {
            $ym = $this->domain;
            $ping = 'http://www.google.com/ping?sitemap=';
            //循环读取sitemap文件名
            $apiFilename = base_path() . '/public/sitemap/';
            $dir = $apiFilename; //../
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' & $file != '..') {
                            //拼接链接
                            $url = $ping . $ym . '/sitemap/' . $file;
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
            $url = $ping . $ym . '/sitemap.xml';
            $res = $this->sendCurl($url);
            $this->sendGoogleMessage = ($res == 200) ? '谷歌推送成功' : '谷歌推送失败';
        }

        return $this;
    }

    /**
     * 发送curl
     * @param $url
     * @return mixed
     */
    public function sendCurl($url)
    {

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
     * @param $urls
     * @return bool|string
     */
    public static function sendBaiduUrl($urls)
    {
        // $baiduToken = Setting::find()->select(['value'])->where(['key' => 'baiduToken'])->one();
        // $token = $baiduToken->value;
        $token = "";
        $url = env('APP_URL');
        $api = 'http://data.zz.baidu.com/urls?site=' . $url . '&token=' . $token;

        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        return $result;
    }

    /**
     * sitemap提交到百度
     * @return void
     */
    public function baiduSendSitemap()
    {
        $frontend_domain = env('APP_URL');
        $sendUrl = $this->sendUrl;
        $siteArr = [];
        foreach ($sendUrl as $item) {
            $siteArr[] = $frontend_domain . '/' . $item;
        }

        $urls = [];
        foreach ($siteArr as $url) {
            $data = $this->get($url);
            $xml = simplexml_load_string($data);
            if (empty($data)) {
                continue;
            }
            foreach ($xml as $key => $value) {
                $urls[] = (string)$value->loc;
            }
        }
        $urls = array_slice($urls, 0, 2);
        // $baiduSetting = Setting::findOne(['key' => 'baiduSend']);
        $baiduSetting = (object) [];
        $baiduSetting->status = 0;
        $showTips = 0;
        if ($baiduSetting->status == 1 || $this->googleOpen == 1) {
            $showTips = 1;
        }
        if ($baiduSetting->status && !empty($urls)) {
            $this->sendBaiduUrl1($urls);
        }

        $message = $this->sendMessage == "over quota" ? '百度推送失败,超出限额。' : '百度推送失败。';

        $tips = empty($this->senNum) ? $message : '百度推送成功' . $this->senNum . ',剩余' . $this->remain . '条。';
        $tips .= $this->sendGoogleMessage;

        $filename = base_path() . '/public/sitemap.xml';
        if (empty($model)) {
            return ['data' => date('Y-m-d H:i:s', filemtime($filename)), 'message' => 'success', 'tips' => $tips, 'showTips' => $showTips];
        }
        if (file_exists($filename)) {
            return ['data' => date('Y-m-d H:i:s', filemtime($filename)), 'message' => 'success', 'tips' => $tips, 'showTips' => $showTips];
        } else {
            return '获取更新时间失败，网站地图.xml不存在';
        }
    }

    public function get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    public function sendBaiduUrl1($urls)
    {

        // $baiduToken = Setting::find()->select(['value'])->where(['key' => 'baiduToken'])->one();
        // $token = $baiduToken->value;
        $token = "";
        $url = env('APP_URL');

        $api = 'http://data.zz.baidu.com/urls?site=' . $url . '&token=' . $token;

        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $result = json_decode($result);
        if (!empty($result->message)) {
            $this->sendMessage = $result->message;
        }
        if (!empty($result->success)) {
            $this->senNum = $result->success;
            $this->remain = $result->remain;
        } else {
            $this->senNum = false;
            $this->remain = false;
        }
        return true;
    }

    /**
     * 文件上传生成网站地图文件
     * @param $locs
     * @return $this
     */
    public function uploadSitemap($locs, $groupIndex)
    {
        $str = $this->createMap($locs);
        $fileName = 'uploadExcel' . $groupIndex . '.xml';
        file_put_contents($this->dir . '/' . $fileName, $str);
        return $fileName;
    }

    /**
     * 提交urls到百度
     */
    public function BaiduPushSitemap($limit = 100)
    {
        $filename = base_path() . '/web/sitemap.xml';
        // 查询当天是否有超额提交的提示
        $OverQuotaCount =  SitemapLog::find()->select('message')->where(['message' => 'over_quota'])->andWhere(['>', strtotime(date('Y-m-d 00:00:00', time())), 'pushed_at'])->count();
        if ($OverQuotaCount > 0) {
            $message = '百度推送失败,超出限额。';
            return false;
        }
        // 清除sitemap日志
        $this->DeleteSiemapLog();
        $on = Setting::find()->select('status')->where(['key' => 'baiduSend'])->scalar();
        if ($on == 0) {
            //            return ['code' => 200 ,'msg' => ' 百度提交sitemap地图开关未开启'];
            return ['data' => date('Y-m-d H:i:s', filemtime($filename)), 'message' => 'success', 'tips' => '百度提交sitemap地图开关未开启', 'showTips' => 0];
        } // 百度提交sitemap地图开关未开启，则直接返回true
        // 1. 查询出需要提交的百度URL
        $lists = SitemapLog::find()->select('id,other_id,url')->where(['baidu_status' => 0])->orderBy('id', 'DESC')->limit($limit)->asArray()->all();

        // 参数必须为数组
        if (!is_array($lists)) {
            //            return ['code' => 200 ,'msg' => '没有需要提交到百度sitemap地图的URL'];
            return ['data' => date('Y-m-d H:i:s', filemtime($filename)), 'message' => 'success', 'tips' => '没有需要提交到百度sitemap地图的URL', 'showTips' => 0];
        }

        // 提交的token
        //        $token = Setting::find()->select(['value'])->where(['alias' => 'baidu_sitemap_token'])->scalar();
        $token = Setting::find()->select(['value'])->where(['key' => 'baiduToken'])->scalar();
        $request_url = 'http://data.zz.baidu.com/urls?site=' . $this->domain . '&token=' . $token;
        $successIds = []; // 成功
        $tips = '';
        $err_tips = '';
        $showTips = 1;
        foreach ($lists as $list) {
            $url = $this->domain . $list['url'];
            try {
                $res = $this->SendBaiduCurl($request_url, implode("\n", [$url]));
                $res = json_decode($res, true);
                if (isset($res['success'])) {
                    $successIds[] = $list['id'];
                    $tips = '剩余推送额度' . $res['remain'] . '条.';
                } else {
                    $status = $res['message'] == 'over quota' ? 0 : 2;
                    $err_tips = $res['message'] == 'over quota' ? '百度推送超出限额' : '百度推送失败';
                    $sql = 'UPDATE {{%sitemap_log}} SET `baidu_status` = :status,`message` = :message,`pushed_at` = :pushed_at WHERE `id` = :id ';
                    Yii::$app->db->createCommand($sql)->bindValues([':status' => $status, ':message' => $res['message'], ':pushed_at' => time(), ':id' => $list['id']])->execute();
                    break;
                }
            } catch (\Throwable $th) {
                $sql = 'UPDATE {{%sitemap_log}} SET `baidu_status` = :status,`message` = :message,`pushed_at` = :pushed_at WHERE `id` = :id ';
                Yii::$app->db->createCommand($sql)->bindValues([':status' => 2, ':message' => '请求网络错误！！', ':pushed_at' => time(), 'id' => $list['id']])->execute();
                return false;
            }
        }

        if (file_exists($filename)) {
            if (!empty($successIds)) {
                $num = count($successIds);
                foreach ($successIds as $id) {
                    $sql = 'UPDATE {{%sitemap_log}} SET `baidu_status` = :status,`message` = :message,`pushed_at` = :pushed_at WHERE `id` = :id ';
                    Yii::$app->db->createCommand($sql)->bindValues([':status' => 1, ':message' => '推送成功', ':pushed_at' => time(), 'id' => $id])->execute();
                }
                $tips = '百度：推送成功' . $num . '条,' . $tips;
            }
            $tips = $tips . $err_tips;
            return ['data' => date('Y-m-d H:i:s', filemtime($filename)), 'message' => 'success', 'tips' => $tips, 'showTips' => $showTips];
        } else {
            return '获取更新时间失败，网站地图.xml不存在';
        }
    }

    /**
     * 清除掉之前已提交的sitemap日志
     */
    public function DeleteSiemapLog()
    {
        $sql = 'DELETE FROM {{%sitemap_log}} WHERE `baidu_status` = :baidu_status AND pushed_at < :pushed_at ';
        Yii::$app->db->createCommand($sql)->bindValues(['baidu_status' => 1, 'pushed_at' => strtotime('-30 day')])->execute();
    }

    /**
     * 发生curl请求
     * @param $url 请求的接口地址
     * @param $options 请求参数
     * @param $type 返回参数 data=>数据，code => 状态码
     * @return $res 返回参数 Response
     */
    public function SendBaiduCurl($url, $options = null, $type = 'data')
    {
        try {
            // 初始化对象
            $curl = curl_init();
            // 请求时若有参数则加上携带参数
            if (!empty($options)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $options);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
            //设置抓取的url
            curl_setopt($curl, CURLOPT_URL, $url);
            // POST方式传参
            curl_setopt($curl, CURLOPT_POST, TRUE);
            //连接超时时间
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
            //超时时间
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            //执行请求
            $res = curl_exec($curl);
            if ($type == 'code') {
                $res = curl_getinfo($curl, CURLINFO_HTTP_CODE); //输出请求状态码
            }
            //关闭URL请求
            curl_close($curl);
            //返回数据
            return $res;
        } catch (\Throwable $th) {
            //throw $th;
            return false;
        }
    }
}
