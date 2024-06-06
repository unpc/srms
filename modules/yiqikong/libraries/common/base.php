<?php

class Common_Base {

    protected static $errors = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        404 => 'Not Found',
        1001 => '请求非法!',
        1002 => '您没有权限进行该操作!',
        1010 => '用户信息不合法!',
        2001 => '未找到相应仪器!',
        2002 => '该仪器不接受送样!',
        2003 => '该仪器不接受预约!',
        2004 => '还需填写额外信息, 若无显示请至网页端进行操作!',
        3001 => '您账号余额不足!'
    ];

    const STATE_PENDING = 0;
    const STATE_SUCCESS = 1;
    const STATE_FAILED = 2;
    const STATE_DELETED = 3;
    const STATE_UPDATE_PENDING = 4;
    const STATE_UPDATE = 5;
    const STATE_UPDATE_FAILED = 6;

    const SEND_PENDING = 1; //未发送
    const SEND_ENDING = 2;
    const SEND_SUCCESS = 3; //已发送
    const SEND_NOPE = 4; //不发送

    const STATUS_APPROVE = 0;
    const STATUS_DONE = 1;
    const STATUS_REJECTED = 2;

    public static function _MAKEUSER($id, $lims_id = 0) {
        if ($lims_id){
            $user =  O('user', ['id' => $lims_id]);
        }else{
            if (is_numeric($id) && $id > 0) $user = O('user', ['yiqikong_id' => $id]);
            else $user = O('user', ['email' => $id]);
        }
        if (!$user->id) throw new API_Exception('没找到对应用户', 404);
        return $user;
    }
}
