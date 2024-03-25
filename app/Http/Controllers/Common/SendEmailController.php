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
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\Products;
use App\Models\SystemValue;
use App\Models\User;

class SendEmailController extends Controller
{
    /**
     * 动态配置邮箱参数
     * @param array $data 邮箱配置参数信息
     */
    private function SetConfig($data,$name = 'trends'){
        $keys = ['transport','host','port','encryption','username','password','timeout','local_domain'];
        foreach ($data as $key => $value) {
            if(in_array($key,$keys)){
                Config::set('mail.mailers.'.$name.'.'.$key,$value,true);
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
    private function SendEmail($email,$templet,$data,$subject,$EmailUser,$name = 'trends')
    {
        $res = Mail::mailer($name)->to($email)->send(new TrendsEmail($templet,$data,$subject,$EmailUser));
        return $res;
    }

    // 注册账号发送邮箱
    public function Register($id)
    {
        try {
            $user = User::find($id);
            $user = $user ? $user->toArray() : [];
            $token = $user['email'].'&'.$user['id'];
            $user['token'] = base64_encode($token);
            $user['domain'] = 'http://'.$_SERVER['SERVER_NAME'];
            $siteInfo = SystemValue::whereIn('key',['siteName','sitePhone'])->pluck('value','key')->toArray();
            if($siteInfo){
                foreach ($siteInfo as $key => $value) {
                    $user[$key] = $value;
                }
            }
            $scene = EmailScene::where('action','register')->select(['id','name','title','body','email_sender_id','email_recipient','status','alternate_email_id'])->first();
            if(empty($scene)){
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }

            if($scene->status == 0)
            {
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if($scene->alternate_email_id){
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig,'backups');// 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($user['email'],$scene->body,$user,$scene->title,$senderEmail->email);
            } catch (\Exception $e) {
                if($scene->alternate_email_id){
                    $this->SendEmail($user['email'],$scene->body,$user,$scene->title,$BackupSenderEmail->email,'backups');
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE,$e->getMessage());
        }
    }

    /**
     * reset password eamil send
     * @param use Illuminate\Http\Request $request;
     * @return response Code
     */
    public function ResetPassword($email){
        try {
            $user = User::where('email',$email)->first();
            if(empty($user)){
                ReturnJson(FALSE,trans()->get('lang.eamail_undefined'));
            }
            $user = $user->toArray();
            $token = $user['email'].'&'.$user['id'];
            $user['token'] = base64_encode($token);
            $user['domain'] = 'http://'.$_SERVER['SERVER_NAME'];
            $scene = EmailScene::where('action','password')->select(['id','name','title','body','email_sender_id','email_recipient','status','alternate_email_id'])->first();
            if(empty($scene)){
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if($scene->alternate_email_id){
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig,'backups');// 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($email,$scene->body,$user,$scene->title,$senderEmail->email);
            } catch (\Exception $e) {
                if($scene->alternate_email_id){
                    $this->SendEmail($email,$scene->body,$user,$scene->title,$BackupSenderEmail->email,'backups');
                }
            }
            ReturnJson(true,trans()->get('lang.eamail_success'));
        } catch (\Exception $e) {
            ReturnJson(FALSE,$e->getMessage());
        }
    }

    // 申请样本
    public function productSample($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $data['area_id'] = City::where('id',$data['area_id'])->value('name');
            $token = $data['email'].'&'.$data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://'.$_SERVER['SERVER_NAME'];
            $siteInfo = SystemValue::whereIn('key',['siteName','sitePhone','siteEmail'])->pluck('value','key')->toArray();
            if($siteInfo){
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $scene = EmailScene::where('action','productSample')->select(['id','name','title','body','email_sender_id','email_recipient','status','alternate_email_id'])->first();
            if(empty($scene)){
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }

            if($scene->status == 0)
            {
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if($scene->alternate_email_id){
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig,'backups');// 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'],$scene->body,$data,$scene->title,$senderEmail->email);
            } catch (\Exception $e) {
                if($scene->alternate_email_id){
                    $this->SendEmail($data['email'],$scene->body,$data,$scene->title,$BackupSenderEmail->email,'backups');
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE,$e->getMessage());
        }
    }

    // 联系我们
    public function contactUs($id)
    {
        try {
            $ContactUs = ContactUs::find($id);
            $data = $ContactUs ? $ContactUs->toArray() : [];
            $data['area_id'] = City::where('id',$data['area_id'])->value('name');
            $token = $data['email'].'&'.$data['id'];
            $data['token'] = base64_encode($token);
            $data['domain'] = 'http://'.$_SERVER['SERVER_NAME'];
            $siteInfo = SystemValue::whereIn('key',['siteName','sitePhone','siteEmail'])->pluck('value','key')->toArray();
            if($siteInfo){
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $scene = EmailScene::where('action','contactUs')->select(['id','name','title','body','email_sender_id','email_recipient','status','alternate_email_id'])->first();
            if(empty($scene)){
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }

            if($scene->status == 0)
            {
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if($scene->alternate_email_id){
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig,'backups');// 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'],$scene->body,$data,$scene->title,$senderEmail->email);
            } catch (\Exception $e) {
                if($scene->alternate_email_id){
                    $this->SendEmail($data['email'],$scene->body,$data,$scene->title,$BackupSenderEmail->email,'backups');
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE,$e->getMessage());
        }
    }

    // 下单后未付款
    public function placeOrder($id)
    {
        try {
            $OrderGoods = OrderGoods::find($id);
            $order = Order::find($OrderGoods->order_id);
            $user = User::find($order->user_id);
            $data = $order->toArray();
            $user = $user->toArray();
            $products = Products::whereIn('id',$OrderGoods->goods_id)->select(['link','name','id as product_id','published_date','language','price_edition','goods_number','goods_present_price'])->get()->toArray();
            $data = [
                'userName' => $user['username'],
                'userEmail' => $user['email'],
                'userCompany' => $user['company'],
                'userAddress' => City::where('id',$user['area_id'])->value('name'),
                'userPhone' => $user['phone'],
                'orderStatus' => $order['status'],
                'paymentMethod' => $order['pay_type'],
                'orderAmount' => $order['order_amount'],
                'preferentialAmount' => $order['order_amount'] - $order['actually_paid'],
                'orderActuallyPaid' => $order['actually_paid'],
                'goods' => $products,
            ];
            $data['domain'] = 'http://'.$_SERVER['SERVER_NAME'];
            $siteInfo = SystemValue::whereIn('key',['siteName','sitePhone','siteEmail'])->pluck('value','key')->toArray();
            if($siteInfo){
                foreach ($siteInfo as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $scene = EmailScene::where('action','placeOrder')->select(['id','name','title','body','email_sender_id','email_recipient','status','alternate_email_id'])->first();
            if(empty($scene)){
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }

            if($scene->status == 0)
            {
                ReturnJson(FALSE,trans()->get('lang.eamail_error'));
            }
            $senderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->email_sender_id);
            // 邮箱账号配置信息
            $config = [
                'host' =>  $senderEmail->host,
                'port' =>  $senderEmail->port,
                'encryption' =>  $senderEmail->encryption,
                'username' =>  $senderEmail->email,
                'password' =>  $senderEmail->password
            ];
            $this->SetConfig($config);
            if($scene->alternate_email_id){
                // 备用邮箱配置信息
                $BackupSenderEmail = Email::select(['name','email','host','port','encryption','password'])->find($scene->alternate_email_id);
                $BackupConfig = [
                    'host' =>  $BackupSenderEmail->host,
                    'port' =>  $BackupSenderEmail->port,
                    'encryption' =>  $BackupSenderEmail->encryption,
                    'username' =>  $BackupSenderEmail->email,
                    'password' =>  $BackupSenderEmail->password
                ];
                $this->SetConfig($BackupConfig,'backups');// 若发送失败，则使用备用邮箱发送
            }
            try {
                $this->SendEmail($data['email'],$scene->body,$data,$scene->title,$senderEmail->email);
            } catch (\Exception $e) {
                if($scene->alternate_email_id){
                    $this->SendEmail($data['email'],$scene->body,$data,$scene->title,$BackupSenderEmail->email,'backups');
                }
            }
            return true;
        } catch (\Exception $e) {
            ReturnJson(FALSE,$e->getMessage());
        }
    }
}
