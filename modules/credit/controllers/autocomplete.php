<?php

class Autocomplete_Controller extends AJAX_Controller
{
    public function user($id = 0)
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
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
        $now = Date::time();

        if ($s) {
            $s = Q::quote($s);
            $selector = "user[name*={$s}|name_abbr*={$s}][!hidden][atime][dto=0,{$now}~]:limit({$start},{$n})";
        } else {
            $selector = "user[!hidden][atime][dto=0,{$now}~]:limit({$start},{$n})";
        }

        $users = Q($selector);
        $users_count = $users->total_count();

        if ($start == 0 && !$users_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => true
            ];
        } else {
            foreach ($users as $user) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/user', ['user'=>$user]),
                    'alt' => $user->id,
                    'text' => $user->friendly_name(),
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => true
                ];
            }
        }
    }
    public function labs()
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
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
        /*
        NO.TASK#312(guoping.zhang@2011.01.07)
        查询限制数量：10
        */
        if ($s) {
            $s = Q::quote($s);
            $labs = Q("lab[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
        } else {
            $labs = Q("lab:limit({$start},{$n})");
        }
        $labs_count = $labs->total_count();

        if ($start == 0 && !$labs_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => true
            ];
        } else {
            foreach ($labs as $lab) {
                Output::$AJAX[] = [
                    'html' => (string) V('equipments:autocomplete/lab', ['lab'=>$lab]),
                    'alt' => $lab->id,
                    'text' => $lab->name,
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => true
                ];
            }
        }
    }

    public function groups()
    {
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
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
        $root = Tag_Model::root('group');
        if ($s) {
            $s = Q::quote($s);
            $groups = Q("tag_group[root={$root}][name*={$s}|name_abbr*={$s}]:limit({$start}, {$n})");
        } else {
            $groups = Q("tag_group[root={$root}]:limit({$start}, {$n})");
        }
        $groups_count = $groups->total_count();

        if ($start == 0 && !$groups_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => true
            ];
        } else {
            foreach ($groups as $group) {
                Output::$AJAX[] = [
                    'html' => (string) V('equipments:autocomplete/group', ['group'=>$group]),
                    'alt' => $group->id,
                    'text' => $group->name,
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => true
                ];
            }
        }
    }
}
