<?php

namespace App\Http\Controllers\Common;

use App\Const\PayConst;
use App\Jobs\HandlerEmailJob;
use App\Models\Country;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrendsEmail;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\DictionaryValue;
use App\Models\Email;
use App\Models\EmailScene;
use App\Models\Languages;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\Pay;
use App\Models\PriceEditionValues;
use App\Models\Products;
use App\Models\ProductsCategory;
use App\Models\SystemValue;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class SendEmailController extends Controller
{
    public $testEmail   = '';
    public $testSendcnt = 0; //测试邮箱发送次数

    /**
     * 动态配置邮箱参数
     *
     * @param array $data 邮箱配置参数信息
     */
    private function SetConfig($data, $name = 'trends')
    {
        $keys = ['transport', 'host', 'port', 'encryption', 'username', 'password', 'timeout', 'local_domain'];
        foreach ($data as $key => $value) {
            if (in_array($key, $keys)) {
                Config::set('mail.mailers.' . $name . '.' . $key, $value, true);
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
    public function Register($id)
    {
        try {
            $user = User::find($id);
            $data = $user ? $user->toArray() : [];
            $data['domain'] = 'https://' . $_SERVER['SERVER_NAME'];
            $token = $data['email'] . '&' . $data['id'];
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
            $verifyUrl = $data['domain'] . '/?verifyemail=' . $emailCode . '&token=' . $token;
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'backendUrl'   => env('IMAGE_URL'),
                'verifyUrl'    => $verifyUrl,
                'userName'     => $data['name'],
                'area'         => City::where('id', $data['city_id'])->value('name'),
                'dateTime'     => date('Y-m-d',time()),
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'register')->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
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
    public function RegisterSuccess($id)
    {
        try {
            $user = User::find($id);
            $data = $user ? $user->toArray() : [];
            $data['domain'] = 'https://' . $_SERVER['SERVER_NAME'];
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'backendUrl'   => env('IMAGE_URL'),
                'userName'     => $data['name'],
                'area'         => City::where('id', $data['area_id'])->value('name'),
                'dateTime'     => date('Y-m-d',time()),
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'registerSuccess')->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
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
    public function ResetPassword($email)
    {
        try {
            $user = User::where('email', $email)->first();
            if (empty($user)) {
                ReturnJson(false, trans()->get('lang.eamail_undefined'));
            }
            $user = $user->toArray();
            //过期时间一天后
            $end_time = time() + 86400;
            $token = $user['email'] . '&' . $user['id'] . '&' . $end_time . '&' . $user['updated_at'];
            $user['token'] = encrypt($token);
            $user['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $scene = EmailScene::where('action', 'password')->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            $domain = 'http://' . $_SERVER['SERVER_NAME'];
            $data = $user;
            $data['homePage'] = $domain;
            $data['myAccountUrl'] = rtrim($domain, '/') . '/account/account-infor';
            $data['contactUsUrl'] = rtrim($domain, '/') . '/contact-us';
            $data['homeUrl'] = $domain;
            $data['backendUrl'] = env('IMAGE_URL');
            $verifyUrl = $data['domain'] . '/signIn/resetPassword?verifyemail=do-reset-register=&email=' . $user['email']
                . '&token=' . $user['token'];
            $data['verifyUrl'] = $verifyUrl;
            $data['dateTime'] = date('Y-m-d',time());
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            $data = array_merge($data, $siteInfo);
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
                $productsName = '';
                $productLink = '';
            }
            $data['province'] = City::where('id', $data['province_id'])->value('name') ?? '';
            $data['city'] = City::where('id', $data['city_id'])->value('name') ?? '';
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'area'         => $data['province'] . $data['city'],
                'phone'        => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => $data['buy_time'],
                'content'      => $data['content'],
                'backendUrl'   => env('IMAGE_URL'),
                'link'         => $productLink,
                'productsName' => $productsName,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', $code)->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.email_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.email_error'));
            }
            //邮件标题
            $scene->title = $scene->title .  (!empty($productsName) ? (':' . $productsName) : '');
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
    public function Message($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            //$result['country'] = DictionaryValue::GetDicOptions('Country');
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
                $productsName = '';
                $productLink = '';
            }
            $data['country'] = Country::where('id', $data['country_id'])->value('name');
            $data['province'] = City::where('id', $data['province_id'])->value('name') ?? '';
            $data['city'] = City::where('id', $data['city_id'])->value('name') ?? '';
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'area'         => $data['province'] . $data['city'],
                'phone'        => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => $data['buy_time'],
                'content'      => $data['content'],
                'backendUrl'   => env('IMAGE_URL'),
                'link'         => $productLink,
                'productsName' => $productsName
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'productSample')->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
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
            ReturnJson(false, $e->getMessage());
        }
    }

    // 申请样本
    public function productSample($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
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
                $productsName = '';
                $productLink = '';
            }
            $data['province'] = City::where('id', $data['province_id'])->value('name') ?? '';
            $data['city'] = City::where('id', $data['city_id'])->value('name') ?? '';
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'area'         => $data['province'] . $data['city'],
                'phone'        => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => $data['buy_time'],
                'content'      => $data['content'],
                'backendUrl'   => env('IMAGE_URL'),
                'link'         => $productLink,
                'productsName' => $productsName,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'productSample')->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            //邮件标题
            $scene->title = $scene->title . ":  {$productsName}";
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

    // 联系我们
    public function contactUs($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $area = $this->getAreaName($data);
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ? $data['name'] : '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'area'         => $area,
                'phone'        => $data['phone'] ?: '',
                'plantTimeBuy' => $data['buy_time'],
                //'content' => $data['remarks'],
                'content'      => $data['content'],
                'backendUrl'   => env('IMAGE_URL'),
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'contactUs')->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
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
                $productsName = '';
                $productLink = '';
            }
            $area = $this->getAreaName($data);
            $data2 = [
                'homePage'     => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'      => $data['domain'],
                'userName'     => $data['name'] ?: '',
                'email'        => $data['email'],
                'company'      => $data['company'],
                'area'         => $area,
                'phone'        => $data['phone'] ?: '',
                'plantTimeBuy' => $data['buy_time'],
                'content'      => $data['content'],
                'backendUrl'   => env('IMAGE_URL'),
                'link'         => $productLink,
                'productsName' => $productsName,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'customized')->select(
                ['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id']
            )->first();
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            //邮件标题
            $scene->title = $scene->title . ":  {$productsName}";
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
            $PayName = Pay::where('id', $data['pay_type'])->value('name');
            $orderGoodsList = OrderGoods::where('order_id', $orderId)->get()->toArray();
            $languageList = Languages::GetListById();
            $goods_data_list = [];
            $productsName = "";
            $sum_goods_cnt = 0;
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
                    ['url', 'thumb', 'name', 'id as product_id', 'published_date', 'category_id']
                )->where('id', $OrderGoods['goods_id'])->first();
                if (empty($products)) {
                    continue;
                }
                //拼接产品名称
                if (!empty($products->name)) {
                    $productsName .= $products->name . " ";
                }
                $goods_data = $products->toArray();
                $goods_data['goods_number'] = $OrderGoods['goods_number'] ?: 0;
                $sum_goods_cnt += $goods_data['goods_number'];
                $goods_data['language'] = $language;
                $goods_data['price_edition'] = isset($priceEdition['name']) && !empty($priceEdition['name']) ? $priceEdition['name'] : '';
                $goods_data['goods_present_price'] = $OrderGoods['goods_original_price'];
                $goods_data['goods_sum_price'] = bcmul(
                    $OrderGoods['goods_original_price'],
                    $OrderGoods['goods_number'],
                    2
                );
                //$goods_data['goods_present_price'] = $OrderGoods['goods_present_price'];
                $goods_data['thumb'] = rtrim(env('IMAGE_URL', ''), '/') . $products->getThumbImgAttribute();
                $goods_data['link'] = $this->getProductUrl($products);
                $goods_data_list[] = $goods_data;
            }
            $areaInfo = $this->getAreaName($data);
            $addres = $areaInfo . ' ' . $data['address'];
            $data2 = [
                'homePage'           => $data['domain'],
                'myAccountUrl'       => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl'       => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'            => rtrim($data['domain'], '/') . '/account/order',
                'backendUrl'         => env('IMAGE_URL', ''),
                'userName'           => $data['username'] ? $data['username'] : '',
                'userEmail'          => $data['email'],
                'userCompany'        => $data['company'],
                'userAddress'        => $addres,
                'userPhone'          => $data['phone'] ? $data['phone'] : '',
                'orderStatus'        => '未付款',
                'paymentMethod'      => $PayName,
                'orderAmount'        => $data['order_amount'],
                'preferentialAmount' => $data['coupon_amount'],
                'orderActuallyPaid'  => $data['actually_paid'],
                'pay_coin_symbol'    => PayConst::$coinTypeSymbol[$data['pay_coin_type']] ?? '', // 支付符号,
                'orderNumber'        => $data['order_number'],
                'paymentLink'        => $data['domain'] . '/api/order/pay?order_id=' . $data['id'],
                'orderDetails'       => $data['domain'] . '/account?orderdetails=' . $data['id'],
                'goods'              => $goods_data_list,
                'userId'             => $data['user_id'],
                'dateTime'           => date('Y-m-d H:i:s'),
                'sumGoodsCnt'        => $sum_goods_cnt,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address'])
                ->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'placeOrder')->select(
                ['id',
                    'name',
                    'title',
                    'body',
                    'email_sender_id',
                    'email_recipient',
                    'status',
                    'alternate_email_id'
                ]
            )->first();
            if (empty($scene)) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            if ($scene->status == 0) {
                ReturnJson(false, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find(
                $scene->email_sender_id
            );
            //$scene->title = $scene->title.":  {$productsName}";
            $scene->title = $scene->title . ", 订单号是 {$data['order_number']}";
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
    public function payment($id)
    {
        try {
            $Order = Order::where('id', $id)->first();
            $data = $Order ? $Order->toArray() : [];
            if (!$data) {
                ReturnJson(false, '未找到订单数据');
            }
            $user = User::find($data['user_id']);
            $user = $user ? $user->toArray() : [];
            $data['domain'] = env('DOMAIN_URL', 'https://mmgcn.marketmonitorglobal.com.cn');
            $PayName = Pay::where('id', $data['pay_type'])->value('name');
            $orderGoodsList = OrderGoods::where('order_id', $Order['id'])->get()->toArray();
            $languageList = Languages::GetListById();
            $goods_data_list = [];
            $productsName = "";
            $sum_goods_cnt = 0;
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
                    ['url', 'thumb', 'name', 'id as product_id', 'published_date', 'category_id']
                )->where('id', $OrderGoods['goods_id'])->first();
                if (empty($products)) {
                    continue;
                }
                //拼接产品名称
                if (!empty($products->name)) {
                    $productsName .= $products->name . " ";
                }
                $goods_data = $products->toArray();
                $goods_data['goods_number'] = $OrderGoods['goods_number'] ?: 0;
                $sum_goods_cnt += $goods_data['goods_number'];
                $goods_data['language'] = $language;
                $goods_data['price_edition'] = isset($priceEdition['name']) && !empty($priceEdition['name']) ? $priceEdition['name'] : '';
                //$goods_data['goods_present_price'] = $OrderGoods['goods_present_price'];
                $goods_data['goods_present_price'] = $OrderGoods['goods_original_price'];
                $goods_data['goods_sum_price'] = bcmul(
                    $OrderGoods['goods_original_price'],
                    $OrderGoods['goods_number'],
                    2
                );
                $goods_data['thumb'] = rtrim(env('IMAGE_URL', ''), '/') . $products->getThumbImgAttribute();
                $goods_data['link'] = $this->getProductUrl($products);
                $goods_data_list[] = $goods_data;
            }
            $cityName = City::where('id', $data['city_id'])->value('name');
            $provinceName = City::where('id', $data['province_id'])->value('name');
            $addres = $provinceName . ' ' . $cityName . ' ' . $data['address'];
            $data2 = [
                'homePage'           => $data['domain'],
                'myAccountUrl'       => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl'       => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl'            => $data['domain'],
                'backendUrl'         => env('IMAGE_URL', ''),
                'userName'           => $data['username'] ?: '',
                'userEmail'          => $data['email'],
                'userCompany'        => $data['company'],
                'userAddress'        => $addres,
                'userPhone'          => $data['phone'] ?: '',
                'orderStatus'        => '已付款',
                'paymentMethod'      => $PayName,
                'orderAmount'        => $data['order_amount'],
                'preferentialAmount' => $data['coupon_amount'],
                'orderActuallyPaid'  => $data['actually_paid'],
                'pay_coin_symbol'    => PayConst::$coinTypeSymbol[$data['pay_coin_type']] ?? '', // 支付符号,
                'orderNumber'        => $data['order_number'],
                'paymentLink'        => $data['domain'] . '/api/order/pay?order_id=' . $data['id'],
                'orderDetails'       => $data['domain'] . '/account?orderdetails=' . $data['id'],
                'goods'              => $goods_data_list,
                'userId'             => $data['user_id'],
                'dateTime'           => date('Y-m-d H:i:s'),
                'sumGoodsCnt'        => $sum_goods_cnt,
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail', 'postCode', 'address'])->pluck('value', 'key')
                ->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'payment')->select(
                ['id',
                    'name',
                    'title',
                    'body',
                    'email_sender_id',
                    'email_recipient',
                    'status',
                    'alternate_email_id'
                ]
            )->first();
            //邮件标题
            $scene->title = $scene->title . ", 订单号是 " . $data['order_number'];
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
            \Log::error('ex:' . $e->getMessage());
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
            HandlerEmailJob::dispatch($scene, $email, $data, $senderEmail, $this->testEmail);

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
            $this->SendEmail(
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
                $this->SendEmail(
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
    }

    /**
     *
     * @param array $data
     *
     * @return array
     */
    private function getAreaName($data)
    {
        $area = '';
        if (!empty($data['province_id'])) {
            $area .= City::where('id', $data['province_id'])->value('name') . " ";
        }
        if (!empty($data['city_id'])) {
            $area .= City::where('id', $data['city_id'])->value('name');
        }

        return $area;
    }

    public function getProductUrl($products)
    {
        //https://mmgcn.marketmonitorglobal.com.cn/reports/332607/strain-wave-gear
        $domain = env('DOMAIN_URL', 'https://mmgcn.marketmonitorglobal.com.cn');

        return $domain . "/reports/{$products->product_id}/{$products->url}";
    }
}
