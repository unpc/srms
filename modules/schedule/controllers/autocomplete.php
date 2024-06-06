<?php

class Autocomplete_Controller extends AJAX_Controller {
	
	function users() {
        $s = Q::quote(trim(Input::form('s')));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
        $users = Q("user[!hidden][atime][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
        $users_count = $users->total_count();

        if ($start == 0 && !$users_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($users as $user) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/user', ['user'=>$user]),
                    'alt' => $user->id,
                    'text' => $user->friendly_name(),
                ];
            }
//            $rest = $users->total_count() - $users_count;
            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
	}
	

	function role() {

        $me = L('ME');
        $roles = [];
        foreach(L('ROLES') as $role) {
            if($role->id > 0 ) {
                if ($me->is_allowed_to('查看', $role)) {
                    $roles[$role->id] = $role->name;
                }
            }
        }
        $roles = array_map(function ($val) { return H($val); }, $roles);

        $s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
        $count = 10;
        $max_count = Config::get('messages.role_list_max_count', $n);

        $selector_roles = [];

        foreach ($roles as $id => $role_name) {
            if (preg_match('/'.$s.'/i', $role_name, $match)) {
                $selector_roles[$id] = $role_name;
            }
        }

        $selector_count = count($selector_roles);

        if ($start == 0 && !$selector_count) {
            Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/empty'),
                    'special' => TRUE
            ];
        }
        else {
            $showd_role_count  = 0;
			$selector_roles = array_slice($selector_roles, $start, $max_count, TRUE);
            foreach ($selector_roles as $id => $role_name) {
                Output::$AJAX[] = [
                        'html' => (string) V('autocomplete/small_tag', ['tag' => $role_name]),
                        'alt' => $id,
                        'text' => $role_name,
                ];
            }

//            $rest = $selector_count - $max_count;

            if ($start == 95) {
                Output::$AJAX[] = [
                        'html' => (string) V('autocomplete/special/rest'),
                        'special' => TRUE
                ];
            }
        }
    }

    function group() {
        $root = Tag_Model::root('group');

        $s = Q::quote(trim(Input::form('s')));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
        if ($s) {
            $tags = Q("tag_group[root={$root}][name*={$s}|name_abbr*={$s}]");
        }
        else {
            $tags = Q("tag_group[root={$root}]");
        }

        $count = $tags->total_count();
        $max_count = Config::get('messages.group_list_max_count', $n);
		$tags = $tags->limit($start, $max_count);

        if ($start == 0 && !$count) {
            Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/empty'),
                    'special' => TRUE
                    ];
        }
        else {
            foreach($tags as $tag){
                Output::$AJAX[] = [
                        'html' => (string) V('autocomplete/tag', ['tag'=>$tag, 'tag_root'=>$root]),
                        'alt' => $tag->id,
                        'text' => $tag->name,
                ];
            }
//            $rest = $tags->total_count() - $showd_group_count;
            if ($start == 95) {
                Output::$AJAX[] = [
                        'html' => (string) V('autocomplete/special/rest'),
                        'special' => TRUE
                ];
            }
        }
    }
}
