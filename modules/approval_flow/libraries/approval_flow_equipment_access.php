<?php
class Approval_Flow_Equipment_Access
{
    /**
     * @param [orm] $user
     * @param [string] $perm
     * @param [orm] $equipment
     */
    public static function approval_ACL($e, $user, $perm, $equipment, $options)
    {
        switch ($perm) {
            case '查看审核':
                if ($user->access('管理所有内容') || Q("{$user}<incharge {$equipment}")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }
}
