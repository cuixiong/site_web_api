<?php
/**
 * SenWordsService.php UTF-8
 * 敏感关键词过滤业务
 *
 * @date    : 2024/5/8 11:46 上午
 *
 * @license 这不是一个自由软件，未经授权不许任何使用和传播。
 * @author  : cuizhixiong <cuizhixiong@qyresearch.com>
 * @version : 1.0
 */

namespace App\Services;

use App\Models\SensitiveWords;

class SenWordsService {
    public static $senWords;  //关键词数组

    public static function getSenWords() {
        if (empty(self::$senWords)) {
            self::$senWords = SensitiveWords::query()->where("status", \App\Const\CommonConst::CONST_NORMAL_STATUS)
                                            ->pluck("word")->toArray();
        }

        return self::$senWords;
    }

    /**
     * 校验是否存在敏感词
     *
     * @param $content
     *
     * @return bool
     */
    public static function checkFitter($content) {
        $senWordsList = self::getSenWords();
        $checkRes = false;
        foreach ($senWordsList as $fillterRules) {
            if (mb_strpos($content, $fillterRules) !== false) {
                $checkRes = true;
                break;
            }
        }

        return $checkRes;
    }
}
