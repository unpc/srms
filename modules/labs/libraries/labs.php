<?php

class Labs {

	static function setup() {
	    if (!People::perm_in_uno()){
            Event::bind('profile.edit.tab', "Labs::edit_lab");
        }
	}

	static function edit_lab($e, $tabs) {
		$user = $tabs->user;
		$yiqikongLab = Config::get('people.yiqikong_lab_name');
		if (L('ME')->is_allowed_to('修改实验室', $user) 
			&& (Q("$user lab[name!={$yiqikongLab}]")->total_count() || !Q("$user lab")->total_count())
		 ) {
			if ($GLOBALS['preload']['people.multi_lab']) {
				Event::bind('profile.edit.content', "Labs::multi_edit_lab_content", 0, 'lab');
			}
			else {
				Event::bind('profile.edit.content', "Labs::edit_lab_content", 0, 'lab');
			}
			$tabs->add_tab('lab', [
				'url' => $user->url('lab', NULL, NULL, 'edit'),
				'title' => I18N::T('labs', '实验室')
			]);
		}
	}

	static function multi_edit_lab_content($e, $tabs) {
		$me = L('ME');
		$user = $tabs->user;
		if (!$me->is_allowed_to('修改实验室', $user)) URI::redirect('error/401');
		$form = Form::filter(Input::form());

		/*
		 * TASK #1398::LIMS-CF中的一些问题修正:成员模块修改该成员的实验室时可以进行将实验室清空修改的操作。
		 * 方法：增加了对$form['lab']为空的判断。(kai.wu@2011.08.31)
		 */
		if ($form['submit']) {
            $labs = (array)@json_decode($form['labs'],TRUE);
			if (!count($labs)) {
                $form->set_error('labs', I18N::T('labs', '请填写实验室!'));
			}
			if ($form->no_error) {
				$old_labs = Q("$user lab")->to_assoc('id', 'name');
				$disconLabs = array_diff_key($old_labs, $labs);
				$newConLabs = array_diff_key($labs, $old_labs);
				foreach ($disconLabs as $labId => $labName) {
					$lab = O('lab', $labId);
					if (Q("$user<pi $lab")->total_count()) {
						Lab::message(LAB::MESSAGE_NORMAL, I18N::T('labs', '%user_name是%lab_name的PI，不可删除', [
								'%user_name' => $user->name,
								'%lab_name' => $lab->name,
							]));
						break;
					}
					$user->disconnect($lab);
				}
				foreach ($newConLabs as $labId => $labName) {
					$lab = O('lab', $labId);
					$user->connect($lab);
                    Event::trigger('lab.multi_lab.add_member', $lab, $user);
				}

				if (Module::is_installed('nfs_share')) NFS_Share::setup_share($user);

                Log::add(strtr('[labs] %user_name[%user_id]修改了成员%member_name[%member_id]的实验室', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%member_name'=> $user->name, '%mbmer_id'=> $user->id]), 'journal');
				Lab::message(LAB::MESSAGE_NORMAL, I18N::T('labs', '用户实验室保存成功！'));
				URI::redirect($user->url('lab', NULL, NULL, 'edit'));
			}
		}

		$tabs->content = V('labs:edit/multi_edit_user_lab', [
			'user'=>$user,
			'form'=>$form
		]);
        $tabs->content .= Event::trigger('db_sync.slave_disable_input', Controller::$CURRENT);
	}

	static function edit_lab_content($e, $tabs) {
		$me = L('ME');
		$user = $tabs->user;
		if (!$me->is_allowed_to('修改实验室', $user)) URI::redirect('error/401');
		$form = Form::filter(Input::form());

		/*
		 * TASK #1398::LIMS-CF中的一些问题修正:成员模块修改该成员的实验室时可以进行将实验室清空修改的操作。
		 * 方法：增加了对$form['lab']为空的判断。(kai.wu@2011.08.31)
		 */
		if ($form['submit']) {
			if ($form['lab'] != NULL) {
				$form->validate('lab', 'is_numeric', I18N::T('labs', '请选择正确的实验室！'));
			}
			if ($form->no_error) {

                $lab = O('lab', $form['lab']);

                if (!$lab->id)  $lab = Lab_Model::default_lab();

                //如果用户的lab改变了, 创建一个新的用户, 保存原有用户的email card_no等信息
                $old_lab = Q("$user lab")->current();
                if ($old_lab->id && $old_lab->id != $lab->id) {

                    $roles = [];
                    foreach ($user->roles() as $r) {
                        if ($r <= 0) {
                            continue;
                        }
                        $roles[] = $r;
                    }

                    $new_user = $user->replacement();

                    $user->remove_unique()->save();

                    if ( $new_user->save() ) {
                    	$new_user->connect($lab);

                    	if (count($roles)) {
	                        $new_user->connect(['role', $roles]);
	                    }

	                    $new_user->group->connect($new_user);

	                    $user->move_img_to($new_user);

                        $user->move_training($user, $new_user);

                        $user->move_credit($user, $new_user);

	                    $user->delete_reserv($old_lab);

                    }

                }
				if (!$old_lab->id && $lab->id) {
					$user->connect($lab);
				}

				if ($user->save()) {

                    Log::add(strtr('[labs] %user_name[%user_id]修改了成员%member_name[%member_id]的实验室', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%member_name'=> $user->name, '%mbmer_id'=> $user->id]), 'journal');

					Lab::message(LAB::MESSAGE_NORMAL, I18N::T('labs', '用户实验室保存成功！'));
					if (isset($new_user)) {
						URI::redirect($new_user->url('lab', NULL, NULL, 'edit'));
					}
					else {
						URI::redirect($user->url('lab', NULL, NULL, 'edit'));
					}
				}
				else {
					Lab::message(LAB::MESSAGE_ERROR, I18N::T('labs', '用户实验室保存失败！'));
				}
			}
		}

		$tabs->content = V('labs:edit/edit_user_lab', [
			'user'=>$user,
			'form'=>$form
		]);
        // $tabs->content .= Event::trigger('db_sync.slave_disable_input', Controller::$CURRENT);
	}

	static function on_enumerate_user_perms($e, $user, $perms) {
		if (!$user->id) return;

//		$perms['查看所有实验室'] = 'on';
        //取消现默认赋予给pi的权限
		if (Q("$user<pi lab")->total_count()) {
//			$perms['添加/移除负责实验室成员'] = 'on';
//			$perms['修改负责实验室信息'] = 'on';
//			$perms['修改负责实验室成员的信息'] = 'on';
//			$perms['查看负责实验室的经费信息'] = 'on';
			/**
			 * BUG#12512 成员目录，课题组PI可以修改自己的角色
			 * author: Cheng.liu@geneegroup.com
			 * Fix Des: 默认仅为在单Lab模式下（Lims)让PI拥有绝对控制权
			 * Date: 2017.3.6
			 */
			$pi = Lab::get('lab.pi');
			if ($pi && $user->token == $pi) {
				$perms['修改负责实验室成员的角色'] = 'on';
			}
		}
	}

	static function on_achievement_edit($e, $object, $form) {

		if ($GLOBALS['preload']['people.multi_lab']) {
			$e->return_value .= V('labs:lab/achievements_mlab_project', [
                'lab' => $labs,
				'projects' => $projects,
				'form' => $form,
				'object' => $object
                ]);
            }
        else {
			$user = L('ME');

			if ($form->no_error) {
				$project = Q("{$object} lab_project:limit(1)")->current();
				$lab = $project->lab->id ? $project->lab : (Q("{$object} lab")->total_count() ? Q("{$object} lab")->current() : O('lab'));
			} else {
				$project = O('lab_project', $form['lab_project']);
				$lab = O('lab', $form['lab']);
			}

			$view = Event::trigger('achievement.project.select', $object, $lab, $project, $form);
			$e->return_value .= V('labs:lab/achievements_lab_project', [
				'lab'=>$lab,
				'project'=>$project,
				'view'=>$view,
				'object'=>$object
			]);
		}
	}

	static function on_lab_project_saved ($e, $form, $object) {

		$me = L('ME');
		foreach (Q("{$object} lab") as $old_lab) {
			$object->disconnect($old_lab);
		}

		if ($GLOBALS['preload']['people.multi_lab']) {
			$labIds = join(',', array_keys(json_decode($form['lab'],true)));
		}
		else {
			$labIds = $form['lab'];
		}
		$labs = Q("lab[id={$labIds}]");
		foreach ($labs as $lab) {
			$object->connect($lab);
		}

		foreach (Q("{$object} lab_project") as $old_proj) {
			$object->disconnect($old_proj);
		}

		if ($GLOBALS['preload']['people.multi_lab']) {
			if (!count($form['projects'])) return;
			$projectIds = join(',', array_keys($form['projects']));
			$projects = Q("lab[id={$labIds}] lab_project[id={$projectIds}]");
			foreach ($projects as $project) {
				$object->connect($project);
			}
		}
		else {
			if (!$form['lab_project']) return;
			$project = O('lab_project', $form['lab_project']);
			$object->connect($project);
		}
	}

	static function before_lab_relation_delete($e, $object) {
		$lab_projects = Q("{$object} lab_project");
		foreach ($lab_projects as $lab_project) {
			$object->disconnect($lab_project);
		}
	}

	private static function _access_own_attachments($user, $object) {
		if ($user->id == $object->id) {
			return TRUE;
		}
		if (Q("$user<pi {$object->lab}")->total_count()) {
			return TRUE;
		}
		return FALSE;
	}

	static function user_ACL($e, $user, $perm, $object, $options) {
		$ignores = $options['@ignore'];
		if (!is_array($ignores)) $ignores = [$ignores];
		$ignore = in_array('修改下属机构成员', $ignores) ? TRUE : FALSE;
		switch($perm) {
		case '修改实验室':
			if ($user->access('添加/修改所有成员信息') ||
				!$ignore &&
				$user->access('添加/修改下属机构成员的信息') &&
				$user->group->id && $object->group->id &&
				($user->group->is_itself_or_ancestor_of($object->group))) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '管理角色':
			if (Q("($user, $object) lab")->total_count() && $user->access('修改本实验室成员的角色') && $object->atime) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (Q("($user<pi, $object) lab")->total_count() && $user->access('修改负责实验室成员的角色') && $object->atime) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '删除':
			if ($object->undeletable || $user->id == $object->id) {
				$e->return_value = FALSE;
				return FALSE;
			}
		case '添加':
		case '修改':
			$edit_local = Q("($user, $object) lab")->total_count() && $user->access('修改本实验室成员的信息');
			$edit_own = Q("($user<pi, $object) lab")->total_count() && $user->access('修改负责实验室成员的信息');
			if ($object->id && ($edit_local || $edit_own)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '修改组织机构':
			if (($user->access('修改本实验室成员的信息') && $user->id && Q("($user, $object) lab")->total_count() && $object->atime)
				|| ($user->access('修改负责实验室成员的信息') && $user->id && Q("($user<pi, $object) lab")->total_count() && $object->atime)) {
				if (!$ignore
					&& $user->access('添加/修改下属机构成员的信息')
					&& $user->group->id
					&& ($user->group->is_itself_or_ancestor_of($object->group) || !$object->group->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
            break;
        case '查看联系方式':
            if ($object->privacy == User_Model::Privacy_Lab && ($user->id == $object->id)) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        }
	}

	/*
	NO.TASK#274(guoping.zhang@2010.11.25)
	操作实验室权限
		$object为lab对象
	*/
	static function operate_lab_is_allowed($e, $user, $perm, $object, $options) {
		$ignores = $options['@ignore'];
		if (!is_array($ignores)) $ignores = [$ignores];
		$ignore = in_array('修改下属机构实验室', $ignores) ? TRUE : FALSE;
		switch($perm) {
		/*
			添加/修改/删除3个权限中都增加了对【添加/修改下属机构实验室】 权限的判断
		*/
		case '查看':
			if (count(Q("$user $object")) > 0) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('查看所有实验室')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '添加':
			if ($user->access('添加/修改实验室')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('添加/修改下属机构实验室')
				&& $user->group->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '删除':
			$db = Database::factory();
			$findDefaultLabSQL = "SELECT `val` FROM `_config` WHERE `key` = 'equipment.temp_lab_id'";
		    $defaultLabID = @unserialize($db->value($findDefaultLabSQL));
		    if ($object->id == $defaultLabID) {
		    	$e->return_value = FALSE;
		    	return FALSE;
		    }

		    $findDefaultLabSQL = "SELECT `val` FROM `_config` WHERE `key` = 'default_lab_id'";
		    $defaultLabID = @unserialize($db->value($findDefaultLabSQL));
		    if ($object->id == $defaultLabID) {
		    	$e->return_value = FALSE;
		    	return FALSE;
		    }

			if ($user->access('添加/修改实验室')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('添加/修改下属机构实验室')
				&& $user->group->id
				&& $user->group->is_itself_or_ancestor_of($object->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '导出':
            if ($user->access('管理所有内容')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            $e->return_value = FALSE;
            return FALSE;
			break;
		case '修改':
			if ($user->access('添加/修改实验室')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($object->id > 0 && $user->access('修改本实验室信息') && Q("$user $object")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($object->id > 0 && $user->access('修改负责实验室信息') && Q("$user<pi $object")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('添加/修改下属机构实验室')
				&& $user->group->id
				&& $user->group->is_itself_or_ancestor_of($object->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '修改组织机构':
		case '激活':
			/*
				Cheng.liu@2010.12.03
				针对于lab修改组织机构的2种判断方式：
				1.如果有总的权限【添加/修改实验室】 则给与修改
			*/
			if ($user->access('添加/修改实验室')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			/*
				2.判断用户组织机构是否在实验室权限之上且具备【添加/修改下属机构实验室】权限.如果
				实验室无id，证明为新添加，只需要判断【添加/修改下属机构实验室】权限
			*/
			if (!$ignore
				&& $user->access('添加/修改下属机构实验室')
				&& $user->group->id
				&& ($user->group->is_itself_or_ancestor_of($object->group) || !$object->id)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '查看经费':
			if ($user->access('查看所有经费信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (Q("$user $object")->total_count() && $user->access('查看本实验室的经费信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (Q("$user<pi $object")->total_count() && $user->access('查看负责实验室的经费信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '管理':
			if ($user->access('添加/修改实验室')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '查看建立者':
			if ($user->access('查看实验室建立者')) {
				$e->return_value = TRUE;
				return FALSE;
			}
            if ($user->access('查看下属机构的实验室建立者')
                && $user->group->id
                && $object->id
                && ($user->group->is_itself_or_ancestor_of($object->group))
            ) {
                $e->return_value = TRUE;
                return FALSE;
            }
			break;
		case '查看审批者':
			if ($user->access('查看实验室审批者')) {
				$e->return_value = TRUE;
				return FALSE;
			}
            if ($user->access('查看下属机构的实验室审批者')
                && $user->group->id
                && $object->id
                && ($user->group->is_itself_or_ancestor_of($object->group))
            ) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        case '修改实验室负责人':
        	if (($user->id != $object->owner->id && !$user->access('管理所有内容'))
        		|| ($user->access('管理所有内容'))) {
        		$e->return_value = TRUE;
        		return FALSE;
        	}
        	break;
		}
	}
	/*
	NO.TASK#274(guoping.zhang@2010.11.26)
	操作成员附件权限
		$object为user对象
	*/
	static function operate_attachment_is_allowed($e, $user, $perm, $object, $options) {
		if ($options['type'] != 'attachments') return;

		//用户是实验室负责人
		if (Q("$user<pi {$object->lab}")->total_count()) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	/**
	 *@ zhen.liu modified
	 */
	static function operate_lab_user_is_allowed($e, $user, $perm, $lab, $options) {

		switch($perm) {
		case '添加成员':
		case '删除成员':
			if ($user->access('添加/移除所有实验室的成员')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('添加/移除下属机构实验室的成员')
				&& $user->group->id
				&& $user->group->is_itself_or_ancestor_of($lab->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (Q("$user<pi $lab")->total_count()
				&& $user->access('添加/移除负责实验室成员')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			//如果用户是该实验室成员且具备 【添加/移除本实验室成员】 权限的话,就能针对该实验室进行添加和移除用户
			if (Q("$user $lab")->total_count()
				&& $user->access('添加/移除本实验室成员')) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
	}

	static function prerender_people_users_table($e, $table) {
		$form = $table->form;
		$lab = is_object($form['lab']) ? $form['lab'] : O('lab', $form['lab']);
		$table->add_column('lab', [
			'title'=>I18N::T('labs', '实验室'),
			'invisible' => TRUE,
			'suppressible' => TRUE,
            'weight' => 75,
			'filter'=>[
			'form'=>Widget::factory('labs:lab_selector', [
						'name'=>'lab',
						'selected_lab'=> $lab,
						'all_labs'=>TRUE,
						'no_lab'=>TRUE,
						// 'size'=>30,
					]),
				'value'=> $lab->id ? H($lab->name) : NULL,
			],
		]);
	}

	static function people_lab_selector($e, $form , $selector, $pre_selector) {
		$lab_id = $form['lab'];
		if ($lab_id && $lab_id != '*') {
			$lab = O('lab', $lab_id);
			if ($lab->id) {
				$pre_selector[] = "{$lab}";
			}
		}
	}

	static function accessible_controller($e, $controller, $method, $params) {
		if ($controller instanceof Layout_Controller) {
			$me = L('ME');
			// 没有激活的用户表示尚未注册成功 因此需要转到注册页面
			$need_signup = (!$me->id && Auth::logged_in()) || ($me->id && !$me->is_active());
			if ( $need_signup && Input::arg(0) !== 'logout' && Input::arg(0) !== 'error') {
				//BUG #638::未激活用户登录之后无法查看【帮助中心】模块内容。
				if ( !defined('MODULE_ID') || MODULE_ID !== 'labs' || Input::arg(0) !== 'signup' ) {
				    $mudules_access = Config::get('besides.access_modules');
					if (!in_array(MODULE_ID,$mudules_access)) {
						URI::redirect('!labs/signup');
					}
				}
			}
		}
	}

	static function before_user_save($e, $user, $new_data) {
		/***
		 * 用户保存时，若无组织机构，则将其组织机构设为实验室的组织机构
		 * (xiaopei.li@2011.09.05)
		 ***/
		if ($user->id) {
			$group_root = Tag_Model::root('group');
			$labs = Q("$user lab");
			// 若用户无组织机构,且用户实验室有组织机构
			if (!$new_data['group']
				&& (!$user->group->id || $user->group->id == $group_root)
				&& ($labs->total_count() == 1)) {
				if ($lab = $labs->current() && $lab->group->id && $lab->group->id != $group_root->id) {
					$user->group = $lab->group; // 则将用户组织机构设为实验室的组织机构
					$user->group->connect($user); // 若用户为新建，这里无法链接
				}
			}
		}
	}

    /* 记录实验室的审批者(kai.wu@2011.10.17) */
	static function before_lab_save($e, $lab, $new_data) {
		if (!$lab->name) {
			$e->return_value = FALSE;
			return FALSE;
		}
		if ($new_data['atime']) {
			$me = L('ME');
			// 只有有atime更新，就要更新审批者为L('ME')
			// 但是lab有可能是从sync模块而来，故L('ME')不存在的情况下不更新
			$lab->auditor = $me->id ? $me : $lab->auditor;
			
			// 仅仅creator不存在的情况下才会更新creator
			$creator = $lab->creator;
			$lab->creator = $creator->id ? $creator : $me;
		}
	}


	// 用户激活时实验室PI的通知
	static function register_remind_PI($e, $people, $old_data, $new_data) {
		if (!$old_data->atime
		&&  $new_data['atime']      // 确定此次操作为激活
		&&  Q("$people lab")->total_count() == 1        // 用户有实验室
		&&  Q("$people lab<owner user")->total_count() == 1// 实验室有负责人
		) {
			$lab	= Q("$people lab")->current();
			$owner	= Q("$people lab<owner user")->current();

			if( $lab->enable_reg_notif === null ) {
				$lab->enable_reg_notif = TRUE;
				$lab->save();
			}
			if( $lab->ignore_PI_reg_notif === null ) {
				$lab->ignore_PI_reg_notif = TRUE;
				$lab->save();
			}

			// 负责人接收提醒，或负责人接收自己激活的提醒
			if ($lab->enable_reg_notif
			&&  ( ($owner->id != L('ME')->id) || !$lab->ignore_PI_reg_notif )
			) {
				Notification::send('labs.register', $owner, [
					'%user' => Markup::encode_Q($people),
					'%PI'   => Markup::encode_Q($owner),
					'%lab'  => Markup::encode_Q($lab),
				]);
			}
		}

		// 注释掉：
		// 1、register_remind_PI应该仅仅用于用户激活时实验室PI的通知
		// 2、2.17之后user没有lab属性
		// //修改用户课题组时，判断用户是否是修改前课题组的负责人，若是，则将课题组负责人设置为空
		// $old_lab = $old_data['lab'];
		// $new_lab = $new_data['lab'];

        // //如果用户的lab改变了，如果旧实验室所属名下没有任何用户，则应该将原来lab清空
        // if ($old_lab->id != $new_lab->id) {
        // 	if (!Q("user[lab={$old_lab}]")->total_count()) {
        // 		$old_lab->delete();
		// 	}
        // }
	}

	static function people_register_notifications($e, $lab, $sections ) {
		if( $lab->enable_reg_notif === null ) {
			$lab->enable_reg_notif = TRUE;
			$lab->save();
		}
		if( $lab->ignore_PI_reg_notif === null ) {
			$lab->ignore_PI_reg_notif = TRUE;
			$lab->save();
		}

		if( Input::form('submit') ) {
			$form = Form::filter(Input::form());
			$lab->enable_reg_notif = (bool) $form['enable_reg_notif'];
			$lab->ignore_PI_reg_notif = (bool) $form['ignore_PI_reg_notif'];
			$lab->save();
		}

		$sections[] = V('labs:lab/edit.notifications.register', ['lab'=>$lab] );
	}

	static function people_register_content($e, $lab, $sections) {
		$configs = ['notification.labs.register'];
		$vars = [];
		$form = Form::filter(Input::form());
		if($form['submit']){
			$form
				->validate('title', 'not_empty', I18N::T('labs', '消息标题不能为空！'))
				->validate('body', 'not_empty', I18N::T('labs', '消息内容不能为空！'));
			$vars['form'] = $form;
			if ($form->no_error && in_array($form['type'], $configs)) {
				$config = Lab::get($form['type'], Config::get($form['type']));
				$tmp = [
					'description'=>$config['description'],
					'strtr'=>$config['strtr'],
					'title'=>$form['title'],
					'body'=>$form['body']
				];
				foreach(Lab::get('notification.handlers') as $k=>$v){
					if(isset($form['send_by_'.$k])){
						$value = $form['send_by_'.$k];
					}else{
						$value = 0;
					}
					$tmp['send_by'][$k] = $value;
				}
				Lab::set($form['type'], $tmp);

				Log::add(strtr('[labs] %user_name[%user_id]修改了系统设置中的用户激活时实验室PI的提醒', ['%user_name'=> L('ME')->name, '%user_id'=> L('ME')->id]), 'journal');

				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '内容修改成功'));
			}
		}
		elseif ($form['restore']){
			Lab::set($form['type'], NULL);
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '恢复系统默认设置成功'));
		}
		$sections[] = Notification::preference_views($configs, $vars, 'labs');
	}

	static function on_lab_saved($e, $lab, $old_data, $new_data) {
		/* 激活实验室的时候，同时激活PI */
		if (!$old_data['atime'] && $new_data['atime']) {
			Event::trigger('lab.auto_open_lab', $lab);
			$owner = $lab->owner;
			if ($owner->id && !$owner->atime) {
				$owner->atime = time();
				$owner->save();
			}

			foreach (Q("$lab user[hidden=0]") as $user) {
				if ($user->id && !$user->atime && $user->auto_uactive) {
					$user->atime = time();
					$user->auto_uactive = 0;
					$user->save();
				}
			}
		}

		/* TODO 不激活实验室将实验室下所有用户打成未激活 */
		if ($old_data['atime'] && !$new_data['atime'] && $new_data['atime'] != -1) {
			$users = Q("$lab user[hidden=0]");
			foreach ($users as $user) {
				if ($user->id && $user->atime && !People::perm_in_uno()) {
					$user->atime = 0;
					$user->auto_uactive = 1;
					$user->save();
				}
			}
		}
	}

    static function notifi_classification_enable_callback($user) {
        return Q("lab $user.owner")->total_count();
    }

    static function get_project_items($e, $lab, $params = []) {
    	if ( !$lab->id ) return;

    	$project_types = [];
    	$status = Lab_Project_Model::STATUS_ACTIVED;
	    foreach( Lab_Project_Model::$types as $key => $type_name ){
	        $projects = Q("lab_project[status={$status}][lab={$lab}][type={$key}]");
	        if ($projects->total_count() > 0) {
	            $project_types[I18N::T('labs', $type_name)] = $projects->to_assoc('id', 'name');
	        }
	    }

	    $e->return_value = $project_types;

	    return TRUE;
    }

    static function get_stat_options($e) {
        $lab_projects_list = [];
        $lab_projects = [
			Lab_Project_Model::TYPE_RESEARCH=>'科研类项目',
			Lab_Project_Model::TYPE_EDUCATION=>'教学类项目',
        	Lab_Project_Model::TYPE_SERVICE=>'社会服务类项目',
        ];
        $start_weight = 1000;

        foreach($lab_projects as $pro_k => $pro_v) {
            $lab_projects_list[Lab_Project_Model::$stat_types[$pro_k]] = [
                'name' => $pro_v,
                'weight' => ++ $start_weight,
            ];
        }

        $e->return_value = $lab_projects_list;
    }

    static function get_stat_export_options($e) {

        $e->return_value = [
            'project_research' => '服务科研项目数',
            'project_education' => '服务教学项目数',
            'project_service' => '服务社会项目数',
        ];
	}

	static function on_user_connect_lab($e, $user, $obj, $tpye) {
		if (!$GLOBALS['preload']['people.multi_lab']
			&& Q("{$user} lab")->total_count() > 1
		) {
			foreach(Q("{$user} lab[id!={$obj->id}]") as $lab) {
				$user->disconnect($lab);
			}
		}
	}
}
