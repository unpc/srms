<?php

class AJAX_Controller extends _AJAX_Controller {

    final function index_heartbeat_check() {
		
        $auth_token = Auth::token();
        if ($_SESSION['heartbeat_token'] != $auth_token) {
            Output::$AJAX['error'] = true;
            //hasError时，进行Log增加
            Log::add(strtr('[application] hearbeat发生发生错误。当前token: %auth_token，heartbeat_token: %heartbeat_token', [
                '%auth_token'=> $auth_token,
                '%heartbeat_token'=> $_SESSION['heartbeat_token']
            ]), 'heartbeat');
        }

        $form = Input::form();

        foreach((array) $form['events'] as $e) {

            $event_name = $e['event'];
            $event_params = (array)$e['params'];

            $real_func = Config::get('heartbeat.'. $event_name);

            if ($real_func) {
                call_user_func_array($real_func, $event_params);
            }
        }
    }
}
