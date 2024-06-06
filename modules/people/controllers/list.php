<?php

class List_Controller extends Base_Controller {

	function index($tab='all') {

		$me = L('ME');

		$this->layout->title = I18N::T('people', '成员列表');
		$this->layout->body->primary_tabs->select($tab);

		//$secondary_tabs = Widget::factory('tabs');
        $this->layout->body->primary_tabs
				->add_tab('all', [
							'url'=>URI::url('!people/list/index.all'),
							'title'=>I18N::T('people', '所有成员'),
							'weight' => -2000,
						])
				->add_tab('activated', [
						'url' => URI::url('!people/list/index.activated'),
						'title' => I18N::T('people', '已激活成员'),
						'weight' => -1700,

					])
				->add_tab('unactivated', [
					'url'=>URI::url('!people/list/index.unactivated'),
					'title'=>I18N::T('people', '未激活成员'),
					'weight'=>-1500,
					]);

		$default_roles = Config::get('roles.default_roles');

		unset($default_roles[ROLE_STUDENTS]);
		unset($default_roles[ROLE_TEACHERS]);
		unset($default_roles[ROLE_CURRENT_MEMBERS]);
        unset($default_roles[ROLE_LAB_PI]);
        unset($default_roles[ROLE_EQUIPMENT_CHARGE]);

        if (Module::is_installed('summary')) {
            unset($default_roles[ROLE_NRII_HELP]);
        }

        $default_roles_key_name = []; // 存储默认role的key和name

        foreach ($default_roles as $default_role) {
            $default_roles_key_name[($default_role['key'])] = $default_role['name'];
        }

        $role_mts = [];    // 角色与member_type的对应数组
        $weight = 0;
        foreach(L('ROLES') as $role) {
            if(in_array($role->name, array_column($default_roles, 'name'))) {

                $r = [
                    'name' => $role->name,
                    'key' => array_flip($default_roles_key_name)[$role->name],
                    'hide' => ''
                ];
                if ($r['name'] && !$r['hide']) {
                    if (in_array($r['key'], (array)Config::get('people.disable_member_type'))) {
                        continue;
                    }
                    if (in_array($r['key'], ['current', 'past']) && ! $GLOBALS['preload']['people.enable_member_date']) {
                        continue;
                    }
                    $mt_key = $r['member_type_key']?:$r['name'];
                    $role_mts[$r['key']] = User_Model::get_members()[$mt_key];
                    $this->layout->body->primary_tabs
                        ->add_tab($r['key'], [
                            'url'=>URI::url('!people/list/index.'.$r['key']),
                            'title' => I18N::T('people', $r['name']),
                            'weight' => $weight,
                        ]);
                }
            }
            $weight ++;
        }

		$now = Date::time();
		$pre_selectors = new ArrayIterator;
		$selector = '';

        $show_hidden_user = $me->show_hidden_user();

		switch($tab){
		case 'activated':
			$selector = $show_hidden_user ? "user[atime>0]" : "user[!hidden][atime>0]";
			$role_name = $default_roles_key_name[$tab] ? : '已激活成员';
			break;
		case 'unactivated':
			$selector = $show_hidden_user ? "user[atime=0]" : "user[!hidden][atime=0]";
            if ($GLOBALS['preload']['people.enable_member_date']) {
                $selector .= '[dto=0|dto>'.$now.']';
            }
            $role_name = $default_roles_key_name[$tab] ? : '未激活成员';
			break;
		case 'current':
			if ($GLOBALS['preload']['people.enable_member_date']){
				$selector = $show_hidden_user ? "user[dto=0,{$now}~]": "user[!hidden][dto=0,{$now}~]";
			}
            $role_name = $default_roles_key_name[$tab] ? : '目前成员';
			break;
		case 'past':
			if ($GLOBALS['preload']['people.enable_member_date']){
				$selector = $show_hidden_user ? "user[dto!=0][dto<{$now}]:sort(auditor_id)" : "user[!hidden][dto!=0][dto<{$now}]:sort(auditor_id)";
			}
            $role_name = $default_roles_key_name[$tab] ? : '过期成员';
			break;
        default :
            $mt = $role_mts[$tab];
            if ($mt) {
                reset($mt);
                $mt_min = key($mt);
                end($mt);
                $mt_max = key($mt);
                $selector = $show_hidden_user ? "user[member_type>=$mt_min][member_type<=$mt_max]" : "user[member_type>=$mt_min][member_type<=$mt_max][!hidden]";
            }
            else {
                $selector = $show_hidden_user ? "user" : "user[!hidden]";
            }
            break;
		}

		$selector = Event::trigger('people.list.selector', $tab, $selector) ?: $selector;

        $this->layout->body->primary_tabs->select($tab);

		//多栏搜索
		$type = strtolower(Input::form('type'));
		$form_token = Input::form('form_token');

        $form_token = Session::temp_token('people_list_',300);
        $form = Lab::form(function(&$old_form, &$form) {
                if (!isset($old_form['group_id']) && !isset($form['group_id']) && !Session::get_url_specific('default_search_set')) {
                    // 使用默认的机构筛选
                    $me = L('ME');
                    $form['group_id'] = $me->default_group('people');
                    // 增加配置，默认组织机构是否选择自己所在的组织机构
                    if (Config::get('people.default_group_select_user_group') && !$form['group_id'] && $me->group->id) {
                        $form['group_id'] = $me->group->id;
                    }
                    Session::set_url_specific('default_search_set', TRUE);
                }

                if ($form['role'][0] == -1) unset($form['role'][0]);

            });
        $form = new ArrayIterator($form);
        Event::trigger('extra_form_value', $form);
        $form['role_name'] = $role_name;
        $form['form_token'] = $form_token;
        $_SESSION[$form_token] = $form;

		//GROUP搜索
		$group = O('tag_group', $form['group_id']);
		$group_root = Tag_Model::root('group');

		if ($group->id && $group->root->id == $group_root->id) {
			$pre_selectors['group'] = "$group";
		}
		else {
			$group = NULL;
		}

        $member_type_tag_root = Tag_Model::root('member_type');

        //-1为默认值，不予检索, 同样人员类型的root标签id，不予检索
        if (isset($form['member_type']) && $form['member_type'] != '-1' && $form['member_type'] != $member_type_tag_root->id) {

            $tag = O('tag', ['root'=> $member_type_tag_root, 'id'=> $form['member_type']]);

            //使用对应的tag进行检索
            if ($tag->id) {

                $get_mt_id = function($tag) {
                    foreach(User_Model::get_members() as $name => $submt) {
                        //submt => sub member type
                        if ($tag->parent->name == $name) {
                            foreach($submt as $k=>$v) {
                                if ($v == $tag->name) {
                                    return $k;
                                }
                            }
                        }
                    }
                };

                //如果存在子tag
                //例如: 学生 => 本科生、硕士研究生、博士研究生、其他
                //我们需要获取本科生、硕士研究生、博士研究生、其他的member_type的id值
                //如果不存在子tag
                //例如：本科生，我们只需要获对应的member_type的id值即可
                $mts = [];
                //will returen  Q
                $children = $tag->children();
                if ($children->total_count()) {
                    foreach($children as $c) {
                        $mts[] = $get_mt_id($c);
                    }
                }
                else {
                    $mts[] = $get_mt_id($tag);
                }

                $mts = join(',', $mts);
                $selector .= "[member_type={$mts}]";
                $form['member_type_name'] = $tag->name;
            }
            else {
                unset($form['member_type']);
            }
        }

		if($form['name']){
			$name = Q::quote(trim($form['name']));
			$selector .= "[name*=$name|name_abbr*=$name]";
		}
		if ($form['email']) {
			$email = Q::quote(trim($form['email']));
			$selector .= "[email*=$email]";
		}
		if ($form['phone']) {
			$phone = Q::quote(trim($form['phone']));
			if ( Config::get('people.show_personal_phone', false) ) {
				$selector .= "[phone*=$phone|personal_phone*={$phone}]";
			}
			else {
				$selector .= "[phone*=$phone]";
			}

		}

        if ($form['ref_no']) {
            $ref_no = Q::quote(trim($form['ref_no']));
            $selector .= "[ref_no*=$ref_no]";
        }

		if ($form['address']) {
			$address = Q::quote(trim($form['address']));
			$selector .= "[address*=$address]";
		}

		if ($me->is_allowed_to('查看建立者', 'user')) {
			if ($form['creator']) {
				$creator = Q::quote(trim($form['creator']));
				$pre_selectors['creator'] = "user[name*=$creator|name_abbr*=$creator]<creator";
			}
		}
		if ($me->is_allowed_to('查看审批者', 'user')) {
			if ($form['auditor']) {
				$auditor = Q::quote(trim($form['auditor']));
				$pre_selectors['auditor'] = "user[name*={$auditor}|name_abbr*={$auditor}]<auditor";
			}
		}
		if ($me->is_allowed_to('查看登录账号', 'user')) {
			if ($form['token']) {
				$token = Q::quote($form['token']);
				$selector .= "[token^={$token}]";
			}
        }

        if (isset($form['backends']) && $form['backends'] != -1) {
            $selector .= "[token$={$form['backends']}]";
            $form['backends_name'] = Config::get('auth.backends')[$form['backends']]['title'];
        }

		/*
		if ($lab_enabled) {
				$lab_id = $form['lab'];
				if ($lab_id != '*' && $lab_id !== NULL) {
					$lab = O('lab', $lab_id);
					$selector .= "[lab=$lab]";
				}
		}
		*/

		// people.index.search.submit 对搜索SELECTOR进行进一步处理
        $new_selector = Event::trigger('people.index.search.submit', $form, $selector, $pre_selectors);

		if ($new_selector) $selector = $new_selector;

		if (count($pre_selectors)>0) $selector = '('.implode(',', (array) $pre_selectors).') '.$selector;

		//$sort = $lab_enabled ? 'lab_abbr, lab_id, name_abbr, weight' : 'name_abbr, weight';
		$sort = 'name_abbr, weight';
        
		if ( $tab == 'all' ) $sort = 'atime D, name_abbr, weight';

        if (isset(Config::get('people.list_sort_by')[$tab])) 
        $sort = Config::get('people.list_sort_by')[$tab];

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A':'D';
        switch ($sort_by) {
            case 'name':
                $sort = 'name_abbr ' . $sort_flag . ', ' . $sort;
                break;
            case 'contact_info':
                $sort = 'email ' . $sort_flag . ', ' . $sort;
                break;
            case 'address':
                $sort = 'address_abbr ' . $sort_flag . ', ' . $sort;
                break;
            case 'creator':
                $sort = 'creator_abbr ' . $sort_flag . ', ' . $sort;
                break;
            case 'auditor':
                $sort = 'auditor_abbr ' . $sort_flag . ', ' . $sort;
                break;
            case 'college':
                $sort = "tag.name_abbr {$sort_flag}, $sort";
                break;
            case 'lab':
            default:
                if ($sort_by) {
                    $sort = $sort_by . ' ' . $sort_flag . ', ' . $sort;
                }
                break;
        }

        $selector .= ":sort($sort)";

		$users = Q($selector);

		//统计人数
		$user_count = $users->total_count();

		$export_types = ['print', 'csv'];

        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;
        $start = (int) $form['st'];
        $per_page = 30;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($users, $start, $per_page);
        $this->add_css('preview');
        $this->add_js('preview');

        $me = L('ME');

        $panel_buttons = new ArrayIterator;
        if ($me->is_allowed_to('添加', 'user')) {
            if (!Module::is_installed('db_sync')) {
                $panel_buttons[] = [
                    'tip' => I18N::HT(
                        'people',
                        '添加新成员'
                    ),
                    'text' => I18N::HT('people', '添加'),
                    'extra' => 'q-object="add_user_record" q-event="click" q-src="' . H(URI::url('!people/profile')) .
                        '" q-static="' . H(['tab' => $tab]) .
                        '" class="button button_add"',
                ];
            } elseif (Module::is_installed('db_sync') && DB_SYNC::is_master()) {
                $panel_buttons[] = [
                    'tip' => I18N::HT(
                        'people',
                        '添加新成员'
                    ),
                    'text' => I18N::HT('people', '添加'),
                    'extra' => 'q-object="add_user_record" q-event="click" q-src="' . H(URI::url('!people/profile')) .
                        '" q-static="' . H(['tab' => $tab]) .
                        '" class="button button_add"',
                ];
            }
        }

        if (Config::get('people.batch.disable.users') && ($me->access('添加/修改所有成员信息') || $me->access('管理所有内容')) && ($secondary_tabs->selected != 'unactivated')) {
            $panel_buttons[] = [
                'text' => I18N::T('people', ''),
                'extra' => 'id="disable_selected" class="icon-trash"',
            ];
        }

        if (Config::get('people.batch.import.users') && ($me->access('添加/修改所有成员信息') || $me->access('管理所有内容')) && ($secondary_tabs->selected != 'unactivated')) {
            $panel_buttons[] = [
                'text' => I18N::T('people', ''),
                'extra' => 'q-object="import_users" q-event="click" q-src="' . URI::url('!people/list') .
                '" q-static="' . H(['form_token' => $form_token]) .
                '" class="button button_add"',
            ];
        }

        if ($me->is_allowed_to('导出', 'user')) {
            /* 直接打印 */
            /*
            $panel_buttons[] = array(
                'url' => URI::url("!people/list/index?type=print&form_token={$form_token}"),
                'text' => I18N::T('people', '打印'),
                'extra' => 'class="button button_print "',
                );
            */

            $panel_buttons[] = [
                //'url' => URI::url("!people/list/index?type=csv&form_token={$form_token}"),
                'tip' => I18N::T('people', '导出Excel'),
                'text' => I18N::T('people', '导出'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!people/list') .
                '" q-static="' . H(['type'=>'csv','form_token' => $form_token]) .
                '" class="button button_save "',
                //'extra' => 'class="button button_save "',
                ];
            /* 选若干项打印 */
            $panel_buttons[] = [
                //'url' => URI::url('!people/list'),
                'tip' => I18N::T('people', '打印'),
                'text' => I18N::T('people', '打印'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!people/list') .
                '" q-static="' . H(['type'=>'print','form_token' => $form_token]) .
                '" class="button button_print "',
                ];
        }

        if ($GLOBALS['preload']['roles.manage_subgroup_perm'] && $me->group->id && !$me->access('管理所有内容')) {
        	$group_root = $me->group;
        }

        $new_panel_buttons = Event::trigger('people_list.panel_buttons', $panel_buttons, $form_token,$tab);
        $panel_buttons = $new_panel_buttons ? $new_panel_buttons : $panel_buttons;

        $this->layout->body->primary_tabs->content
            = V('people', [
                    'users'=>$users,
                    'secondary_tabs'=>$secondary_tabs,
                    'pagination'=>$pagination,
                    'form'=>$form,
                    'group' => $group,
                    'group_root' => $group_root,
                    'sort_by' => $sort_by,
                    'sort_asc' => $sort_asc,
                    'panel_buttons' => $panel_buttons,
                    'user_count' => $user_count
                    ]);

        $this->add_css('people:common');
	}

    function export() {

        $form = Input::form();
        $token = $form['form_token'];

        try {
            //如session中不存在对应的form信息, 则考虑跳过
            if (!count($_SESSION[$token])) throw new Error_Exception;

            $export_types = ['print', 'csv'];

            $old_form = (array) $_SESSION[$token];
            $new_form = (array) Input::form();

            if (isset($new_form['columns'])) {
                unset($old_form['columns']);
            }

            $form = $_SESSION[$token] = $new_form + $old_form;

            if (in_array($form['type'], $export_types)) {
                $users = Q($_SESSION[$token]['selector']);
                call_user_func([$this, '_export_'.$form['type']], $users, $form);
            }
            else {
                throw new Error_Exception;
            }
        }
        catch(Error_Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '操作超时, 请刷新页面后重试!'));
            URI::redirect('!people/list');
            return FALSE;
        }
    }

