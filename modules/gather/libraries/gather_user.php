<?php

class Gather_User
{

    static function user_extra_keys($e, $user, $info)
    {
        $info['site'] = $user->source_name;
        $info['site_id'] = $user->source_id;
    }

}
