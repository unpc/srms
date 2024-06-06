<?php

class Autocomplete_Controller extends AJAX_Controller
{

    function user()
    {
        $query = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }

        if ($start >= 100) return;
        $n = 5;
        if ($start == 0) $n = 10;

        if ($query) {
            $condition = [
                'ref_no$' => "*{$query}*",
                'pg' => ($st / $n) ?: 1,
                'pp' => $n,
            ];
        } else {
            $condition = [
                'pg' => ($st / $n) ?: 1,
                'pp' => $n,
            ];
        }

        $usersRes = Gapper_User::get_remote_user($condition);
        $users_count = count($usersRes['items']);
        $users = $usersRes['items'];

        if ($start == 0 && !$users_count) {
            Output::$AJAX[] = [
                'html' => (string)V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        } else {
            foreach ($users as $user) {
                $showStrt = $user['name'] . (!empty($user['ref_no']) ? "({$user['ref_no']})" : '');
                $userAlt = [
                    'gapper_id' => $user['id'],
                    'gapper_name' => $user['name'],
                    'gapper_ref_no' => $user['ref_no'],
                    'gapper_email' => $user['email'],
                    'gapper_avatar' => $user['avatar'],
                ];
                Output::$AJAX[] = [
                    'html' => (string)V('autocomplete/user', ['user' => $user]),
                    'alt' => json_encode($userAlt),
                    'text' => I18N::T('labs', '%user', ['%user' => $showStrt]),
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string)V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }
}
