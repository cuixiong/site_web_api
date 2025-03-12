<?php
/**
 * Questions.php UTF-8
 * 问答
 *
 * @date    : 2025/3/11 10:12 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Models;

class Questions extends Base {
    protected $table = 'questions';
    // 设置允许入库字段,数组形式
    protected $fillable
        = ['id', 'title', 'keywords', 'sort', 'status', 'created_by', 'created_at', 'updated_by', 'updated_at',
           'user_id', 'ask_at'];

    protected $appends = ['user_name' , 'ask_at_str' , 'answer_cnt'];

    public function getUserNameAttribute()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->value('username');
    }

    public function getAskAtStrAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['ask_at']);
    }

    public function getAnswerCntAttribute()
    {
        return $this->hasMany(Answers::class, 'question_id', 'id')->count();
    }

}
