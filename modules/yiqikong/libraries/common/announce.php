<?php

class Common_Announce extends Common_Base
{
    public static function read($data)
    {
        $me = parent::_MAKEUSER($data['user'], $data['user_local']);
        if (!$me->id) throw new API_Exception;
        Cache::L('ME', $me);
        $announce = O('eq_announce', $data['announce']);
        if (!$announce->id) throw new API_Exception;

        return $me->connect($announce,'read');
    }
}