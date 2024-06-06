<?php

class Autocomplete_Controller extends AJAX_Controller {

    function user($lab_id=0) {
        $query = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }

        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;

        $lab = O('lab', $lab_id);
        $default_lab = O('lab', Lab::get('default_lab_id'));
        /*
        NO.TASK#312(guoping.zhang@2011.01.07)
        查询限制数量：10
        */
        if ($query) {
            $query = Q::quote($query);
            $selector = "user[!hidden][name*={$query}|name_abbr*={$query}][atime>0]:not(lab user.owner):limit({$start},{$n})";
        }
        else {
            $selector = "user[!hidden][atime>0]:not(lab user.owner):limit({$start},{$n})";
        }

        // if ($lab->id) {
        //     $pre_selector = "{$lab} ";
        // }
        // $selector = $pre_selector.$selector;
        $users = Q($selector);
        $users_count = $users->length();

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
					/* 
					 * BUG #660::实验室管理员添加实验室从目前成员选择PI时有问题
					 * 不显示lab名称
					 */
					'text'  => I18N::T('labs', '%user', ['%user'=>H($user->name)]),
				];
			}

            //$rest = $users->total_count() - $users_count;
            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }

    function tags($lab_id=NULL){

        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }

        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;

        $root = Tag_Model::root('lab');
        /*
        NO.TASK#312(guoping.zhang@2011.01.07)
        查询限制数量：10
        */
        if ($s) {
            $s = Q::quote($s);
            $tags = Q("tag[root={$root}][name*={$s}]:limit({$start}, {$n})");
        }
        else {
            $tags = Q("tag[root={$root}]:limit({$start}, {$n})");
        }

        $tags_count = $tags->length();

        if ($start == 0 && !$tags_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach($tags as $tag){
                Output::$AJAX[] = [
                    'html' => (string) V('labs:autocomplete/tag', ['tag'=>$tag->name]),
                    'alt' => $tag->id,
                    'text' => H($tag->name),
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }

    function lab_users($lab_id=0) {

        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }

        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;

        if ($s) {
            $s = Q::quote($s);
            $users = Q("lab#{$lab_id} user[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
        } else {
            $users = Q("lab#{$lab_id} user:limit({$start},{$n})");
        }
        $users_count = $users->length();

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
					/* 
					 * BUG #660::实验室管理员添加实验室从目前成员选择PI时有问题
					 * 不显示lab名称
					 */
					'text'  => I18N::T('labs', '%user', ['%user'=>H($user->name)]),
				];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }

    function lab($user_id = '', $type = null) {
        $me = L('ME');
        $show_hidden_lab = $me->show_hidden_lab();
        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if ($user_id) {
            $user_id = Q::quote($user_id);
            $pre_selector = $type ? "user#{$user_id}<{$type}" : "user#{$user_id}";
        }

        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;

        if ($s) {
            $s = Q::quote($s);
            $labs = $show_hidden_lab ? Q("$pre_selector lab[name*={$s}|name_abbr*={$s}]:limit({$start},{$n})") : Q("$pre_selector lab[!hidden][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})");
        }
        else {
            $labs = $show_hidden_lab ? Q("$pre_selector lab:limit({$start},{$n})") : Q("$pre_selector lab[!hidden]:limit({$start},{$n})");
        }

        $all_labs_count = $labs->total_count();

        if ($start == 0 && !$all_labs_count) {
            Output::$AJAX[] = [
                'html' => (string) V('autocomplete/special/empty'),
                'special' => TRUE
            ];
        }
        else {
            foreach ($labs as $lab) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/lab', ['lab'=>$lab]),
                    'alt' => $lab->id,
                    'text' => T('%lab', ['%lab'=>$lab->name])
                ];
            }
            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }

    }

    function user_token() {
        $query = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }

        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;

        // $lab = O('lab', $lab_id);
        $default_lab = O('lab', Lab::get('default_lab_id'));
        if ($query) {
            $query = Q::quote($query);
            $selector = "user[!hidden][token^={$query}][atime>0]:limit({$start},{$n})";
        }
        else {
            $selector = "user[!hidden][atime>0]:limit({$start},{$n})";
        }
        $users = Q($selector);
        $users_count = $users->length();

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
                    'text' => I18N::T('labs', '%user', ['%user'=>H($user->name)]),
                ];
            }

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }
}
