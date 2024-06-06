<?php

class API_YiQiKong_Announce extends API_Common {

    public function read($data) {
        $this->_ready();
        $me = O('user', ['yiqikong_id'=>$data['user']]);
        if (!$me->id) throw new API_Exception;
        Cache::L('ME', $me);
        $announce = O('eq_announce', $data['announce']);
        if (!$announce->id) throw new API_Exception;

        return $me->connect($announce,'read');
    }

}