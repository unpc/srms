<?php

class Autocomplete_Controller extends AJAX_Controller
{

    public function users()
    {
        $s     = trim(Input::form('s'));
        $st    = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if ($start >= 100) {
            return;
        }

        $n = 5;
        if ($start == 0) {
            $n = 10;
        }

        if ($s) {
            $s        = Q::quote($s);
            $selector = "user[name*={$s}|name_abbr*={$s}][atime]:limit({$start}, {$n})";
        } else {
            $selector = "user[atime]:limit({$start},{$n})";
        }
        $users       = Q($selector);
        $users_count = $users->total_count();

        if ($start == 0 && !$users_count) {
            Output::$AJAX[] = [
                'html'    => (string) V('autocomplete/special/empty'),
                'special' => true,
            ];
        } else {
            foreach ($users as $user) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/user', ['user' => $user]),
                    'alt'  => $user->id,
                    'text' => $user->friendly_name(),
                ];
            }
            if ($start == 95) {
                Output::$AJAX[] = [
                    'html'    => (string) V('autocomplete/special/rest'),
                    'special' => true,
                ];
            }
        }
    }
}
