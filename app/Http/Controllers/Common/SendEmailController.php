<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrendsEmail;
use App\Models\City;
use App\Models\ContactUs;
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
    /**
     * 动态配置邮箱参数
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
     * @param string $email 接收邮箱号
     * @param string $templet 邮箱字符串的模板
     * @param array $data 渲染模板需要的数据
     * @param string $subject 邮箱标题
     * @param string $EmailUser 邮箱发件人
     */
    private function SendEmail($email, $templet, $data, $subject, $EmailUser, $name = 'trends')
    {
        $res = Mail::mailer($name)->to($email)->send(new TrendsEmail($templet, $data, $subject, $EmailUser));
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
            $data['token'] = base64_encode($token);
            $emailCode = 'signupToBeMember';
            $dataQuery = [
                'timestamp' => time(),
                'randomstr' => '123',
                'authkey' => '123',
                'sign' => $data['token'],
            ];
            $verifyUrl = $data['domain'] . '/?verifyemail=' . $emailCode . '&' . http_build_query($dataQuery);
            $data2 = [
                'homePage' => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl' => $data['domain'],
                'backendUrl' => env('IMAGE_URL'),
                'verifyUrl' => $verifyUrl,
                'userName' => $data['name'],
                'area' => City::where('id', $data['area_id'])->value('name'),
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'register')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }

            if ($scene->status == 0) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($user['email'], $scene->body, $data, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($user['email'], $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
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
                'homePage' => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl' => $data['domain'],
                'backendUrl' => env('IMAGE_URL'),
                'userName' => $data['name'],
                'area' => City::where('id', $data['area_id'])->value('name'),
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'registerSuccess')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }

            if ($scene->status == 0) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($user['email'], $scene->body, $data, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($user['email'], $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
        }
    }

    /**
     * reset password eamil send
     * @param use Illuminate\Http\Request $request;
     * @return response Code
     */
    public function ResetPassword($email)
    {
        try {
            $user = User::where('email', $email)->first();
            if (empty($user)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_undefined'));
            }
            $user = $user->toArray();
            $token = $user['email'] . '&' . $user['id'];
            $user['token'] = base64_encode($token);
            $user['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $scene = EmailScene::where('action', 'password')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($email, $scene->body, $user, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($email, $scene->body, $user, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            ReturnJson(true, trans()->get('lang.eamail_success'));
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
        }
    }

    // 申请样本
    public function productSample($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            // $data['country'] = Country::where('id',$data['country_id'])->value('name');
            $data['province'] = City::where('id', $data['province_id'])->value('name') ?? '';
            $data['city'] = City::where('id', $data['city_id'])->value('name') ?? '';
            $token = $data['email'] . '&' . $data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $data2 = [
                'homePage' => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl' => $data['domain'],
                'userName' => $data['name'] ? $data['name'] : '',
                'email' => $data['email'],
                'company' => $data['company'],
                'area' => $data['province'] . $data['city'],
                'phone' => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => $data['buy_time'],
                'content' => $data['content'],
                'backendUrl' => env('IMAGE_URL'),
                'plantTimeBuy' => $data['buy_time'],
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'productSample')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }

            if ($scene->status == 0) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            foreach ($emails as $email) {
                try {
                    $this->SendEmail($email, $scene->body, $data, $scene->title, $senderEmail->email);
                } catch (\Exception $e) {
                    if ($scene->alternate_email_id) {
                        $this->SendEmail($email, $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
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
            $data2 = [
                'homePage' => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl' => $data['domain'],
                'userName' => $data['name'] ? $data['name'] : '',
                'email' => $data['email'],
                'company' => $data['company'],
                'area' => City::where('id', $data['area_id'])->value('name'),
                'phone' => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => $data['buy_time'],
                'content' => $data['remarks'],
                'backendUrl' => env('IMAGE_URL'),
                'plantTimeBuy' => $data['buy_time'],
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'contactUs')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }

            if ($scene->status == 0) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            foreach ($emails as $email) {
                try {
                    $this->SendEmail($email, $scene->body, $data, $scene->title, $senderEmail->email);
                } catch (\Exception $e) {
                    if ($scene->alternate_email_id) {
                        $this->SendEmail($email, $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
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
            $data2 = [
                'homePage' => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl' => $data['domain'],
                'userName' => $data['name'] ? $data['name'] : '',
                'email' => $data['email'],
                'company' => $data['company'],
                'area' => City::where('id', $data['area_id'])->value('name'),
                'phone' => $data['phone'] ? $data['phone'] : '',
                'plantTimeBuy' => $data['buy_time'],
                'content' => $data['remarks'],
                'backendUrl' => env('IMAGE_URL'),
                'plantTimeBuy' => $data['buy_time'],
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'customized')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }

            if ($scene->status == 0) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            foreach ($emails as $email) {
                try {
                    $this->SendEmail($email, $scene->body, $data, $scene->title, $senderEmail->email);
                } catch (\Exception $e) {
                    if ($scene->alternate_email_id) {
                        $this->SendEmail($email, $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
        }
    }

    // 下单后未付款
    public function placeOrder($id)
    {
        try {
            $OrderGoods = OrderGoods::where('id', $id)->first();
            $Order = Order::where('id', $OrderGoods['order_id'])->first();
            $data = $Order ? $Order->toArray() : [];
            if (!$data) {
                ReturnJson(false, '未找到订单数据');
            }
            $user = User::find($data['user_id']);
            $user = $user ? $user->toArray() : [];
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $PayName = Pay::where('id', $data['pay_type'])->value('name');

            $priceEdition = Redis::hget(PriceEditionValues::RedisKey, $OrderGoods['price_edition']);
            $priceEdition = json_decode($priceEdition, true);
            $language = Redis::hget(Languages::RedisKey, $priceEdition['language_id']);
            $language = json_decode($language, true);
            $language = isset($language['name']) ? $language['name'] : '';
            $Products = Products::select(['url as link', 'thumb', 'name', 'id as product_id', 'published_date', 'category_id'])->whereIn('id', explode(',', $OrderGoods['goods_id']))->get()->toArray();
            if ($Products) {
                foreach ($Products as $key => $value) {
                    $Products[$key]['goods_number'] = $OrderGoods['goods_number'] ? intval($OrderGoods['goods_number']) : 0;
                    $Products[$key]['language'] = $language;
                    $Products[$key]['price_edition'] = $priceEdition['name'];
                    $Products[$key]['goods_present_price'] = $OrderGoods['goods_present_price'];
                    $Products[$key]['thumb'] = rtrim(env('IMAGE_URL', ''), '/') . $value['thumb'];
                    if (empty($value['thumb'])) {
                        $categoryThumb = ProductsCategory::where('id', $value['category_id'])->value('thumb');
                        $Products[$key]['thumb'] = rtrim(env('IMAGE_URL', ''), '/') . $categoryThumb;
                    } else {
                        $Products[$key]['thumb'] = rtrim(env('IMAGE_URL', ''), '/') . $value['thumb'];
                    }
                }
            }
            $cityName = City::where('id', $data['city_id'])->value('name');
            $provinceName = City::where('id', $data['province_id'])->value('name');
            $addres = $provinceName . ' ' . $cityName . ' ' . $data['address'];
            $data2 = [
                'homePage' => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl' => $data['domain'],
                'backendUrl' => env('IMAGE_URL', ''),
                'userName' => $data['username'] ? $data['username'] : '',
                'userEmail' => $data['email'],
                'userCompany' => $data['company'],
                'userAddress' => $addres,
                'userPhone' => $data['phone'] ? $data['phone'] : '',
                'orderStatus' => '未付款',
                'paymentMethod' => $PayName,
                'orderAmount' => $data['order_amount'],
                'preferentialAmount' => $data['order_amount'] - $data['actually_paid'],
                'orderActuallyPaid' => $data['actually_paid'],
                'orderNumber' => $data['order_number'],
                'paymentLink' => $data['domain'] . '/api/order/pay?order_id=' . $data['id'],
                'orderDetails' => $data['domain'] . '/account?orderdetails=' . $data['id'],
                'goods' => $Products,
                'userId' => $user['id']
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'placeOrder')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }

            if ($scene->status == 0) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            foreach ($emails as $email) {
                try {
                    $this->SendEmail($email, $scene->body, $data, $scene->title, $senderEmail->email);
                } catch (\Exception $e) {
                    if ($scene->alternate_email_id) {
                        $this->SendEmail($email, $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
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
            $OrderGoods = OrderGoods::where('order_id', $Order['id'])->first();
            $user = User::find($data['user_id']);
            $user = $user ? $user->toArray() : [];
            $data['domain'] = 'http://' . $_SERVER['SERVER_NAME'];
            $PayName = Pay::where('id', $data['pay_type'])->value('name');

            $priceEdition = Redis::hget(PriceEditionValues::RedisKey, $OrderGoods['price_edition']);
            $priceEdition = json_decode($priceEdition, true);
            $language = Redis::hget(Languages::RedisKey, $priceEdition['language_id']);
            $language = json_decode($language, true);
            $language = isset($language['name']) ? $language['name'] : '';
            $Products = Products::select(['url as link', 'thumb', 'name', 'id as product_id', 'published_date', 'category_id'])->whereIn('id', explode(',', $OrderGoods['goods_id']))->get()->toArray();
            if ($Products) {
                foreach ($Products as $key => $value) {
                    $Products[$key]['goods_number'] = $OrderGoods['goods_number'] ? intval($OrderGoods['goods_number']) : 0;
                    $Products[$key]['language'] = $language;
                    $Products[$key]['price_edition'] = $priceEdition['name'];
                    $Products[$key]['goods_present_price'] = $OrderGoods['goods_present_price'];
                    if (empty($value['thumb'])) {
                        $categoryThumb = ProductsCategory::where('id', $value['category_id'])->value('thumb');
                        $Products[$key]['thumb'] = rtrim(env('IMAGE_URL', ''), '/') . $categoryThumb;
                    } else {
                        $Products[$key]['thumb'] = rtrim(env('IMAGE_URL', ''), '/') . $value['thumb'];
                    }
                }
            }
            $cityName = City::where('id', $data['city_id'])->value('name');
            $provinceName = City::where('id', $data['province_id'])->value('name');
            $addres = $provinceName . ' ' . $cityName . ' ' . $data['address'];
            $data2 = [
                'homePage' => $data['domain'],
                'myAccountUrl' => rtrim($data['domain'], '/') . '/account/account-infor',
                'contactUsUrl' => rtrim($data['domain'], '/') . '/contact-us',
                'homeUrl' => $data['domain'],
                'backendUrl' => env('IMAGE_URL', ''),
                'userName' => $data['username'] ? $data['username'] : '',
                'userEmail' => $data['email'],
                'userCompany' => $data['company'],
                'userAddress' => $addres,
                'userPhone' => $data['phone'] ? $data['phone'] : '',
                'orderStatus' => '已付款',
                'paymentMethod' => $PayName,
                'orderAmount' => $data['order_amount'],
                'preferentialAmount' => $data['order_amount'] - $data['actually_paid'],
                'orderActuallyPaid' => $data['actually_paid'],
                'orderNumber' => $data['order_number'],
                'paymentLink' => $data['domain'] . '/api/order/pay?order_id=' . $data['id'],
                'orderDetails' => $data['domain'] . '/account?orderdetails=' . $data['id'],
                'goods' => $Products,
                'userId' => $user['id']
            ];
            $siteInfo = SystemValue::whereIn('key', ['siteName', 'sitePhone', 'siteEmail'])->pluck('value', 'key')->toArray();
            if ($siteInfo) {
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $data = array_merge($data2, $data);
            $scene = EmailScene::where('action', 'payment')->select(['id', 'name', 'title', 'body', 'email_sender_id', 'email_recipient', 'status', 'alternate_email_id'])->first();
            // 收件人的数组
            $emails = explode(',', $scene->email_recipient);
            if (empty($scene)) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }

            if ($scene->status == 0) {
                ReturnJson(FALSE, trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if ($scene->alternate_email_id) {
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name', 'email', 'host', 'port', 'encryption', 'password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig, 'backups'); // 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $senderEmail->email);
            } catch (\Exception $e) {
                if ($scene->alternate_email_id) {
                    $this->SendEmail($data['email'], $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                }
            }
            foreach ($emails as $email) {
                try {
                    $this->SendEmail($email, $scene->body, $data, $scene->title, $senderEmail->email);
                } catch (\Exception $e) {
                    if ($scene->alternate_email_id) {
                        $this->SendEmail($email, $scene->body, $data, $scene->title, $BackupSenderEmail->email, 'backups');
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE, $e->getMessage());
        }
    }
}
