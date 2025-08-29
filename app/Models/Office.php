<?php

namespace App\Models;

use App\Models\Base;

class Office extends Base
{
    public static $daysMapEn = [
        'Sunday' => 0,
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6
    ];

    // 办公室是否处于营业时间
    public static function isWithinBusinessHours($businessString, \DateTime $now)
    {
        if(empty($businessString)){
            return false;
        }

        $currentDay  = $now->format('l');
        $currentTime = $now->format('H:i');

        // 正则表达式  Monday to Friday, 09:00 am - 06:00 pm
        $pattern = '/([A-Za-z]+)\s+to\s+([A-Za-z]+)[,\s]+(\d{1,2}:\d{2}\s*[ap]m)\s*-\s*(\d{1,2}:\d{2}\s*[ap]m)/i';

        if (!preg_match($pattern, $businessString, $matches)) {
            return false;
        }

        $startDay = ucfirst(strtolower(trim($matches[1])));
        $endDay = ucfirst(strtolower(trim($matches[2])));
        $startTimeStr = trim($matches[3]);
        $endTimeStr = trim($matches[4]);

        // 转换时间格式
        $startTime24 = date('H:i', strtotime($startTimeStr));
        $endTime24 = date('H:i', strtotime($endTimeStr));

        $daysMap = self::$daysMapEn;

        // 验证星期几名称是否有效
        if (!isset($daysMap[$startDay]) || !isset($daysMap[$endDay])) {
            return false;
        }

        $currentDayNum = $daysMap[$currentDay];
        $startDayNum = $daysMap[$startDay];
        $endDayNum = $daysMap[$endDay];

        // 检查星期范围
        $isDayInRange = false;
        if ($startDayNum <= $endDayNum) {
            $isDayInRange = ($currentDayNum >= $startDayNum && $currentDayNum <= $endDayNum);
        } else {
            $isDayInRange = ($currentDayNum >= $startDayNum || $currentDayNum <= $endDayNum);
        }
        if (!$isDayInRange) {
            return false;
        }

        // 检查时间范围
        if ($currentTime < $startTime24 || $currentTime > $endTime24) {
            return false;
        }

        return true;
    }
}
