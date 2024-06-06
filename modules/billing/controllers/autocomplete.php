<?php

class Autocomplete_Controller extends AJAX_Controller {

	function users() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		/*
		  NO.TASK#312(guoping.zhang@2011.01.07)
		  查询限制数量：10
		*/
		if ($s) {
			$s = Q::quote($s);
			$selector = "user[name*={$s}|name_abbr*={$s}][atime]:limit({$start},{$n})";
		}
		else {
			$selector = "user[atime]:limit({$start},{$n})";
		}
		$users = Q($selector);
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
					'html'=>(string)V('autocomplete/user',['user'=>$user]),
					'alt'=>$user->id, 
					'text'=> $user->friendly_name(),
				];
			}
//			$rest = $users->total_count() - $users_count;
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	/* BUG #1052::财务明细页面中，实验室搜索和其他不同，可能导致由于实验室过多，无法选定需要搜索的实验室(kai.wu@2011.08.22) */
	function department() {

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
			$selector = "billing_department[name*={$s}]:limit({$start},{$n})";
		}
		else {
			$selector = "billing_department:limit({$start},{$n})";
		}
		$departments = Q($selector);
		$departments_count = $departments->total_count();


		if ($start == 0 && !$departments_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($departments as $department) {
				Output::$AJAX[] = [
					'html'=>(string)V('autocomplete/department',['department'=>$department]),
					'alt'=>$department->id,
					'tip'=>I18N::T('billing','%department',['%department'=>$department->name]),
				];
			}
//			$rest = $departments->total_count() - $departments_count;		
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
	
	function lab_department($lab_id, $type = null) {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$lab = O('lab', $lab_id);
		$me = L('ME');
		$me_is_admin  = $me->access('管理财务中心', 'billing');
		$remote = $type == 'refill' ? "[source=local][!voucher]" : null;
		
		if ($s) {
			$s = Q::quote($s);
			if ($me_is_admin) {
				$selector = "billing_account[lab={$lab}]$remote<department billing_department[name*={$s}]";
			}
			else {
				$selector = "({$me} tag_group, billing_account[lab={$lab}]$remote<department) billing_department[name*={$s}]";
			}	
		}
		else {
			if ($me_is_admin) {
				$selector = "billing_account[lab={$lab}]$remote<department billing_department";
			}
			else {
				$selector = "({$me} tag_group,billing_account[lab={$lab}]$remote<department) billing_department";
			}	
		}
		
		$departments = Q($selector)->limit($start, $n);
		$departments_count = $departments->length();
		if ($start == 0 && !$departments_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($departments as $department) {
				Output::$AJAX[] = [
					'html'=>(string)V('autocomplete/department',['department'=>$department]),
					'alt'=>$department->id . '.'. $lab->id,
					'tip'=>I18N::T('billing','%department',['%department'=>$department->name]),
				];
			}
//			$rest = $departments->total_count() - $departments_count;		
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	function lab($id=0) {

		$me = L('ME');
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$department = O('billing_department', $id);
		if ($s) {
			$s = Q::quote($s);
			$selector = "lab[name*={$s}|name_abbr*={$s}]:not(billing_account[department={$department}] lab)";
			if(People::perm_in_uno()){
				$selector = "lab[atime][name*={$s}|name_abbr*={$s}]:not(billing_account[department={$department}] lab)";
			}
			if (Event::trigger('billing_department.show_supervised_labs', $department)) {
				$selector = "{$me}<group tag_group[parent] " . $selector;
			}
		}
		else {
			$selector = "lab:not(billing_account[department={$department}] lab)";
			if(People::perm_in_uno()){
				$selector = "lab[atime]:not(billing_account[department={$department}] lab)";
			}
			if (Event::trigger('billing_department.show_supervised_labs', $department)) {
			 	$selector = "{$me}<group tag_group[parent] " . $selector;
			}
		}

		$selector .= ':sort(name_abbr)';
		$labs = Q($selector)->limit($start, $n);
		$labs_count = $labs->length();

		if ($start == 0 && !$labs_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($labs as $lab) {
				Output::$AJAX[] = [
					'html'=>(string)V('autocomplete/lab',['lab'=>$lab]),
					'alt'=>$lab->id,
					'tip'=>I18N::T('billing','%lab',['%lab'=>$lab->name]),
				];
			}
//			$rest = $labs->total_count() - $labs_count;		
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	function transaction_lab($department_id = 0) {
		$department = O('billing_department', $department_id);
		if (!$department->id) return;
		
		$me = L('ME');
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
			$selector = "billing_account[department={$department}]<lab lab[hidden=0][name*={$s}|name_abbr*={$s}]";
			if (!$me->is_allowed_to('列表收支明细', $department)) {
				$selector = "{$me}<group tag[parent] " . $selector;
			}
		}
		else {
			$selector = "billing_account[department={$department}]<lab lab[hidden=0]";
			if (!$me->is_allowed_to('列表收支明细', $department)) {
				$selector = "{$me}<group tag[parent] " . $selector;
			}
		}

		$labs = Q($selector)->limit($start, $n);
		$labs_count = $labs->length();

		if ($start == 0 && !$labs_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($labs as $lab) {
				Output::$AJAX[] = [
					'html'=>(string)V('autocomplete/lab',['lab'=>$lab]),
					'alt'=>$lab->id,
					'tip'=>I18N::T('billing', $lab->name)
				];
			}
//			$rest = $labs->total_count() - $labs_count;
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
	
	function recharger() {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		$department = O('billing_department', Input::form('department_id'));
		if ($s) {
			$s = Q::quote($s);
			$selector = "billing_account[department={$department}]<account billing_transaction user[name*={$s}|name_abbr*={$s}]";
		}
		else {
			$selector = "billing_account[department={$department}]<account billing_transaction user";
		}
		
		$rechargers = Q($selector)->limit($start, $n);
		$rechargers_count = $rechargers->total_count();
		
		if ($start == 0 && !$rechargers_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($rechargers as $recharger) {
				Output::$AJAX[] = [
					'html'=>(string)V('autocomplete/recharger',['recharger'=>$recharger]),
					'alt'=>$recharger->id,
					'tip'=>I18N::T('billing', $recharger->name)
				];
			}
//			$rest = $rechargers->total_count() - $rechargers_count;		
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

    function account() {

        $form = Input::form();

        $department = O('billing_department', $form['department_id']);

        if (!$department->id) return FALSE;

        $lab = $form['lab_id'];

        //获取需要过滤billing_account
        $account = O('billing_account', ['department'=> $department, 'lab'=> O('lab', $form['lab_id'])]);

        $s = trim($form['s']);
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
        if ($account->id) {
            $selector = "billing_account[department={$department}]:not({$account})";
        }
        else {
            $selector = "billing_account[department={$department}]";
        }

        if ($s) $selector = "lab[name*={$s}|name_abbr*={$s}].lab ". $selector;

        $full_accounts = Q($selector);

        $total_count = $full_accounts->total_count();
		$accounts = $full_accounts->limit($start, $n);

		if ($start == 0 && !$total_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($accounts as $account) {
				Output::$AJAX[] = [
					'html'=>(string)V('autocomplete/account',['account'=>$account]),
					'alt'=>$account->id,
					'tip'=>I18N::T('billing', $account->lab->name)
				];
			}

//			$rest = $total_count - $accounts->length();
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}
	
	function pi($lab_id=0) {
		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}

		$n = 5;
		if($start == 0) $n = 10;

		if($start >= 100) return;

		if ($s) {
			$s = Q::quote($s);
			$selector = "lab<pi user[!hidden][atime][name*={$s}|name_abbr*={$s}]:limit({$start},{$n})";
		}
		else {
			$selector = "lab<pi user[!hidden][atime]:limit({$start},{$n})";
		}
		
		$users = Q($selector);
		$users_count = $users->total_count();

		if ($start == 0 && !$users_count) {
			Output::$AJAX[] = [
				'html' => (string) V('application:autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($users as $user) {
				Output::$AJAX[] = [
					'html' => (string) V('application:autocomplete/user', ['user'=>$user]),
					'alt' => $user->id,
					'text' => $user->friendly_name(),
				];
			}

			if ($start== 95) {
				Output::$AJAX[] = [
					'html' => (string) V('application:autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

    function role() {
        $roles = Event::trigger('people.get.roles');
        $roles = array_map(function ($val) { return H($val); }, $roles);

        foreach ($roles as $k => $role){
            if (in_array($role,['学生','教师','过期成员','目前成员'])) unset($roles[$k]);
			if(Module::is_installed('uno') && !o('role',$k)->gapper_id){
				unset($roles[$k]);
			}
        }

        $s = trim(Input::form('s'));
        $st = trim(Input::form('st'));
        $start = 0;
        if ($st) {
            $start = $st;
        }
        if($start >= 100) return;
        $n = 5;
        if($start == 0) $n = 10;
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
            //需要增加第四个参数 true 用于保留 key 值!
            $selector_roles = array_slice($selector_roles, $start, $max_count, TRUE);
            foreach ($selector_roles as $id => $role_name) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/small_tag', ['tag' => $role_name]),
                    'alt' => $id,
                    'text' => $role_name,
                ];
            }

            //    $rest = $selector_count - $max_count;

            if ($start == 95) {
                Output::$AJAX[] = [
                    'html' => (string) V('autocomplete/special/rest'),
                    'special' => TRUE
                ];
            }
        }
    }
}
