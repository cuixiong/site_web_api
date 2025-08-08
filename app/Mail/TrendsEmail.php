<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

/**
 * 动态发邮类
 * 不包含队列等功能
 */
class TrendsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $templet;
    public $data;
    public $title;
    public $EmailUser;
    public $templetFile;
    public $sendUserName;
    /**
     * Create a new message instance.
     * @param string $templet
     * @param array $data
     * @param string $title
     * @param string $EmailUser
     * @param string $templetFile
     * @return void
     */
    public function __construct($templet,$data,$title,$EmailUser,$sendUserName='')
    {
        $this->templet = $templet;
        $this->data = $data;
        $this->title = $title;
        $this->EmailUser = $EmailUser;
        $this->sendUserName = $sendUserName;
    }

    /**
     * 析构方法:删除模板文件
     * @return void
     */
    public function __destruct()
    {
        if(file_exists($this->templetFile)){
            unlink($this->templetFile);
        }
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            //from: $this->EmailUser,
            from: new Address($this->EmailUser , $this->sendUserName),
            subject: $this->title,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        //$name = 'email_'.time();// 文件名      宝哥埋大坑啊
        $name = 'email_'.time().uniqid(); // 生成唯一ID
        $this->templetFile = resource_path('views/emails/'.$name.'.blade.php');
        $view = 'emails.'.$name;// 渲染模板名称
        file_put_contents($this->templetFile,$this->templet);
        if(!file_exists($this->templetFile)){
            ReturnJson(FALSE,'模板文件不存在,请重试！');
        }
        $res =  new Content(
            view: $view,
            with: $this->data,
        );
        return $res;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
