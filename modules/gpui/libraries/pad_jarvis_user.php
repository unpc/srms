<?php

class Pad_Jarvis_User
{
    public static function get_user_from_sec_card($e, $card_no)
    {
        $e->return_value = Q("user[gapper_id={$card_no}]:limit(1)")->current();
    }
}
