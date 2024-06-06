<?php

class API_Announce {

    static function get_announces ($start = 0,$end = 10) {
        $announces = Q('announce')->limit($start, $end)->to_assoc('id', 'title');
        return $announces;
    }

    static function get_announce ($id) {
        $announce = O('announce', $id);

        if (!$announce->id) {
            throw new API_Exception(self::$errors[401], 401);
        }

        $result['title'] = $announce->title;
        $result['content'] = $announce->content;
        $result['ctime'] = $announce->ctime;

        return $result;
    }

}
