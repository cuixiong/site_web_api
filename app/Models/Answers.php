<?php
/**
 * Answers.php UTF-8
 * 问答
 *
 * @date    : 2025/3/11 10:12 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Models;

class Answers extends Base {
    protected $table = 'answers';
    // 设置允许入库字段,数组形式
    protected $fillable
        = ['id', 'question_id', 'content', 'sort', 'status', 'created_by', 'created_at', 'updated_by', 'updated_at',
           'user_id', 'answer_at'];

    protected $appends = ['user_name' , 'answer_at_str'];

    public function getUserNameAttribute()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->value('username');
    }

    public function getAnswerAtStrAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['answer_at']);
    }

}
