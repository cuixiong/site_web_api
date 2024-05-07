<?php

namespace App\Jobs;

use App\Http\Controllers\Common\SendEmailController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandlerEmailJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable;

    //Laravel 会自动序列化和反序列化传递给队列任务的 Eloquent 模型。
    //这意味着您无需手动处理序列化过程，这可以节省时间并降低错误风险。
    //, SerializesModels;  注释
    /**
     * @var Object $scene 邮件模版
     */
    public $scene;
    /**
     * @var string $email 邮件接收者
     */
    public $email;
    /**
     * @var array $data 邮件模板数据
     */
    public $data;
    /**
     * @var object $senderEmail 发邮件配置信息
     */
    public $senderEmail;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($scene, $email, $data, $senderEmail) {
        $this->scene = $scene;
        $this->email = $email;
        $this->data = $data;
        $this->senderEmail = $senderEmail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        try {
            (new SendEmailController())->handlerSendEmail(
                $this->scene, $this->email, $this->data, $this->senderEmail, true
            );
        } catch (\Exception $e) {
            $errData = [
                'scene'       => $this->scene,
                'email'       => $this->email,
                'data'        => $this->data,
                'senderEmail' => $this->senderEmail,
                'error'       => $e->getMessage(),
            ];
            \Log::error('发送邮件失败：错误信息与数据:'.json_encode($errData));
        }

        return true;
    }
}
