<?php

class Gapper_Gateway
{
    public static function get_user_from_gapper_id($e, $user_id)
    {
        $remote_user = Remote_Gateway::getUser($user_id);
        if ($remote_user['ref_no']) {
            $e->return_value = O('user', ['ref_no' => $remote_user['ref_no']]);
        } elseif ($remote_user['email']) {
            $e->return_value = O('user', ['email' => $remote_user['email']]);
        }
    }
}
