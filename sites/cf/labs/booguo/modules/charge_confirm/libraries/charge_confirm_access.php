<?php

class Charge_Confirm_Access {

    static function charge_ACL($e, $user, $perm, $object, $options) {
        $me = L('ME');

        switch ($perm) {
            case '审核':
                if (Q("{$me} equipment.incharge")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '确认':
                if (Q("{$me} equipment.incharge")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '打印':
                if ($object->confirm == Neu_EQ_Charge_Model::CONFIRM_PRINT || $object->confirm == Neu_EQ_Charge_Model::CONFIRM_CONFIRM) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
        }
    }
}
