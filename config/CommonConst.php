<?php

class CommonConst {
    const CONST_DELETED        = 1; //已被删除
    const CONST_NOT_DELETE     = 2; //未删除
    const CONST_NORMAL_STATUS  = 1; /* 正常状态 */
    const CONST_DISABLE_STATUS = 2;  /* 禁用状态 */
    const CONST_IS_EXIST       = 2; /* 存在 */
    const CONST_IS_NO_EXIST    = 1; /* 不存在 */
    /**
     * 获取通用状态
     *
     * @param      $status
     * @param bool $all
     *
     * @return array|bool|mixed
     */
    public static function getStatusMsg($status, $all = false) {
        $_data = [
            CommonConst::CONST_NORMAL_STATUS  => '正常',
            CommonConst::CONST_DISABLE_STATUS => '禁用'
        ];
        if (true == $all) {
            return $_data;
        }

        return isset($_data[$status]) ? $_data[$status] : false;
    }
}



