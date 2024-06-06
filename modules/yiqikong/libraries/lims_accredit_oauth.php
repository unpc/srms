<?php

class LIMS_Accredit_Oauth extends LIMS_Accredit {

    public function get_user_info($form = []) {
        $client = OAuth_Client::factory(Input::form('remote'));
        if (!$client) {
            URI::redirect('error/401');
        }
        return $client->apicall_current_user();
    }

    public function find_user_by_info ($info) {
        if ($remote_id = $info['remote_id']) {
            $user = O('user', ['remote_id' => $remote_id]);
            return $user;
        }
        return O('user');
    }
}