	function _export_print($users, $form) {
        $valid_columns = Config::get('people.export_columns.people');
		$visible_columns = Input::form('columns');

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$this->layout = V('people_print', [
							  'users' => $users,
							  'valid_columns' => $valid_columns,
                              'role_name'=>$form['role_name']
							  ]);
        $me = L('ME');

        Log::add(strtr('[people] %user_name[%user_id]打印了成员列表', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

	}

	function _export_csv($users, $form) {
        $valid_columns = Config::get('people.export_columns.people');
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$csv = new CSV('php://output', 'w');

        $me = L('ME');
        Log::add(strtr('[people] %user_name[%user_id]以CSV导出了成员列表', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

		$title = [];
		$title[] = I18N::T('people','用户姓名');
		foreach ($valid_columns as $key => $value) {
			$title[] = I18N::T('people',$value);
		 }
		$csv->write($title);

		$roles = L('ROLES')->to_assoc('id', 'name');

		if ($users->total_count() > 0) {
			foreach ($users as $user) {
				$role_names = array_intersect_key($roles, $user->roles());
				$data = [];
				$data[] = trim($user->name);
				foreach ($valid_columns as $key => $value) {
					switch ($key) {
						case 'token':
							$data[] =  trim(People::print_token($user->token));
							break;
						case 'gender':
							$data[] = I18N::T('people', User_Model::$genders[$user->gender]) ?: '--';
							break;
						case 'member_type':
							$data[] = User_Model::get_member_label($user->member_type) ?: '--';
							break;
						case 'mentor_name':
							$data[] = trim($user->mentor_name);
							break;
						case 'major':
							$data[] = trim($user->major);
							break;
						case 'organization':
							$data[] = trim($user->organization);
							break;
						case 'group':
							$data[] = trim($user->group->name);
							break;
						case 'email':
							$data[] = trim($user->email);
							break;
						case 'phone':
							$data[] = trim($user->phone);
							break;
						case 'personal_phone':
							$data[] = trim($user->personal_phone);
							break;
						case 'address':
							$data[] = trim($user->address);
							break;
						case 'lab':
							$labs = Q("$user lab")->to_assoc('id', 'name');
							$data[] = trim(join(',', $labs));
							break;
                        case 'lab_contact' :
                            $data[] = join(', ', Q("$user lab")->to_assoc('id', 'contact'));
                            break;
						case 'roles':
							$data[] = join(', ', $role_names);
							break;
					}
				}
				$csv->write($data);
			}
		}
		$csv->close();
	}

    function download_template() {
        $file_path = ROOT_PATH.'modules/people//public/template/人员信息导入模板.xlsx';
        if (file_exists($file_path)) {
            Downloader::download($file_path, false);
            exit;
        }
    }

    function import() {
        $me = L('ME');
        if ($me->access('添加/修改所有成员信息') || $me->access('管理所有内容')) {
            $file = Input::file('Filedata');
            $full_path = $file['tmp_name'];
            if (!file_exists('/tmp/user_upload/')) mkdir('/tmp/user_upload/', 0755, true);
            @copy($full_path, '/tmp/user_upload/'.$file['name']);
            if (!$file['error']) {
                $res = [];
                $autoload = ROOT_PATH.'vendor/os/php-excel/PHPExcel/PHPExcel.php';
                if(file_exists($autoload)) require_once($autoload);
                $reader = new \PHPExcel_Reader_Excel2007;
                if(!$reader->canRead($full_path)){
                    $reader = new \PHPExcel_Reader_Excel5;
                    if(!$reader->canRead($full_path)){
                        $res['status'] = FALSE;
                        $res['content'] = I18N::T('people', '无法读取Excel文件!');
                        echo json_encode($res);
                        exit;
                    }
                }

                $excel = $reader->load($full_path);
                $sheet = $excel->getSheet(0);
                $rows = $sheet->getHighestRow();

                $head =  Config::get('people.import_columns.head');

                foreach ($head as $key => $value) {
                    if ($sheet->getCellByColumnAndRow(ord($key) - 65, 1)->getValue() != $value) {
                        $res['status'] = FALSE;
                        $res['content'] = I18N::T('people', 'Excel文件格式错误!');
                        echo json_encode($res);
                        exit;
                    }
                }

                $new_users = 0;
                $existed_users = [];
                $failed_users = [];

                for ($row = 2; $row <= $rows; $row++) {
                    $name = trim($sheet->getCellByColumnAndRow(ord('A') - 65, $row)->getValue());
                    $token = Auth::normalize(trim($sheet->getCellByColumnAndRow(ord('B') - 65, $row)->getValue()), 'database');
                    $password = trim($sheet->getCellByColumnAndRow(ord('C') - 65, $row)->getValue());
                    $gender = trim($sheet->getCellByColumnAndRow(ord('D') - 65, $row)->getValue()) == '男' ? 0 : 1;
                    $member_type_name = trim($sheet->getCellByColumnAndRow(ord('E') - 65, $row)->getValue());
                    $ref_no = trim($sheet->getCellByColumnAndRow(ord('F') - 65, $row)->getValue());
                    $major = trim($sheet->getCellByColumnAndRow(ord('G') - 65, $row)->getValue());
                    $organization = trim($sheet->getCellByColumnAndRow(ord('H') - 65, $row)->getValue());
                    $group_name = trim($sheet->getCellByColumnAndRow(ord('I') - 65, $row)->getValue());
                    $email = trim($sheet->getCellByColumnAndRow(ord('J') - 65, $row)->getValue());
                    $phone = trim($sheet->getCellByColumnAndRow(ord('K') - 65, $row)->getValue());
                    $address = trim($sheet->getCellByColumnAndRow(ord('L') - 65, $row)->getValue());
                    $lab_name = trim($sheet->getCellByColumnAndRow(ord('M') - 65, $row)->getValue());

                    if (!$name || !$token || !$password || !$member_type_name || !$email || !$phone || !$lab_name) {
                        $failed_users[] = ['row' => $row, 'name' => $name];
                        continue;
                    }

                    $member_type_array = array_keys(User_Model::get_members()[$member_type_name]);
                    $member_type = array_pop($member_type_array);

                    $user = O('user', ['token' => $token]);
                    if (!$user->id) {
                        $user = O('user');

                        $user->name = $name;
                        $user->token = $token;
                        $auth = new Auth($token);
                        if (!$auth->create($password)) {
                            $failed_users[] = ['row' => $row, 'name' => $name];
                            continue;
                        }
                        $user->gender = $gender;
                        $user->member_type = $member_type;
                        $user->ref_no = $ref_no;
                        $user->major = $major;
                        $user->organization = $organization;
                        $root = Tag_Model::root('group');
                        $group = O('tag_group', ['root' => $root, 'parent' => $root, 'name' => $group_name]);
                        if (!$group->id) {
                            $group = O('tag_group');
                            $group->parent = $root;
                            $group->root = $root;
                            $group->name = $group_name;
                            $group->save();
                        }
                        $user->group = $group;
                        $user->email = $email;
                        $user->phone = $phone;
                        $user->address = $address;
                        $lab = O('lab', ['name' => $lab_name]);

                        $user->atime = time();

                        if ($user->save()) {
                            $user->connect($lab);

                            $new_users++;
                        } else {
                            $auth->remove();
                            $failed_users[] = ['row' => $row, 'name' => $name];
                        }
                    } else {
                        $existed_users[] = ['row' => $row, 'name' => $name];
                    }
                }
                $res['status'] = TRUE;
                $existed = array_filter($existed_users, function($existed_users) {
                    return $existed_users['name'];
                });
                $failed = array_filter($failed_users, function($failed_users) {
                    return $failed_users['name'];
                });
                $res['content'] = ['total' => $new_users + count($existed) + count($failed), 'new' => $new_users, 'existed' => ['count' => count($existed), 'users' => $existed], 'failed' => ['count' => count($failed), 'users' => $failed]];
                echo json_encode($res);
            } else {
                $res['status'] = FALSE;
                $res['content'] = I18N::T('people', 'Excel文件错误!');
                echo json_encode($res);
                exit;
            }
            exit;
        }
    }
}

class List_AJAX_Controller extends AJAX_Controller{

	private function _follow($follow=TRUE) {
		try {

			$form = Input::form();
			$object = O($form['oname'], $form['oid']);
			if (!$object->id) throw new Error_Exception;

			$me = L('ME');
			if (!$me->id) throw new Error_Exception;

			//by guoping.zhang @ 2010.10.28
			//禁止显示当前用户对自己进行关注或取消关注
			if ($object->name() == 'user' && $object->id == $me->id) throw new Error_Exception;

			$ret = $follow ? $me->follow($object) : $me->unfollow($object);
			if (!$ret) throw new Error_Exception;

			$element = $form['ajax_id'];
			$links = $me->follow_links($object, $form['mode']);
			Output::$AJAX['#'.$element] = [
				'data'=>(string)Widget::factory('application:links', ['links' => $links, 'separator'=>$form['mode'] = 'view'?'':NULL]),
				'mode'=>'replace'
			];

		}
		catch (Error_Exception $e) {
			return FALSE;
		}

		return TRUE;
	}

	function index_follow_click() {
		if (JS::confirm(I18N::T('people','您确定要添加对此的关注吗?'))) {
			if (!$this->_follow(TRUE)) {
				JS::alert(I18N::T('people','无法添加关注!'));
			}
		}
	}

	function index_unfollow_click() {
		if (JS::confirm(I18N::T('people','您确定要取消对此的关注吗?'))) {
			if (!$this->_follow(FALSE)) {
				JS::alert(I18N::T('people','无法取消关注!'));
			}
		}
	}

	/*
	NO.TASK#313(guoping.zhang@2011.01.12)
	列表信息预览功能
	*/
	function index_preview_click() {
		 $form = Input::form();
		 $user = O('user',$form['uid']);

		 if (!$user->id) return;

		 Output::$AJAX['preview'] = (string)V('people:profile/preview', ['user'=>$user]);

	}

	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];

        //对session进行过期判断
        //防止出现空数据的情况
        if (!count($_SESSION[$form_token])) {
            JS::alert(I18N::T('people', '操作超时, 请刷新页面后重试!'));
            JS::refresh();
            return FALSE;
        }

		$type = $form['type'];
        $columns = Config::get('people.export_columns.people');

		/*bug 5337 jiankun.fu requires to keep print order, but we have to unset personal_phone、menter_name, the code is
		temporary, please change in the future.
		*/

		$unset_columns = [];
		if (!Config::get('people.show_mentor_name', FALSE)) {
			$unset_columns[] = 'mentor_name';
		}
		if (!Config::get('people.show_personal_phone', FALSE)) {
			$unset_columns[] = 'personal_phone';
		}
		foreach($unset_columns as $unset_column) {
			unset($columns[$unset_column]);
		}

		$type = $form['type'];
		if ($type == 'csv') {
			$title = I18N::T('people', '请选择要导出Excel的列');
		}
		else {
			$title = I18N::T('people', '请选择要打印的列');
		}
		JS::dialog(V('export_form', [
						  'form_token' => $form_token,
						  'columns' => $columns,
						  'type' => $type
					]),[
						'title' => I18N::T('people', $title)
					]);
	}

	function index_print_submit() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$properties_to_print = [];

		foreach ($form['properties_to_print'] as $p => $checked) {
			if ($checked == 'on') {
				$properties_to_print[] = $p;
			}
		}
		$form = $_SESSION[$form_token];
		$form['properties_to_print'] = $properties_to_print;
		$_SESSION[$form_token] = $form;

		$target = URI::url("!people/list", ['type' => 'print', 'form_token' => $form_token]);
		JS::redirect($target);
	}

	function index_people_export_submit() {
		$form = Input::form();
		$token = $form['form_token'];

		try {
			//如session中不存在对应的form信息, 则考虑跳过
			if (!count($_SESSION[$token])) throw new Error_Exception;

			$export_types = ['print', 'csv'];

			$old_form = (array) $_SESSION[$token];
			$new_form = (array) Input::form();

			if (isset($new_form['columns'])) {
				unset($old_form['columns']);
			}

			$form = $_SESSION[$token] = $new_form + $old_form;

            $file_name_time = microtime(TRUE);
            $file_name_arr = explode('.', $file_name_time);
            $file_name = $file_name_arr[0].$file_name_arr[1];

			if (in_array($form['type'], $export_types)) {
				$this->_export_csv($_SESSION[$token]['selector'], $form, $file_name);
				JS::dialog(V('export_wait', [
	                'file_name' => $file_name
	            ]), [
	                'title' => I18N::T('calendars', '导出等待')
	            ]);
			}
			else {
				throw new Error_Exception;
			}
		}
		catch(Error_Exception $e) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '操作超时, 请刷新页面后重试!'));
			URI::redirect('!people/list');
			return FALSE;
		}

	}

	function _export_csv($selector, $form, $file_name) {
        $valid_columns = Config::get('people.export_columns.people');
        $visible_columns = $form['columns'];
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}

        $me = L('ME');
        Log::add(strtr('[people] %user_name[%user_id]以CSV导出了成员列表', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

		$title = [];
		$title[] = I18N::T('people','用户姓名');
		foreach ($valid_columns as $key => $value) {
			$title[] = I18N::T('people',$value);
		 }
		// $csv->write($title);

		$roles = L('ROLES')->to_assoc('id', 'name');

		$file_name_arr = explode('.', $form['form_token']);

		// if ($users->total_count() > 0) {
			putenv('Q_ROOT_PATH=' . ROOT_PATH);
			$cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_people export ';
			$cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' '".json_encode($roles, JSON_UNESCAPED_UNICODE)."' '".json_encode($title, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
			exec($cmd, $output);
		// }
	}

    function index_disable_selected_click() {
        $me = L('ME');
        if ($me->access('添加/修改所有成员信息') || $me->access('管理所有内容')) {
            $selected_ids = Input::form('selected_ids');
            if (JS::confirm(I18N::T('people','您确定要停用选中的用户吗?'))) {
                foreach ($selected_ids as $id) {
                    $user = O('user', $id);
                    if ($user->id && $user->atime != 0) {
                        $user->dto = Date::get_day_start();
                        $user->save();
                        Log::add(strtr('[people] %user_name[%user_id] 停用了用户 %disabled_user_name[%disabled_user_id]', [
                                        '%user_name' => $me->name,
                                        '%user_id' =>$me->id,
                                        '%disabled_user_name' => $user->name,
                                        '%disabled_user_id' => $user->id
                                ]), 'journal');
                    }
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户停用成功!'));
                JS::refresh();
            }
        }
    }

    function index_import_users_click() {
		JS::dialog(V('import_form'),['title' => I18N::T('people', '批量导入新成员')]);
    }
}
