<?php

class CLI_Base_Convert
{
    public static function ip2area()
    {
        $_cache = [];
        foreach (Q('action[!area]') as $action) {
            if (!$_cache[$action->ip]) {
                $res = Base_Action::get_location($action->ip);
                if ($res['status'] === 0) {
                    $_cache[$action->ip] = $res['content'];
                } else {
                    continue;
                }
            }

            $data = $_cache[$action->ip];
            $action->area = $data['address'];
            $action->lon = $data['point']['x'];
            $action->lat = $data['point']['y'];
            $action->save();
        }
    }
}
