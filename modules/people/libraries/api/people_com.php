<?php

class API_People_Com extends API_Component {

    function userApproval ($id) {
        $me = $this->user();
        if (!$me->id) return FALSE;
        
        $user = O('user', $id);
        if ($me->is_allowed_to('激活', $user)) {
            $user->auditor = $me;
            $user->atime = Date::time();
            return $user->save();
        }

        return FALSE;
    }
}