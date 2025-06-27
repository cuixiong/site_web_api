<?php

namespace App\Http\Controllers\Common;

use App\Const\PayConst;
use App\Jobs\HandlerEmailJob;
use App\Models\Country;
use App\Models\EmailLog;
use App\Models\Office;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrendsEmail;
use App\Models\City;
use App\Models\Common;
use App\Models\ContactUs;
use App\Models\DictionaryValue;
use App\Models\Email;
use App\Models\EmailScene;
use App\Models\Languages;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\Pay;
use App\Models\PriceEditions;
use App\Models\PriceEditionValues;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\SystemValue;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class SendEmailController extends Controller {
    public $testEmail   = '';
    public $testSendcnt = 0; //测试邮箱发送次数

    /**
     * 动态配置邮箱参数
     *
     * @param array $data 邮箱配置参数信息
     */
    private function SetConfig($data, $name = 'trends') {
        $keys = ['transport', 'host', 'port', 'encryption', 'username', 'password', 'timeout', 'local_domain'];
        foreach ($data as $key => $value) {
            if (in_array($key, $keys)) {
                Config::set('mail.mailers.'.$name.'.'.$key, $value, true);
            }
        }

        return true;
    }

    /**
     * 发送邮箱
     *
     * @param string $email        接收邮箱号
     * @param string $templet      邮箱字符串的模板
     * @param array  $data         渲染模板需要的数据
     * @param string $subject      邮箱标题
     * @param string $EmailUser    邮箱发件人
     * @param string $sendUserName 发件人昵称
     */
    private function SendEmail(
        $email,
        $templet,
        $data,
        $subject,
        $EmailUser,
        $name = 'trends',
        $SendEmailNickName = ''
    ) {
        $res = Mail::mailer($name)->to($email)->send(
            new TrendsEmail($templet, $data, $subject, $EmailUser, $SendEmailNickName)
        );

        return $res;
    }

    // 注册账号发送邮箱(验证用户邮箱是否正确)
    public function Register($id) {
        try {
            $user = User::find($id);
            $data = $user ? $user->toArray() : [];
            $data['domain'] = 'https://'.$_SERVER['SERVER_NAME'];
            $token = $data['email'].'&'.$data['id'];
            $token = $user['token'];
            // $data['token'] = base64_encode($token);
            $emailCode = 'signupToBeMember';
            // $dataQuery = [
            //     'timestamp' => time(),
            //     'randomstr' => '123',
            //     'authkey' => '123',
            //     'sign' => $data['token'],
            // ];
            // $verifyUrl = $data['domain'] . '/?verifyemail=' . $emailCode . '&' . http_build_query($dataQuery);
            $verifyUrl = $data['domain'].'/?verifyemail='.$emailCode.'&token='.$token;
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/').'/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/').'/contact-us',
                'homeUrl'      => $data['domain'],
                'backendUrl'   => $imgDomain,
                'verifyUrl'    => $verifyUrl,
                'userName'     => $data['name'],
                'area'         => City::where('id', $data['city_id'])->value('name'),
                'dateTime'     => date('Y-m-d', time()),
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                                   ->pluck('value', 'key')
                                   ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }

            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            //兼容qyen邮件字段
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                                                                                             .$data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene('register');
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            $this->handlerSendEmail($scene, $user['email'], $data, $senderEmail);

            return true;
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    // 注册账号发送邮箱(验证用户邮箱是否正确)
    public function RegisterSuccess($id) {
        try {
            $user = User::find($id);
            $data = $user ? $user->toArray() : [];
            $data['domain'] = 'https://'.$_SERVER['SERVER_NAME'];
            $token = $data['email'].'&'.$data['id'];
            $data['token'] = base64_encode($token);
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/').'/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/').'/contact-us',
                'homeUrl'      => $data['domain'],
                'backendUrl'   => $imgDomain,
                'userName'     => $data['name'],
                'area'         => City::where('id', $data['area_id'])->value('name'),
                'dateTime'     => date('Y-m-d', time()),
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail','company_address'])->pluck('value', 'key')
                                   ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                                                                                             .$data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene('registerSuccess');
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            $this->handlerSendEmail($scene, $user['email'], $data, $senderEmail);

            return true;
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    /**
     * reset password eamil send
     *
     * @param use Illuminate\Http\Request $request;
     *
     * @return response Code
     */
    public function ResetPassword($email) {
        try {
            $user = User::where('email', $email)->first();
            if (empty($user)) {
                ReturnJson(false, trans()->get('lang.eamail_undefined'));
            }
            $user = $user->toArray();
            //过期时间一天后
            $end_time = time() + 86400;
            $token = $user['email'].'&'.$user['id'].'&'.$end_time.'&'.$user['updated_at'];
            $user['token'] = encrypt($token);
            $domain = env('DOMAIN_URL', 'https://mmgcn.marketmonitorglobal.com.cn');
            $user['domain'] = $domain;
            $scene = $this->getScene('password');
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            $domain = 'http://'.$_SERVER['SERVER_NAME'];
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data = $user;
            $data['userName'] = $data['name'];
            $data['homePage'] = $domain;
            $data['myAccountUrl'] = rtrim($domain, '/').'/account/account-infor';
            $data['contactUsUrl'] = rtrim($domain, '/').'/contact-us';
            $data['homeUrl'] = $domain;
            $data['backendUrl'] = $imgDomain;
            if (checkSiteAccessData(['mrrs'])) {
                $webRoute = '/forgettenPassword/reset';
            } else {
                $webRoute = '/signIn/resetPassword';
            }
            $verifyUrl = $data['domain'].''.$webRoute.'?verifyemail=do-reset-register=&email='.$user['email']
                         .'&token='.$user['token'];
            $data['verifyUrl'] = $verifyUrl;
            $data['dateTime'] = date('Y-m-d', time());
            $data['userName'] = $user['username'];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                                   ->pluck('value', 'key')
                                   ->toArray();
            $data = array_merge($data, $siteInfo);
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                                                                                             .$data['siteEmail'] : '';
            $this->handlerSendEmail($scene, $user['email'], $data, $senderEmail);
            ReturnJson(true, trans()->get('lang.eamail_success'));
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    /**
     * 申请样本、定制报告、联系我们等表单邮件统一函数发送
     *
     * @param int id 留言id
     * @param string code 对应发邮场景的code
     *
     */
    public function sendMessageEmail($id, $code)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $addressDetail = $data['address'] ?? '';
            $country = '';
            if (!empty($data['country_id'])) {
                $country = Country::where('id', $data['country_id'])->value('name');
            }
            // $data['country'] = Country::where('id',$data['country_id'])->value('name');
            if (!empty($data['product_id'])) {
                $productsInfo = Products::query()->where("id", $data['product_id'])
                    ->select(
                        [
                            'url',
                            'thumb',
                            'name',
                            'id as product_id',
                            'published_date',
                            'category_id'
                        ]
                    )->first();
                $productsName = $productsInfo->name ?? '';
                $productLink = $this->getProductUrl($productsInfo);
            } else {
                $productsName = !empty($data['product_name']) ? $data['product_name'] : '';
                $productLink = '';
            }
            $priceEdition = '';
            if (!empty($data['price_edition'])) {
                $priceEditionRecord = PriceEditionValues::query()->select(['name', 'language_id'])->where('id', $data['price_edition'])->first();
                if ($priceEditionRecord) {
                    $priceEditionData = $priceEditionRecord->toArray();
                    $languageName = Languages::where('id', $priceEditionData['language_id'])->value('name');
                    $priceEdition =  (!empty($languageName) ? $languageName : '') . ' ' . (!empty($priceEditionRecord['name']) ? $priceEditionRecord['name'] : '');
                }
            }
            $data['province'] = City::where('id', $data['province_id'])->value('name') ?? '';
            $data['city'] = City::where('id', $data['city_id'])->value('name') ?? '';
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $addressDetail = $data['address'] ?? '';
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'department'   => $data['department'] ?? '',
                'messageAddress'      => $addressDetail,    // 地址与下方$siteInfo的address做区分
                'area'         => $data['province'] . $data['city'] . " " . $addressDetail,
                'phone'        => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => !empty($data['buy_time']) && $data['buy_time'] != 0 ? $data['buy_time'] : '',
                'content'      => $data['content'],
                'dateTime'     => date('Y-m-d'),
                'language'     => $ContactUs['language_version'] ?? '',
                'backendUrl'   => $imgDomain,
                'link'         => $productLink,
                'productsName' => $productsName,
                'priceEdition' => $priceEdition,
                'country'      => $country,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                ->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                . $data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene($code);
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.email_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.email_error'));
            }
            //邮件标题
            $scene->title = $scene->title . (!empty($productsName) ? (':' . $productsName) : '');
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            //$this->handlerSendEmail($scene, $data['email'], $data, $senderEmail);
            foreach ($emails as $email) {
                $this->handlerSendEmail($scene, $email, $data, $senderEmail);
            }

            return true;
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    // 留言
    public function Message($id, $code = 'productSample')
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $addressDetail = $data['address'] ?? '';
            $country = '';
            if (!empty($data['country_id'])) {
                $country = Country::where('id', $data['country_id'])->value('name');
            }
            //$result['country'] = DictionaryValue::GetDicOptions('Country');
            $productsName = '';
            $productLink = '';
            $categoryEmail = '';
            if (isset($data['product_id']) && !empty($data['product_id'])) {
                $productsInfo = Products::query()->where("id", $data['product_id'])
                    ->select(
                        [
                            'url',
                            'thumb',
                            'name',
                            'id as product_id',
                            'published_date',
                            'category_id'
                        ]
                    )->first();
                $productsName = !empty($productsInfo) ? $productsInfo->name : '';
                $productLink = !empty($productsInfo) ? $this->getProductUrl($productsInfo) : '';
                // 分类邮箱
                if (!empty($productsInfo)) {
                    $categoryEmail = ProductsCategory::query()->where('id', $productsInfo['category_id'])->value(
                        'email'
                    );
                }
            }
            $data['country'] = Country::where('id', $data['country_id'])->value('name');
            $data['province'] = City::where('id', $data['province_id'])->value('name') ?? '';
            $data['city'] = City::where('id', $data['city_id'])->value('name') ?? '';
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'messageAddress'      => $addressDetail,
                'area'         => $data['province'] . $data['city'],
                'phone'        => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => !empty($data['buy_time']) && $data['buy_time'] != 0 ? $data['buy_time'] : '',
                'content'      => $data['content'],
                'backendUrl'   => $imgDomain,
                'link'         => $productLink,
                'productsName' => $productsName,
                'dateTime'     => date('Y-m-d'),
                'url'          => $productLink,
                'country'      => $country,
                'language'     => $ContactUs['language_version'] ?? '',
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address', 'company_address'])
                ->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if ($sitePhones) {
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                . $data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene($code);
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            // 收件人额外加上分类邮箱
            if ($categoryEmail) {
                $categoryEmail = explode(',', $categoryEmail);
                $emails = array_merge($emails, $categoryEmail);
            }
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            // $this->handlerSendEmail($scene, $data['email'], $data, $senderEmail);
            foreach ($emails as $email) {
                $this->handlerSendEmail($scene, $email, $data, $senderEmail);
            }

            return true;
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    // 申请样本
    public function productSample($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $addressDetail = $data['address'] ?? '';
            $country = '';
            if (!empty($data['country_id'])) {
                $country = Country::where('id', $data['country_id'])->value('name');
            }
            $priceEdition = '';
            if (!empty($data['price_edition'])) {
                $priceEditionRecord = PriceEditionValues::query()->select(['name', 'language_id'])->where('id', $data['price_edition'])->first();
                if ($priceEditionRecord) {
                    $priceEditionData = $priceEditionRecord->toArray();
                    $languageName = Languages::where('id', $priceEditionData['language_id'])->value('name');
                    $priceEdition =  (!empty($languageName) ? $languageName : '') . ' ' . (!empty($priceEditionRecord['name']) ? $priceEditionRecord['name'] : '');
                }
            }
            $productsName = '';
            $productLink = '';
            $categoryEmail = '';
            $categoryName = '';
            if (isset($data['product_id']) && !empty($data['product_id'])) {
                $productsInfo = Products::query()->where("id", $data['product_id'])
                    ->select(
                        [
                            'url',
                            'thumb',
                            'name',
                            'id as product_id',
                            'published_date',
                            'category_id'
                        ]
                    )->first();
                $productsName = !empty($productsInfo) ? $productsInfo->name : '';
                $productLink = !empty($productsInfo) ? $this->getProductUrl($productsInfo) : '';
                // 分类邮箱
                if (!empty($productsInfo)) {
                    $categoryInfo = ProductsCategory::query()->where('id', $productsInfo['category_id'])->first();
                    if (!empty($categoryInfo)) {
                        $categoryEmail = $categoryInfo->email ?? '';
                        $categoryName = $categoryInfo->name ?? '';
                    }
                }
            }
            $data['province'] = City::where('id', $data['province_id'])->value('name') ?? '';
            $data['city'] = City::where('id', $data['city_id'])->value('name') ?? '';
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'department'   => $data['department']??'',
                'messageAddress'      => $addressDetail,
                'area'         => $data['province'] . $data['city'] . " " . $addressDetail,
                'phone'        => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => !empty($data['buy_time']) && $data['buy_time'] != 0 ? $data['buy_time'] : '',
                'content'      => $data['content'],
                'backendUrl'   => $imgDomain,
                'link'         => $productLink,
                'productsName' => $productsName,
                'dateTime'     => date('Y-m-d'),
                'url'          => $productLink,
                'country'      => $country,
                'language'     => $ContactUs['language_version'] ?? '',
                'priceEdition' => $priceEdition,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                ->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                . $data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene('productSample');
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            //邮件标题
            if (!empty($categoryName)) {
                $scene->title = $categoryName . "-" . $scene->title;
            }
            if (!empty($productsName)) {
                $scene->title = $scene->title . ":  {$productsName}";
            }

            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            // 收件人额外加上分类邮箱
            if ($categoryEmail) {
                $categoryEmail = explode(',', $categoryEmail);
                $emails = array_merge($emails, $categoryEmail);
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            //$this->handlerSendEmail($scene, $data['email'], $data, $senderEmail);
            foreach ($emails as $email) {
                $this->handlerSendEmail($scene, $email, $data, $senderEmail);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('邮件:'.$e->getMessage());
            ReturnJson(false, $e->getMessage());
        }
    }

    // 联系我们
    public function contactUs($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $productsName = '';
            $productLink = '';
            $country = '';
            if (!empty($data['country_id'])) {
                $country = Country::where('id', $data['country_id'])->value('name');
            }
            $priceEdition = '';
            if (!empty($data['price_edition'])) {
                $priceEditionRecord = PriceEditionValues::query()->select(['name', 'language_id'])->where('id', $data['price_edition'])->first();
                if ($priceEditionRecord) {
                    $priceEditionData = $priceEditionRecord->toArray();
                    $languageName = Languages::where('id', $priceEditionData['language_id'])->value('name');
                    $priceEdition =  (!empty($languageName) ? $languageName : '') . ' ' . (!empty($priceEditionRecord['name']) ? $priceEditionRecord['name'] : '');
                }
            }
            if (isset($data['product_id']) && !empty($data['product_id'])) {
                $productsInfo = Products::query()->where("id", $data['product_id'])
                    ->select(
                        [
                            'url',
                            'thumb',
                            'name',
                            'id as product_id',
                            'published_date',
                            'category_id'
                        ]
                    )->first();
                $productsName = !empty($productsInfo) ? $productsInfo->name : '';
                $productLink = !empty($productsInfo) ? $this->getProductUrl($productsInfo) : '';
            }
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $area = $this->getAreaName($data);
            $addressDetail = $data['address'] ?? '';
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'department'   => $data['department']??'',
                'messageAddress'      => $addressDetail,
                'area'         => $area . " " . $addressDetail,
                'phone'        => $data['phone'] ?: '',
                'plantTimeBuy' => !empty($data['buy_time']) && $data['buy_time'] != 0 ? $data['buy_time'] : '',
                //'content' => $data['remarks'],
                'content'      => $data['content'],
                'backendUrl'   => $imgDomain,
                'dateTime'     => date('Y-m-d'),
                'language'     => $ContactUs['language_version'] ?? '',
                'link'         => $productLink,
                'productsName' => $productsName,
                'country'      => $country,
                'priceEdition' => $priceEdition,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                ->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                . $data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene('contactUs');
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            //$this->handlerSendEmail($scene, $data['email'], $data, $senderEmail);
            foreach ($emails as $email) {
                $this->handlerSendEmail($scene, $email, $data, $senderEmail);
            }

            return true;
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    // 定制报告
    public function customized($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $addressDetail = $data['address'] ?? '';
            $productsName = '';
            $productLink = '';
            $categoryEmail = '';
            $categoryName = '';
            $country = '';
            if (!empty($data['country_id'])) {
                $country = Country::where('id', $data['country_id'])->value('name');
            }
            $priceEdition = '';
            if (!empty($data['price_edition'])) {
                $priceEditionRecord = PriceEditionValues::query()->select(['name', 'language_id'])->where('id', $data['price_edition'])->first();
                if ($priceEditionRecord) {
                    $priceEditionData = $priceEditionRecord->toArray();
                    $languageName = Languages::where('id', $priceEditionData['language_id'])->value('name');
                    $priceEdition =  (!empty($languageName) ? $languageName : '') . ' ' . (!empty($priceEditionRecord['name']) ? $priceEditionRecord['name'] : '');
                }
            }
            if (isset($data['product_id']) && !empty($data['product_id'])) {
                $productsInfo = Products::query()->where("id", $data['product_id'])
                    ->select(
                        [
                            'url',
                            'thumb',
                            'name',
                            'id as product_id',
                            'published_date',
                            'category_id'
                        ]
                    )->first();
                $productsName = !empty($productsInfo) ? $productsInfo->name : '';
                $productLink = !empty($productsInfo) ? $this->getProductUrl($productsInfo) : '';
                // 分类邮箱
                if (!empty($productsInfo)) {
                    $categoryInfo = ProductsCategory::query()->where('id', $productsInfo['category_id'])->first();
                    if (!empty($categoryInfo)) {
                        $categoryEmail = $categoryInfo->email ?? '';
                        $categoryName = $categoryInfo->name ?? '';
                    }
                }
            }
            $area = $this->getAreaName($data);
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ?: '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'department'   => $data['department']??'',
                'messageAddress'      => $addressDetail,
                'area'         => $area . " " . $addressDetail,
                'phone'        => $data['phone'] ?: '',
                'plantTimeBuy' => !empty($data['buy_time']) && $data['buy_time'] != 0 ? $data['buy_time'] : '',
                'content'      => $data['content'],
                'backendUrl'   => $imgDomain,
                'link'         => $productLink,
                'productsName' => $productsName,
                'dateTime'     => date('Y-m-d'),
                'language'     => $ContactUs['language_version'] ?? '',
                'country'      => $country,
                'priceEdition' => $priceEdition,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                ->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                . $data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene('customized');
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            //邮件标题
            if (!empty($categoryName)) {
                $scene->title = $categoryName . "-" . $scene->title;
            }
            if (!empty($productsName)) {
                $scene->title = $scene->title . ":  {$productsName}";
            }

            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            // 收件人额外加上分类邮箱
            if ($categoryEmail) {
                $categoryEmail = explode(',', $categoryEmail);
                $emails = array_merge($emails, $categoryEmail);
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            //$this->handlerSendEmail($scene, $data['email'], $data, $senderEmail);
            foreach ($emails as $email) {
                $this->handlerSendEmail($scene, $email, $data, $senderEmail);
            }

            return true;
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    // 下单后未付款
    public function placeOrder($orderId)
    {
        try {
            $Order = Order::where('id', $orderId)->first();
            $data = $Order ? $Order->toArray() : [];
            if (!$data) {
                ReturnJson(false, '未找到订单数据');
            }
            $data['domain'] = env('DOMAIN_URL', 'https://mmgcn.marketmonitorglobal.com.cn');
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            // $PayName = Pay::where('id', $data['pay_type'])->value('name');
            $payInfo = Pay::query()->where('code', $data['pay_code'])->first();
            $PayName = '';
            $tax_rate = 0;
            $exchange_rate = 1;
            if (!empty($payInfo)) {
                $PayName = $payInfo->name;
                $tax_rate = $payInfo->pay_tax_rate;
                $exchange_rate = $payInfo->pay_exchange_rate;
            }
            $exchange_coupon_amount = bcmul($data['coupon_amount'], $exchange_rate, 2);
            $exchange_order_amount = bcmul($data['order_amount'], $exchange_rate, 2);
            $exchange_order_actually_paid = bcmul($data['actually_paid'], $exchange_rate, 2);
            $exchange_order_tax = bcmul($exchange_order_actually_paid, $tax_rate, 2);

            $orderGoodsList = OrderGoods::where('order_id', $orderId)->get()->toArray();
            $languageList = Languages::GetListById();
            $goods_data_list = [];
            $productsName = "";
            $sum_goods_cnt = 0;
            $sum_goods_original_price_all = 0; // 小计原价,不含税
            $sum_goods_present_price_all = 0; // 小计现价,不含税
            // 默认图片
            // 若报告图片为空，则使用系统设置的默认报告高清图
            $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
            foreach ($orderGoodsList as $key => $OrderGoods) {
                $goods_data = [];
                $priceEditionId = $OrderGoods['price_edition'];
                $priceEdition = PriceEditionValues::find($priceEditionId);
                if (!empty($priceEdition)) {
                    $language = $languageList[$priceEdition->language_id];
                } else {
                    $language = '';
                }
                $products = Products::select(
                    ['url', 'thumb', 'name', 'id as product_id', 'published_date', 'category_id', 'pages']
                )->where('id', $OrderGoods['goods_id'])->first();
                if (empty($products)) {
                    continue;
                }
                //拼接产品名称
                if (!empty($products->name) && empty($productsName)) {
                    $productsName = $products->name; //." ";
                }
                $goods_data = $products->toArray();
                $goods_data['goods_number'] = $OrderGoods['goods_number'] ?: 0;
                $sum_goods_cnt += $goods_data['goods_number'];
                $goods_data['language'] = $language;
                $goods_data['price_edition'] = isset($priceEdition['name']) && !empty($priceEdition['name'])
                    ? $priceEdition['name'] : '';
                // 单个商品现价
                $goods_data['goods_present_price'] = $OrderGoods['goods_present_price'];
                // 多个同商品现价
                $goods_data['goods_sum_price'] = bcmul($OrderGoods['goods_present_price'], $OrderGoods['goods_number'], 2);
                //$goods_data['goods_present_price'] = $OrderGoods['goods_present_price'];

                $goods_data['goods_number'] = $OrderGoods['goods_number'];
                // 单个商品原价
                $goods_data['goods_original_price'] = $OrderGoods['goods_original_price'];
                // 多个同商品原价
                $goods_data['sum_original_price'] = bcmul($goods_data['goods_original_price'], $goods_data['goods_number'], 2);

                // 多个同商品的汇率转换
                $goods_data['exchange_sum_original_price'] = bcmul($goods_data['sum_original_price'], $exchange_rate, 2);
                $goods_data['exchange_sum_present_price'] = bcmul($goods_data['goods_sum_price'], $exchange_rate, 2);

                // 商品累加小计
                $sum_goods_original_price_all += $goods_data['sum_original_price'];
                $sum_goods_present_price_all += $goods_data['goods_sum_price'];

                // 分类信息
                $category = ProductsCategory::select(['id', 'name', 'thumb'])->where('id', $products['category_id'])->first();
                $goods_data['category_name'] = $category['name'] ?? '';
                $goods_data['category_thumb'] = $category['thumb'] ?? '';
                $tempThumb = '';
                if (!empty($products['thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($products['thumb']);
                } elseif (!empty($goods_data['category_thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($goods_data['category_thumb']);
                } else {
                    // 如果报告图片、分类图片为空，使用系统默认图片
                    $tempThumb = !empty($defaultImg) ? $defaultImg : '';
                }
                $goods_data['thumb'] = rtrim($imgDomain, '/') . $tempThumb;
                // $goods_data['thumb'] = rtrim($imgDomain, '/') . $products->getThumbImgAttribute();
                $goods_data['link'] = $this->getProductUrl($products);
                $goods_data_list[] = $goods_data;
            }
            // 小计汇率转换
            $exchange_sum_original_price_all = bcmul($sum_goods_original_price_all, $exchange_rate, 2);
            $exchange_sum_present_price_all = bcmul($sum_goods_present_price_all, $exchange_rate, 2);
            // 税费计算
            $original_tax = bcmul($sum_goods_original_price_all, $tax_rate, 2);
            $present_tax = bcmul($sum_goods_present_price_all, $tax_rate, 2);

            // 税费汇率转换
            $exchange_original_tax = bcmul($original_tax, $exchange_rate, 2);
            $exchange_present_tax = bcmul($present_tax, $exchange_rate, 2);

            $areaInfo = $this->getAreaName($data);
            $addres = $areaInfo . ' ' . $data['address'];
            if ($data['pay_coin_type'] == PayConst::COIN_TYPE_USD) {
                $pay_coin_symbol = PayConst::COIN_TYPE_USD;
            } else {
                $pay_coin_symbol = PayConst::$coinTypeSymbol[$data['pay_coin_type']] ?? '';
            }
            // 订单创建时间
            $orderCreatedTime = '';
            if (isset($data['created_at']) && !empty($data['created_at']) && is_int($data['created_at'])) {
                $orderCreatedTime = date('Y-m-d H:i:s', $data['created_at']);
            } elseif (isset($data['created_at']) && !empty($data['created_at']) && is_string($data['created_at'])) {
                $orderCreatedTime = $data['created_at'];
            }
            $siteName = request()->header('Site');
            if (empty($siteName)) {
                $AppName = env('APP_NAME');
                request()->headers->set('Site', $AppName); // 设置请求头
            }
            if (checkSiteAccessData(['mrrs', 'yhen', 'qyen', 'mmgen', 'lpien' , 'giren'])) {
                $orderStatusText = 'PAY_UNPAID';
            } elseif (checkSiteAccessData(['lpijp'])) {
                $orderStatusText = '支払い待ち';
            } else {
                $orderStatusText = '未付款';
            }
            $is_bank = false; // 是否线下转账，否则在邮件上没有跳转支付的链接
            if($Order->pay_code == PayConst::PAY_TYPE_BANK){
                $is_bank = true;
            }
            $data2 = [
                'homePage'               => $data['domain'],
                'myAccountUrl'           => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl'           => rtrim($data['domain'], '/') . '/contact-us',
                'orderListUrl'           => rtrim($data['domain'], '/') . '/account/order',
                'homeUrl'                => $data['domain'],
                'backendUrl'             => $imgDomain,
                'userName'               => $data['username'] ? $data['username'] : '',
                'userEmail'              => $data['email'],
                'userCompany'            => $data['company'],
                'userDepartment'         => $data['department']??'',
                'userAddress'            => $addres,
                'userPhone'              => $data['phone'] ? $data['phone'] : '',
                'orderStatus'            => $orderStatusText,
                'paymentMethod'          => $PayName,
                'orderAmount'            => $data['order_amount'],
                'preferentialAmount'     => $data['coupon_amount'],
                'orderActuallyPaid'      => $data['actually_paid'],
                'exchange_order_amount'  => $exchange_order_amount,
                'exchange_coupon_amount' => $exchange_coupon_amount,
                'exchange_order_actually_paid' => $exchange_order_actually_paid,
                'exchange_order_tax'     => $exchange_order_tax,
                'pay_coin_symbol'        => $pay_coin_symbol, // 支付符号,
                'orderNumber'            => $data['order_number'],
                'paymentLink'            => $data['domain'] . '/api/order/pay?order_id=' . $data['id'],
                //'orderDetails'           => $data['domain'].'/account?orderdetails='.$data['id'],
                'orderDetails'           => $data['domain'] . '/account/order?orderdetails=' . $data['id'],
                'goods'                  => $goods_data_list,
                'userId'                 => $data['user_id'],
                'dateTime'               => date('Y-m-d H:i:s', time()),
                'orderTime'              => $orderCreatedTime,
                'sumGoodsCnt'            => $sum_goods_cnt,
                'sum_goods_original_price_all'      => $sum_goods_original_price_all,
                'sum_goods_present_price_all'       => $sum_goods_present_price_all,
                'exchange_sum_original_price_all'   => $exchange_sum_original_price_all,
                'exchange_sum_present_price_all'    => $exchange_sum_present_price_all,
                'original_tax'           => $original_tax,
                'present_tax'            => $present_tax,
                'exchange_original_tax'  => $exchange_original_tax,
                'exchange_present_tax'   => $exchange_present_tax,
                'content'                => $Order['remarks'],
                'is_bank'                => $is_bank,
            ];
            $data['country'] = Country::where('id', $Order['country_id'])->value('name');
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                ->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                . $data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene('placeOrder');
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            $scene->title = $scene->title . ": {$productsName}";
            $siteName = request()->header('Site');
            if (empty($siteName)) {
                $siteName = env('APP_NAME');
            }
            //            if (in_array($siteName, ['mrrs', 'yhen', 'qyen'])) {
            //                $scene->title = $scene->title.", order number is: {$data['order_number']}";
            //            } else {
            //                $scene->title = $scene->title.", 订单号是 {$data['order_number']}";
            //            }

            $this->handlerSendEmail($scene, $data['email'], $data, $senderEmail);
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            foreach ($emails as $email) {
                $this->handlerSendEmail($scene, $email, $data, $senderEmail);
            }

            return true;
        } catch (\Exception $e) {
            ReturnJson(false, $e->getMessage());
        }
    }

    // 下单后已付款
    public function payment($id) {
        try {
            $Order = Order::where('id', $id)->first();
            $data = $Order ? $Order->toArray() : [];
            if (!$data) {
                ReturnJson(false, '未找到订单数据');
            }
            $user = User::find($data['user_id']);
            $user = $user ? $user->toArray() : [];
            $data['domain'] = env('DOMAIN_URL', 'https://mmgcn.marketmonitorglobal.com.cn');
            // $PayName = Pay::where('id', $data['pay_type'])->value('name');
            $payInfo = Pay::query()->where('code', $data['pay_code'])->first();
            $PayName = '';
            $tax_rate = 0;
            $exchange_rate = 1;
            if (!empty($payInfo)) {
                $PayName = $payInfo->name;
                $tax_rate = $payInfo->pay_tax_rate;
                $exchange_rate = $payInfo->pay_exchange_rate;
            }
            $exchange_coupon_amount = bcmul($data['coupon_amount'], $exchange_rate, 2);
            $exchange_order_amount = bcmul($data['order_amount'], $exchange_rate, 2);
            $exchange_order_actually_paid = bcmul($data['actually_paid'], $exchange_rate, 2);
            $exchange_order_tax = bcmul($exchange_order_actually_paid, $tax_rate, 2);
            $orderGoodsList = OrderGoods::where('order_id', $Order['id'])->get()->toArray();
            $languageList = Languages::GetListById();
            $goods_data_list = [];
            $productsName = "";
            $sum_goods_cnt = 0;
            $sum_goods_original_price_all = 0; // 小计原价,不含税
            $sum_goods_present_price_all = 0; // 小计现价,不含税
            // 默认图片
            // 若报告图片为空，则使用系统设置的默认报告高清图
            $defaultImg = SystemValue::where('key', 'default_report_img')->value('value');
            // $imgDomain = env('IMAGE_URL');
            $imgDomain = env('IMAGE_URL_BACKUP', '');
            foreach ($orderGoodsList as $key => $OrderGoods) {
                $goods_data = [];
                $priceEditionId = $OrderGoods['price_edition'];
                $priceEdition = PriceEditionValues::find($priceEditionId);
                if (!empty($priceEdition)) {
                    $language = $languageList[$priceEdition->language_id];
                } else {
                    $language = '';
                }
                $products = Products::select(
                    ['url', 'thumb', 'name', 'id as product_id', 'published_date', 'category_id', 'pages']
                )->where('id', $OrderGoods['goods_id'])->first();
                if (empty($products)) {
                    continue;
                }
                //拼接产品名称
                if (!empty($products->name) && empty($productsName )) {
                    $productsName = $products->name;//." ";
                }
                $goods_data = $products->toArray();
                $goods_data['goods_number'] = $OrderGoods['goods_number'] ?: 0;
                $sum_goods_cnt += $goods_data['goods_number'];
                $goods_data['language'] = $language;
                $goods_data['price_edition'] = isset($priceEdition['name']) && !empty($priceEdition['name'])
                    ? $priceEdition['name'] : '';
                if ($data['coupon_id'] > 0) {
                    $goods_data['goods_present_price'] = $OrderGoods['goods_original_price'];
                } else {
                    $goods_data['goods_present_price'] = $OrderGoods['goods_present_price'];
                }
                //$goods_data['goods_present_price'] = $OrderGoods['goods_present_price'];
                $goods_data['goods_sum_price'] = bcmul(
                    $goods_data['goods_present_price'],
                    $OrderGoods['goods_number'],
                    2
                );

                $goods_data['goods_number'] = $OrderGoods['goods_number'];
                $goods_data['goods_original_price'] = $OrderGoods['goods_original_price'];
                $goods_data['sum_original_price'] = bcmul(
                    $goods_data['goods_original_price'], $goods_data['goods_number'], 2
                );


                $goods_data['goods_number'] = $OrderGoods['goods_number'];
                // 单个商品原价
                $goods_data['goods_original_price'] = $OrderGoods['goods_original_price'];
                // 多个同商品原价
                $goods_data['sum_original_price'] = bcmul($goods_data['goods_original_price'], $goods_data['goods_number'], 2);

                // 多个同商品的汇率转换
                $goods_data['exchange_sum_original_price'] = bcmul($goods_data['sum_original_price'], $exchange_rate, 2);
                $goods_data['exchange_sum_present_price'] = bcmul($goods_data['goods_sum_price'], $exchange_rate, 2);

                // 商品累加小计
                $sum_goods_original_price_all += $goods_data['sum_original_price'];
                $sum_goods_present_price_all += $goods_data['goods_sum_price'];

                // 分类信息
                $category = ProductsCategory::select(['id', 'name', 'thumb'])->where('id', $products['category_id'])
                                            ->first();
                $goods_data['category_name'] = $category['name'] ?? '';
                $goods_data['category_thumb'] = $category['thumb'] ?? '';
                $tempThumb = '';
                if (!empty($products['thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($products['thumb']);
                } elseif (!empty($goods_data['category_thumb'])) {
                    $tempThumb = Common::cutoffSiteUploadPathPrefix($goods_data['category_thumb']);
                } else {
                    // 如果报告图片、分类图片为空，使用系统默认图片
                    $tempThumb = !empty($defaultImg) ? $defaultImg : '';
                }
                $goods_data['thumb'] = rtrim($imgDomain, '/').$tempThumb;
                // $goods_data['thumb'] = rtrim($imgDomain, '/') . $products->getThumbImgAttribute();
                $goods_data['link'] = $this->getProductUrl($products);
                $goods_data_list[] = $goods_data;
            }

            // 小计汇率转换
            $exchange_sum_original_price_all = bcmul($sum_goods_original_price_all, $exchange_rate, 2);
            $exchange_sum_present_price_all = bcmul($sum_goods_present_price_all, $exchange_rate, 2);
            // 税费计算
            $original_tax = bcmul($sum_goods_original_price_all, $tax_rate, 2);
            $present_tax = bcmul($sum_goods_present_price_all, $tax_rate, 2);

            // 税费汇率转换
            $exchange_original_tax = bcmul($original_tax, $exchange_rate, 2);
            $exchange_present_tax = bcmul($present_tax, $exchange_rate, 2);

            $cityName = City::where('id', $data['city_id'])->value('name');
            $provinceName = City::where('id', $data['province_id'])->value('name');
            $addres = $provinceName.' '.$cityName.' '.$data['address'];
            if ($data['pay_coin_type'] == PayConst::COIN_TYPE_USD) {
                $pay_coin_symbol = PayConst::COIN_TYPE_USD;
            } else {
                $pay_coin_symbol = PayConst::$coinTypeSymbol[$data['pay_coin_type']] ?? '';
            }
            $siteName = request()->header('Site');
            // 订单创建时间
            $orderCreatedTime = '';
            if (isset($data['created_at']) && !empty($data['created_at']) && is_int($data['created_at'])) {
                $orderCreatedTime = date('Y-m-d H:i:s', $data['created_at']);
            } elseif (isset($data['created_at']) && !empty($data['created_at']) && is_string($data['created_at'])) {
                $orderCreatedTime = $data['created_at'];
            }

            if (empty($siteName)) {
                $AppName = env('APP_NAME');
                request()->headers->set('Site', $AppName); // 设置请求头
            }
            if (checkSiteAccessData(['mrrs', 'yhen', 'qyen', 'mmgen', 'lpien' ,'giren'])) {
                $orderStatusText = 'PAY_SUCCESS';
            } else {
                $orderStatusText = '已付款';
            }
            $data2 = [
                'homePage'               => $data['domain'],
                'myAccountUrl'           => rtrim($data['domain'], '/').'/account/account-infor',
                'contactUsUrl'           => rtrim($data['domain'], '/').'/contact-us',
                'orderListUrl'           => rtrim($data['domain'], '/').'/account/order',
                'homeUrl'                => $data['domain'],
                'backendUrl'             => $imgDomain,
                'userName'               => $data['username'] ?: '',
                'userEmail'              => $data['email'],
                'userCompany'            => $data['company'],
                'userAddress'            => $addres,
                'userPhone'              => $data['phone'] ?: '',
                'orderStatus'            => $orderStatusText,
                'paymentMethod'          => $PayName,
                'orderAmount'            => $data['order_amount'],
                'preferentialAmount'     => $data['coupon_amount'],
                'orderActuallyPaid'      => $data['actually_paid'],
                'pay_coin_symbol'        => $pay_coin_symbol, // 支付符号,
                'exchange_order_amount'  => $exchange_order_amount,
                'exchange_coupon_amount' => $exchange_coupon_amount,
                'exchange_order_actually_paid' => $exchange_order_actually_paid,
                'exchange_order_tax'     => $exchange_order_tax,
                'orderNumber'            => $data['order_number'],
                'paymentLink'            => $data['domain'].'/api/order/pay?order_id='.$data['id'],
                'orderDetails'           => $data['domain'].'/account?orderdetails='.$data['id'],
                'goods'                  => $goods_data_list,
                'userId'                 => $data['user_id'],
                'dateTime'               => date('Y-m-d H:i:s', time()),
                'orderTime'              => $orderCreatedTime,
                'sumGoodsCnt'            => $sum_goods_cnt,
                'sum_goods_original_price_all'      => $sum_goods_original_price_all,
                'sum_goods_present_price_all'       => $sum_goods_present_price_all,
                'exchange_sum_original_price_all'   => $exchange_sum_original_price_all,
                'exchange_sum_present_price_all'    => $exchange_sum_present_price_all,
                'original_tax'           => $original_tax,
                'present_tax'            => $present_tax,
                'exchange_original_tax'  => $exchange_original_tax,
                'exchange_present_tax'   => $exchange_present_tax,
                'content'                => $Order['remarks'],
            ];
            $data['country'] = Country::where('id', $Order['country_id'])->value('name');
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address','company_address'])
                                   ->pluck('value', 'key')
                                   ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            // 多个电话
            $sitePhones = SystemValue::where('key', 'sitePhone')->pluck('value');
            if($sitePhones){
                $sitePhones = $sitePhones->toArray();
                $data['sitePhones'] = [];
                foreach ($sitePhones as $key => $sitePhoneItem) {
                    $data['sitePhones'][] = $sitePhoneItem;
                }
            }
            $data = $this->officeData($data);
            $data['toSiteEmail'] = isset($data['siteEmail']) && !empty($data['siteEmail']) ? 'mailto:'
                                                                                             .$data['siteEmail'] : '';
            $data = array_merge($data2, $data);
            $scene = $this->getScene('payment');
            // 邮件标题
            $siteName = request()->header('Site');
            if (empty($siteName)) {
                $siteName = env('APP_NAME');
            }

            $scene->title = $scene->title.": {$productsName}";
//            if (in_array($siteName, ['mrrs', 'yhen', 'qyen'])) {
//                $scene->title = $scene->title.", order number is: {$data['order_number']}";
//            } else {
//                $scene->title = $scene->title.", 订单号是 {$data['order_number']}";
//            }
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            $this->handlerSendEmail($scene, $data['email'], $data, $senderEmail);
            foreach ($emails as $email) {
                $this->handlerSendEmail($scene, $email, $data, $senderEmail);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('ex:'.$e->getMessage());
            ReturnJson(false, $e->getMessage());
        }
    }

    /**
     *
     * @param object $scene       邮件模板
     * @param string $email       收件人
     * @param array  $data        邮件模板数据
     * @param object $senderEmail 发邮件配置信息
     * @param bool   $isQueue     是否队列执行
     * @params string $testEmail  测试邮件
     *
     * @return mixed
     */
    public function handlerSendEmail($scene, $email, $data, $senderEmail, $isQueue = false, $testEmail = '') {
        //校验邮箱规则
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        //如果是测试邮件,用于cli
        if (!empty($testEmail)) {
            $email = $testEmail;
        }
        //fpm场景
        if (!empty($this->testEmail)) {
            $this->testSendcnt += 1;
            if ($this->testSendcnt >= 2) {
                //测试邮件发送次数限制1次
                return true;
            }
        }
        if (!$isQueue) {
            //让队列执行, 需要放入队列
            $app_name = env('APP_NAME');
            HandlerEmailJob::dispatch($scene, $email, $data, $senderEmail, $this->testEmail)->onQueue($app_name);

            return true;
        }
        // 邮箱账号配置信息
        $config = [
            'host'       => $senderEmail->host,
            'port'       => $senderEmail->port,
            'encryption' => $senderEmail->encryption,
            'username'   => $senderEmail->email,
            'password'   => $senderEmail->password
        ];
        $this->SetConfig($config);
        if ($scene->alternate_email_id) {
            // 备用邮箱配置信息
            $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->alternate_email_id
            );
            $BackupConfig = [
                'host'       => $BackupSenderEmail->host,
                'port'       => $BackupSenderEmail->port,
                'encryption' => $BackupSenderEmail->encryption,
                'username'   => $BackupSenderEmail->email,
                'password'   => $BackupSenderEmail->password
            ];
            $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
        }
        try {
            $rs = $this->SendEmail(
                $email,
                $scene->body,
                $data,
                $scene->title,
                $senderEmail->email,
                'trends',
                $senderEmail->name
            );
        } catch (\Exception $e) {
            if ($scene->alternate_email_id) {
                $rs = $this->SendEmail(
                    $email,
                    $scene->body,
                    $data,
                    $scene->title,
                    $BackupSenderEmail->email,
                    'backups',
                    $BackupSenderEmail->name
                );
            }
        }
        if (empty($rs)) {
            $sendStatus = 0;
        } else {
            $sendStatus = 1;
            if (!empty($data['id'])) {
                if (in_array($scene->action, ['payment', 'placeOrder'])) {
                    //存入订单邮件发送记录
                    Order::query()->where("id", $data['id'])->update(['send_email_time' => time()]);
                } elseif (in_array($scene->action, ['contactUs', 'productSample', 'customized', 'becomeReseller'])) {
                    //存入留言邮件发送记录
                    ContactUs::query()->where("id", $data['id'])->update(['send_email_time' => time()]);
                }
            }
        }
        $emailLog = [
            'status'        => $sendStatus,
            'send_email_id' => $scene->email_sender_id,
            'emails'        => $email,
            'email_scenes'  => $scene->id,
            'data'          => json_encode($data),
            'created_at'    => time(),
            'updated_at'    => time(),
        ];
        EmailLog::insert($emailLog);
    }

    /**
     *
     * @param array $data
     *
     * @return array
     */
    private function getAreaName($data) {
        $area = '';
        if (!empty($data['province_id'])) {
            $area .= City::where('id', $data['province_id'])->value('name')." ";
        }
        if (!empty($data['city_id'])) {
            $area .= City::where('id', $data['city_id'])->value('name');
        }

        return $area;
    }

    public function getProductUrl($products) {
        //https://mmgcn.marketmonitorglobal.com.cn/reports/332607/strain-wave-gear
        $domain = env('DOMAIN_URL', 'https://mmgcn.marketmonitorglobal.com.cn');

        return $domain."/reports/{$products->product_id}/{$products->url}";
    }

    /**
     * $action code码
     *
     * @return mixed
     */
    private function getScene($action) {
        $scene = EmailScene::where('action', $action)->select(
            [
                'id',
                'name',
                'title',
                'action',
                'body',
                'email_sender_id',
                'email_recipient',
                'status',
                'alternate_email_id'
            ]
        )->first();

        return $scene;
    }

    public function officeData($data) {
        if (!checkSiteAccessData(['qyen'])) {
            //return $data;
        }
        $country_id_list = Office::query()
                                 ->where('status', 1)
                                 ->orderBy('sort', 'asc')
                                 ->pluck('country_id')
                                 ->toArray();
        if (!empty($country_id_list)) {
            $globalBranch = Country::query()->whereIn('id', $country_id_list)->pluck('name')->toArray();
        }
        $globalBranch = !empty($globalBranch) ? implode(", ", $globalBranch) : '';
        //英文办公室
        $office_english_obj = Office::query()->where(['language_alias' => 'English', 'status' => 1])->first();
        $office_english = [];
        $office_english['abbreviation'] = '';
        $office_english['address'] = '';
        $office_english['city'] = '';
        if (!empty($office_english_obj)) {
            $office_english['abbreviation'] = $office_english_obj->abbreviation ?? '';
            $office_english['address'] = $office_english_obj->address ?? '';
            $office_english['city'] = $office_english_obj->city ?? '';
            $office_english['phone'] = $office_english_obj->phone ?? '';
            if (strpos($office_english['phone'], ',') > 0) {
                $temp_spilt = explode(',', $office_english['phone']);
                $temp_spilt = array_map(function ($item) {
                    return trim($item);
                }, $temp_spilt);
                $office_english_phone = implode(' /', $temp_spilt);
            } else {
                $office_english_phone = $office_english['phone'];
            }
        }
        //中文办公室
        $office_chinese = Office::query()->where(['language_alias' => 'Chinese', 'status' => 1])->value('phone');
        if (strpos($office_chinese, ',') > 0) {
            $temp_spilt = explode(',', $office_chinese);
            $temp_spilt = array_map(function ($item) {
                return trim($item);
            }, $temp_spilt);
            $office_chinese_phone = implode(' /', $temp_spilt);
        } else {
            $office_chinese_phone = $office_chinese;
        }
        //日本办公室
        $office_japanese = Office::query()->where(['language_alias' => 'Japanese', 'status' => 1])->value('phone');
        if (strpos($office_japanese, ',') > 0) {
            $temp_spilt = explode(',', $office_japanese);
            $temp_spilt = array_map(function ($item) {
                return trim($item);
            }, $temp_spilt);
            $office_japanese_phone = implode(' /', $temp_spilt);
        } else {
            $office_japanese_phone = $office_japanese;
        }
        //韩文办公室
        $office_korean = Office::query()->where(['language_alias' => 'Korean', 'status' => 1])->value('phone');
        if (strpos($office_korean, ',') > 0) {
            $temp_spilt = explode(',', $office_korean);
            $temp_spilt = array_map(function ($item) {
                return trim($item);
            }, $temp_spilt);
            $office_korean_phone = implode(' /', $temp_spilt);
        } else {
            $office_korean_phone = $office_korean;
        }

        if (checkSiteAccessData(['giren'])) {
            $office_gz = Office::query()->where(['language_alias' => 'GZ', 'status' => 1])->first();
            $data['office_gz_name'] = '';
            $data['office_gz_phone'] = '';
            $data['office_gz_address'] = '';
            if(!empty($office_gz )){
                $data['office_gz_name'] = $office_gz->name ?? '';
                $data['office_gz_phone'] = $office_gz->phone ?? '';
                $data['office_gz_address'] = $office_gz->address ?? '';
            }
            $office_hk = Office::query()->where(['language_alias' => 'HK', 'status' => 1])->first();
            $data['office_hk_name'] = '';
            $data['office_hk_phone'] = '';
            $data['office_hk_address'] = '';
            if(!empty($office_hk )){
                $data['office_hk_name'] = $office_hk->name ?? '';
                $data['office_hk_phone'] = $office_hk->phone ?? '';
                $data['office_hk_address'] = $office_hk->address ?? '';
            }
        }

        $data['office_english_name'] = ($office_english['abbreviation'] ?? '').'('.($office_english['city'] ?? '').')';
        $data['office_english_address'] = $office_english['address'] ?? '';
        $data['office_english_phone'] = $office_english_phone ?? '';
        $data['office_chinese_phone'] = $office_chinese_phone ?? '';
        $data['office_japanese_phone'] = $office_japanese_phone ?? '';
        $data['office_korean_phone'] = $office_korean_phone ?? '';
        $data['globalBranch'] = $globalBranch;

        return $data;
    }
}
