<?php

class Department_Controller extends Base_Controller {

    /**
     * zhen.liu modified 2010-12-3
     */
    function edit($id=0, $tab='info') {
        $department = Billing_Department::get($id);
        if (!$department->id) {
            URI::redirect('error/404');
        }

        $me = L("ME");

        if (!$me->is_allowed_to('修改', $department)) {
            URI::redirect('error/401');
        }

        $primary_tabs = $this->layout->body->primary_tabs = Widget::factory('tabs');

		$primary_tabs->edit_title = V('application:edit_title', ['name' => $department->name, 'url' => $department->url()]);

        $this->layout->title = H($department->name);
        $breadcrumbs = [
            [
                'url' => '!billing',
                'title' => I18N::T('equipments', '财务中心'),
            ],
            [
                'url' => $department->url(),
                'title' => $department->name,
            ],
            [
                'title' => '修改',
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);


        Event::bind('department.edit.content', [$this, '_edit_info'], 0, 'info');
        Event::bind('department.edit.content', [$this, '_edit_photo'], 0, 'photo');
        $primary_tabs
                ->add_tab('info', [
                    'url'=>$department->url('info', NULL, NULL, 'edit'),
                    'title'=>I18N::T('billing', '基本信息'),
                ])
                ->set('department', $department)
                ->tab_event('department.edit.tab')
                ->content_event('department.edit.content')
                ->select($tab);
    }

	/**
	 * zhen.liu modified 2010-11-3
	 */
	function _edit_info($e, $tabs) {

		$department = $tabs->department;
		$form = Form::filter(Input::form());

        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

		$has_multi_billing_depts = !$GLOBALS['preload']['billing.single_department'];
		if ($has_multi_billing_depts) {
			$group_root = Tag_Model::root('group');
		}

		if ($form['submit']) {
			$form->validate('name', 'not_empty', I18N::T('billing', '财务部门名称不能为空！'));
			$tmp_name = Q::quote($form['name']);
			if (Q("billing_department[name={$tmp_name}][id!={$department->id}]")->total_count() > 0) {
				$form->set_error('name', I18N::T('billing', '财务部门名称不可重复!'));
			}

			$can_edit_department_id = FALSE;
			$tokens = (array)Config::get('lab.admin');
			if ( L('ME')->access('管理所有内容') ) $can_edit_department_id = TRUE;
			if ( $can_edit_department_id ) {
				if ($form['nickname']) {

	                $nickname = trim($form['nickname']);

	                $existed_dept = O('billing_department', ['nickname'=> $nickname]);

	                if ($existed_dept->id && $existed_dept->id != $department->id) {
	                    $form->set_error('nickname', I18N::T('billing', '该标识名已经被使用!'));
		             }
		        }
		        else {
		                $form->set_error('nickname', I18N::T('billing', '标识名不能为空!'));
		        }
			}

			if ($has_multi_billing_depts) {
				$group = O('tag_group', $form['group_id']);
				$group_root->disconnect($department);
				$group->connect($department);
				$department->group = $group;
			}

			if ($form->no_error) {
                $department->name = $form['name'];
                $form['site'] && $department->site = $form['site'];
				$department->description = $form['description'];
                if( $can_edit_department_id ) $department->nickname = $nickname;

				if ($department->save()) {
					/* 记录日志 */
					Log::add(strtr('[billing] %user_name[%user_id]修改了财务部门%department_name[%department_id]的基本信息', [
								'%user_name' => L('ME')->name,
								'%user_id' => L('ME')->id,
								'%department_name' => $department->name,
								'%department_id' => $department->id,
					]), 'journal');

					$users = Q("{$department} user");
					if(count($users)) foreach($users as $user) {
							$user->disconnect($department);
						}

					$users = @json_decode($form['users'], TRUE);
					foreach($users as $id=>$name) {
						O('user',$id)->connect($department);
					}
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '更新成功!'));

					URI::redirect($department->url('info', NULL, NULL, 'edit'));
				}else{
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '更新失败!'));
				}
			}
		}

		$data = [
			'form'=>$form,
			'department'=>$department
		];

		if ($has_multi_billing_depts) {
			$data['group_root'] = $group_root;
		}

		$tabs->content = V('department/edit.info', $data);
	}

	/**
	 * zhen.liu modified 2010-12-3
	 */
	function _edit_photo($e, $tabs) {
		$department = $tabs->department;

		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try{
					$ext = File::extension($file['name']);
					if ($department->save_icon(Image::load($file['tmp_name'], $ext))) {
						/* 记录日志 */
						Log::add(strtr('[billing] %user_name[%user_id]修改了财务部门%department_name[%department_id]的图标', [
									'%user_name' => L('ME')->name,
									'%user_id' => L('ME')->id,
									'%department_name' => $department->name,
									'%department_id' => $department->id,
						]), 'journal');

						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '部门图标已更新'));
					}
				}
				catch(Error_Exception $e){
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '部门图标更新失败!'));
				}
			}
			else{
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '请选择您要上传的图标文件。'));
			}
		}

		$tabs->content = V('department/edit.photo');
	}

	/**
	 * zhen.liu modified 2010-12-3
	 */
	function delete_photo($id=0) {
		$department = Billing_Department::get($id);
		if (!$department->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('修改', $department)) {
			URI::redirect('error/401');
		}

		$department->delete_icon();

		/* 记录日志 */
		Log::add(strtr('[billing] %user_name[%user_id]修改了财务部门%department_name[%department_id]的图标', [
					'%user_name' => L('ME')->name,
					'%user_id' => L('ME')->id,
					'%department_name' => $department->name,
					'%department_id' => $department->id,
		]), 'journal');

		URI::redirect($department->url('photo', NULL, NULL, 'edit'));
	}

	/**
	 * zhen.liu modified 2010-12-3
	 */
	function add() {
		if (!L('ME')->is_allowed_to('添加', 'billing_department')) {
			URI::redirect('error/401');
		}

		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs
			->add_tab('add',[
					'url'=> URI::url('!billing/department/add'),
					'title'=> I18N::T('billing', '添加财务部门'),
				])
			->select('add');

		$department = O('billing_department');
		$form = Form::filter(Input::form());

		$has_multi_billing_depts = !$GLOBALS['preload']['billing.single_department'];
		if ($has_multi_billing_depts) {
			$group_root = Tag_Model::root('group');
		}

		if ($form['submit']) {
			$form->validate('name', 'not_empty', I18N::T('billing', '财务部门名称不能为空！'));
			$tmp_name = Q::quote($form['name']);
			if (Q("billing_department[name={$tmp_name}][id!={$department->id}]")->total_count() > 0) {
				$form->set_error('name', I18N::T('billing', '财务部门名称不可重复!'));
			}

			if ($has_multi_billing_depts) {
				$group = O('tag_group', $form['group_id']);
				if ($group->root->id == $group_root->id) {
					$department->group = $group;
				}
			}

			$can_edit_department_id = FALSE;
			$tokens = (array)Config::get('lab.admin');
			if ( L('ME')->access('管理所有内容') )  $can_edit_department_id = TRUE;
			if ( $can_edit_department_id ) {
				if ($form['nickname']) {
	                $nickname = trim($form['nickname']);
	                $existed_dept = O('billing_department', ['nickname'=> $nickname]);

	                if ($existed_dept->id) {
	                    $form->set_error('nickname', I18N::T('billing', '该标识名已经被使用!'));
	                }
	            }
	            else {
	                $form->set_error('nickname', I18N::T('billing', '标识名不能为空!'));
	            }
			}


			if ($form->no_error) {
				$department->name = $form['name'];
				$form['site'] && $department->site = $form['site'];
				$department->description = $form['description'];
				$department->ctime = $department->ctime ?: (int)time() ;
				$department->mtime = (int)time();
                if ( $can_edit_department_id ) $department->nickname = $nickname;

				if ($department->save()) {
					/* 记录日志 */
					Log::add(strtr('[billing] %user_name[%user_id]添加了财务部门%department_name[%department_id]', [
								'%user_name' => L('ME')->name,
								'%user_id' => L('ME')->id,
								'%department_name' => $department->name,
								'%department_id' => $department->id,
					]), 'journal');

					/* BUG #619::财务部门，按组织机构查询不到(xiaopei.li@2011.06.02) */
					if ($has_multi_billing_depts) {
						$group->connect($department);
					}

					$users = Q("{$department} user");
					if(count($users)) {
						foreach($users as $user) {
							$user->disconnect($department);
						}
					}
					$users = @json_decode($form['users'], TRUE);
					foreach($users as $id=>$name) {
						O('user',$id)->connect($department);
					}

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '更新成功!'));
					URI::redirect('!billing/departments');
				}else{
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '更新失败!'));
				}
			}
		}

		$data = ['form'=>$form];
		if ($has_multi_billing_depts) {
			$data['group_root'] = $group_root;
		}

		$primary_tabs->content = V('department/add', $data);
	}

	function delete($id=0, $type='') {
		$department = Billing_Department::get($id);
		if (!$department->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('删除', $department)) {
			URI::redirect('error/401');
		}

		$bool = (bool)count(Q("billing_account[department={$department}]"));
		if (!$bool) {
			$users = Q("{$department} user");
			foreach($users as $user) {
				$user->disconnect($department);
			}

			/* BUG #619::财务部门，按组织机构查询不到(xiaopei.li@2011.06.02) */
			if ($has_multi_billing_depts) {
				$group_root = Tag_Model::root('group');
				$group_root->disconnect($department);
			}

			if ($department->delete()) {
				/* 记录日志 */
				Log::add(strtr('[billing] %user_name[%user_id]删除了财务部门%department_name[%department_id]', [
							'%user_name' => $me->name,
							'%user_id' => $me->id,
							'%department_name' => $department->name,
							'%department_id' => $department->id,
				]), 'journal');

				Lab::message(LAB::MESSAGE_NORMAL,I18N::T('billing','财务部门删除成功!'));
			}
			else {
				Lab::message(LAB::MESSAGE_ERROR,I18N::T('billing','财务部门删除失败!'));
			}

		}
		else {
			Lab::message(LAB::MESSAGE_ERROR,I18N::T('billing','财务部门下存在财务帐号!您不能删除!'));
		}

		URI::redirect(URI::url('!billing/departments'));

	}

	function index($id=0, $tab='account') {

		$type = strtolower(Input::form('type'));
		$export_types = ['print','csv'];
		$form_token = Input::form('form_token');

		if ( in_array($type,$export_types)) {
			$form = $_SESSION[$form_token];
			$form['object_name'] = Input::form('object_name');
			$accounts  = Q($form['selector']);
			call_user_func([$this, '_export_'.$type], $accounts, $form);
		}
		else {
			$department = Billing_Department::get($id);
			if (!$department->id) {
				URI::redirect('error/404');
			}

			$me = L('ME');
			/*
				NO. BUG#206 (Cheng.Liu@2010.11.30)
				无权限后转到失败页面error/401
			*/
			if (!$me->is_allowed_to('查看', $department)) URI::redirect('error/401');

            $this->layout->body->primary_tabs->set_tab('departments',null);

			//$content = V('department/info', ['department'=>$department]);

            $this->layout->body->primary_tabs = Widget::factory('tabs')
				->set('department', $department)
				->tab_event('department.index.tab')
				->content_event('department.index.tab.content')
                ->tool_event('department.index.tab.tool')
				//->set('class', 'secondary_tabs')
				->select($tab);
            //$content->secondary_tabs = $tabs;

            $breadcrumbs = [
                [
                    'url' => '!billing/departments',
                    'title'=>I18N::T('billing', '财务部门列表'),
                ],
                [
                    'title' => $department->name,
                ]
            ];
            $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
            $this->layout->header_content = V('department/header_content', ['department' => $department]);
            $this->layout->title = I18N::T('labs', '');

			//$this->layout->body->primary_tabs->content = $content;

		}

	}

	function _export_print($accounts , $form) {
		$valid_columns = Config::get('billing.export_columns.billings');
        //无远程billing
        //删除部分远程信息
        if ( ! (bool) count(Config::get('billing.sources'))) {
            unset($valid_columns['income_remote']);
            unset($valid_columns['income_remote_confirmed']);
            unset($valid_columns['outcome_remote']);
		}

		$new_valid_columns = Event::trigger('billing.export_columns.extra.billings', $valid_columns);
		if ($new_valid_columns) $valid_columns = $new_valid_columns;

		$visible_columns = Input::form('columns');

		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$this->layout = V('accounts_print',[
			'accounts' => $accounts,
			'valid_columns' => $valid_columns,
			'object_name' => $form['object_name'],
            'form' => $form
		]);
	}

	function _export_csv($accounts, $form) {

		$form_token = $form['form_token'];
        $old_form = (array) $form;
        $new_form = (array) Input::form();
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$valid_columns = Config::get('billing.export_columns.billings');

        //无远程billing
        //删除部分远程信息
        if ( ! (bool) count(Config::get('billing.sources'))) {
            unset($valid_columns['income_remote']);
            unset($valid_columns['income_remote_confirmed']);
            unset($valid_columns['outcome_remote']);
		}

		$visible_columns = $form['columns'];

		$csv =new CSV('php://output','w');
		if ($accounts->total_count()) {
			$ids = join(', ', array_keys($accounts->to_assoc('id', 'id')));

            $has_remote_billing = count(Config::get('billing.sources'));

		 	$tol_balance = $accounts->sum('balance');
		 	$tol_use = $accounts->sum('outcome_use');
		 	$tol_credit_line = $accounts->sum('credit_line');


            //如果有远程billng
            if ($has_remote_billing) {
                //总收入
                $tol_amount =
                    $accounts->sum('income_remote') //远程充值
                    -
                    $accounts->sum('outcome_remote') //远程扣费
                    +
                    $accounts->sum('income_local') //本地充值
                    -
                    $accounts->sum('outcome_local'); //本地扣费

                $tol_amount_confirmed =
                    $accounts->sum('income_remote_confirmed') //远程充值confirmed
                    -
                    $accounts->sum('outcome_remote') //远程扣费
                    +
                    $accounts->sum('income_local') //本地充值
                    -
                    $accounts->sum('outcome_local'); //本地扣费
            }
            else {
                //总收入
                //本地收入 - 本地扣费
                $tol_amount = $accounts->sum('income_local') - $accounts->sum('outcome_local');
            }
		}

		if ($form['object_name'] == 'billing_department') {

            if ($has_remote_billing) {
                $statis = [
                    I18N::T('billing', '当前所有实验室的总收入为%tol_amount; 有效收入为%tol_amount_confirmed; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_amount_confirmed'=> Number::currency($tol_amount_confirmed),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
            else {
                $statis = [
                    I18N::T('billing', '当前所有实验室的总收入为%tol_amount; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
		}
		elseif ($form['object_name'] == 'lab') {
            if ($has_remote_billing) {
                $statis = [
                    I18N::T('billing', '当前总收入为%tol_amount; 总有效收入%tol_amount_confirmed; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_amount_confirmed'=> Number::currency($tol_amount_confirmed),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
            else {
                $statis = [
                    I18N::T('billing', '当前总收入为%tol_amount; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
		}
		$csv->write($statis);

		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$title = [];
		foreach ($valid_columns as $p => $p_name) {
			$title[] = I18N::T('billing',$valid_columns[$p]);
		}
		$csv->write($title);

		if ($accounts->total_count()) {
			foreach ($accounts as $account) {
				$data = [];

				if(array_key_exists('billing_department', $valid_columns)){
					$data[] = $account->department->name?:'-';
				}

				if(array_key_exists('lab', $valid_columns)){
					$data[] = $account->lab->name?:'-';

				}
                if(array_key_exists('income_remote', $valid_columns)) {
                    $data[] = $account->income_remote?:'-';
                }
                if(array_key_exists('income_remote_confirmed', $valid_columns)) {
                    $data[] = $account->income_remote_confirmed ?:'-';
                }
                if(array_key_exists('income_local', $valid_columns)) {
                    $data[] = $account->income_local?:'-';
                }
                if(array_key_exists('income_transfer', $valid_columns)) {
                    $data[] = $account->income_transfer?:'-';
                }
                if(array_key_exists('outcome_remote', $valid_columns)) {
                    $data[] = $account->outcome_remote?:'-';
                }
                if(array_key_exists('outcome_local', $valid_columns)) {
                    $data[] = $account->outcome_local?:'-';
                }

                if(array_key_exists('outcome_use', $valid_columns)) {
                    $data[] = $account->outcome_use?:'-';
                }

                if(array_key_exists('outcome_transfer', $valid_columns)) {
                    $data[] = $account->outcome_transfer?:'-';
                }

				if(array_key_exists('balance', $valid_columns)){
					$data[] =  $account->balance ? : '-';
				}

				if(array_key_exists('credit_line', $valid_columns)){
					$data[] = $account->credit_line ? : '-';
				}
				$csv->write($data);
			}

		}

		$csv->close();
	}
}

/*
* BUG#476
* rui.ma@2011.04.22
*/
class Department_AJAX_Controller extends AJAX_Controller {

	function index_check_result_show() {
		$form = Input::form();

		$department = O('billing_department', $form['department_id']);
		$billing_check = $_SESSION['billing_check_' . $department->id];
		Event::trigger('billing_check.setup');
		//??? 为什么要bind不直接写hook

		$csv = new CSV($billing_check['file_name'], 'r');
		$row = $csv->read();
		$cer_key = array_search('凭证号', $row) ? : 0;
		$inc_key = array_search('收入', $row) ? : 1;

		$errors = [];

		while ($row = $csv->read()) {
            if (count($row) == 1) {
                $row = explode(',', $row[0]);
            }

            $certificate = iconv('GB2312', 'UTF-8', $row[$cer_key]);
            $income = iconv('GB2312', 'UTF-8', $row[$inc_key]);

			if (!$income || $income < 0) continue;

			$error = Event::trigger('billing_check.check_by_certificate', $certificate, $income, $billing_check['dtstart'], $billing_check['dtend'], $department);

			if ($error) {
				$errors[] = $error;
			}
		}

		$other_certificate_error = Event::trigger('billing_check.other_certificate_error', $billing_check['dtstart'], $billing_check['dtend'], $department);

		if (is_array($other_certificate_error)) {
			$errors = array_merge($errors, $other_certificate_error);
		}
		else {
			$errors[] = $other_certificate_error;
		}

		$results = V('check/results', ['errors' => $errors]);

		$uniqid = $form['uniqid'];

		Output::$AJAX['#'.$uniqid] = (string) $results;
	}

	function index_reset_check_click() {
		$department_id = Input::form('department_id');

		$billing_check = $_SESSION['billing_check_' . $department_id];

		if (file_exists($billing_check['file_name'])) {
			File::delete($billing_check['file_name']);
		}

		JS::refresh();
	}

	function index_delete_department_click() {

		if (!JS::confirm(I18N::T('billing', '您确定要删除吗？删除后不可恢复'))) return;

		$me = L('ME');
		$form = Input::form();
		$department = O('billing_department', $form['d_id']);

		if (!$department->id) {
			JS::redirect('error/404');
		}

		if (!$me->is_allowed_to('删除', $department)) {
			JS::redirect('error/401');
		}


		if (!count(Q("billing_account[department={$department}]"))) {
			$users = Q("{$department} user");
			foreach($users as $user) {
				$user->disconnect($department);
			}
			/* BUG #619::财务部门，按组织机构查询不到(xiaopei.li@2011.06.02) */
			if ($has_multi_billing_depts) {
				$group_root = Tag_Model::root('group');
				$group_root->disconnect($department);
			}

			if ($department->delete()) {
				/* 记录日志 */
				Log::add(strtr('[billing] %user_name[%user_id]删除了财务部门%department_name[%department_id]', [
							'%user_name' => $me->name,
							'%user_id' => $me->id,
							'%department_name' => $department->name,
							'%department_id' => $department->id,
				]), 'journal');

				Lab::message(LAB::MESSAGE_NORMAL,I18N::T('billing','财务部门删除成功!'));
				JS::redirect(URI::url('!billing/departments/'));
			}
			else {
				Lab::message(LAB::MESSAGE_ERROR,I18N::T('billing','财务部门删除失败!'));
			}

		}
		else {
			JS::alert(I18N::T('billing','财务部门下存在财务帐号!您不能删除!'));
		}
	}

	//导出、打印。点击导出、打印链接会触发该事件
	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$columns = Config::get('billing.export_columns.billings');
		unset($columns[$form['object_name']]);

        //无远程billing
        //删除部分远程信息
        if ( ! (bool) count(Config::get('billing.sources'))) {
            unset($columns['income_remote']);
            unset($columns['income_remote_confirmed']);
            unset($columns['outcome_remote']);
        }

		$new_columns = Event::trigger('billing.export_columns.extra.billings', $columns);
		if ($new_columns) $columns = $new_columns;

		if ($type=='csv') {
			$title = I18N::T('billing','请选择要导出Excel的列');
		}
		elseif ($type=='print')
		{
			$title = I18N::T('billing', '请选择要打印的列');
		}
		JS::dialog(V('export_accounts_form',[
			'form_token' => $form_token,
			'columns' => $columns,
			'type' => $type,
			'object_name' => $form['object_name']
		]),[
			'title' => I18N::T('billing',$title)
		]);

	}

    function index_billing_account_export_submit() {
        $me = L('ME');
        $form_token = Input::form('form_token');
        $form = $_SESSION[$form_token];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $accounts = $form['selector'];
        $old_form = (array) $form;
        $new_form = (array) Input::form();
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$valid_columns = Config::get('billing.export_columns.billings');

        //无远程billing
        //删除部分远程信息
        if ( ! (bool) count(Config::get('billing.sources'))) {
            unset($valid_columns['income_remote']);
            unset($valid_columns['income_remote_confirmed']);
            unset($valid_columns['outcome_remote']);
        }

		$new_valid_columns = Event::trigger('billing.export_columns.extra.billings', $valid_columns);
		if ($new_valid_columns) $valid_columns = $new_valid_columns;

		$visible_columns = $form['columns'];

        if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['form'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_billing_account export ';
        //$cmd .= "'".$accounts."' '".$file_name."' '".$form['object_name']."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' '".json_encode($visible_columns,
        //JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
        $cmd .= "'".$accounts."' '".$file_name."' '".$form['object_name']."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' '".json_encode($visible_columns,
                JSON_UNESCAPED_UNICODE)."' '".json_encode($form,JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
        // exec($cmd, $output);
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id.'-export'][$pid] = $valid_form;

        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid' => $pid
        ]), [
            'title' => I18N::T('calendars', '导出等待')
        ]);

	}
	
	function index_auto_send_click () {
		$form = Form::filter(Input::form());

		$department = O('billing_department', $form['department_id']);
		$me = L('ME');
        if (!Config::get('billing_center.notification') || (!$me->is_allowed_to('修改', $department) && !Q("$department $me")->total_count())) {
			Lab::message(LAB::MESSAGE_ERROR,I18N::T('billing', '发送失败!'));
			JS::refresh();
			return FALSE;
		}
		
		$receiver_type = $form['receiver_type'];
		$receiver = json_decode($form['receiver'], true);
		$start_date = $form['start_date'];
		$end_date = $form['end_date'];

		if (!$start_date || !$end_date) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '请填写结算周期开始时间和结束时间!'));
            JS::refresh();
            return FALSE;
        }
        if ($receiver_type != 'all_pi' && !count($receiver)) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '请填写收件人!'));
			JS::refresh();
			return FALSE;
		} else if ($end_date < $start_date) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '结算周期结束时间必须大于开始时间!'));
			JS::refresh();
			return FALSE;
		} else {
            try{
                $rpc_receiver = [
                    'type' => $receiver_type
                ];
                if ($receiver_type != 'all_pi') {
                    $rpc_receiver['id'] = array_keys($receiver);
                }
                $params = [
                    'department_id' => $department->id,
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ];
                Notification::send('billing.account.detail', $rpc_receiver, $params);

                Lab::message(LAB::MESSAGE_NORMAL,I18N::T('billing', '发送成功!'));

                Log::add(strtr('[billing] %user_name[%user_id]发送了财务部门 %department_name[%department_id] 的结算通知', [
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                    '%department_name' => $department->name,
                    '%department_id' => $department->id,
                ]), 'journal');

            }catch (Exception $e){
                Lab::message(LAB::MESSAGE_ERROR,I18N::T('billing', '发送失败, 请稍后重新发送'));
            }

			JS::refresh();
		}
	}
}
