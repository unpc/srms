<?php
class Approval_Flow_Lab_Access
{
    /**
     * @param [orm] $user
     * @param [string] $perm
     * @param [orm] $lab
     */
    public static function approval_ACL($e, $user, $perm, $lab, $options)
    {
        switch ($perm) {
            case '查看审核':
                if (
                    (isset(Config::get('flow.eq_sample')['approve_pi']) || isset(Config::get('flow.eq_reserv')['approve_pi']))
                    &&
                    ($user->access('管理所有内容') || Q("{$user}<pi {$lab}")->total_count())
                ) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }
}
