<?php

class Account_Controller extends Base_Controller {

	function index($id, $tab = 'log') {
		$account = O('lims_account', $id);
		if (!$account->id) URI::redirect('error/404');

		$available_types = LIMS_Account_Model::get_available_type_titles();

		$tabs = $this->layout->body->primary_tabs;

		$tabs
			->add_tab('view', [
				'url' => $account->url(),
				'title' => H($account->lab_name),
			])
			->select('view');

		Event::bind('account.index.tab.content', [$this, '_index_logs'], 0, 'logs');
		Event::bind('account.index.tab.content', [$this, '_index_attachments'], 0, 'attachments');
		Event::bind('account.index.tab.content', [$this, '_index_versions'], 0, 'versions');

		$secondary_tabs = Widget::factory('tabs')
			->set('account', $account)
			->tab_event('account.index.tab')
			->content_event('account.index.tab.content')
			->add_tab('logs', [ // default tab
						  'url' => $account->url('logs'),
						  'title' => I18N::T('accounts', '日志'),
						  'weight' => 0,
						])
			->add_tab('attachments', [ // default tab
						  'url' => $account->url('attachments'),
						  'title' => I18N::T('accounts', '附件'),
						  'weight' => 0,
						])
			->add_tab('attachments', [
						  'url' => $account->url('attachments'),
						  'title' => I18N::T('accounts', '附件'),
						  'weight' => 0,
						])
			->add_tab('versions', [
						  'url' => $account->url('versions'),
						  'title' => I18N::T('accounts', '版本'),
						  'weight' => 0,
						])
			;

		// TODO hook add tabs

		$secondary_tabs->select($tab);

		$tabs->content = V('accounts:account/view', [
							'account' => $account,
							'available_types' => $available_types,
							'secondary_tabs' => $secondary_tabs
							]);
	}

	function _index_logs($e, $tabs) {
		$account = $tabs->account;
		$tabs->content = V('account/view.logs', ['account' => $account]);
	}

	function _index_attachments($e, $tabs) {
		$account = $tabs->account;
		$tabs->content = V('account/view.attachments', [
							   'account' => $account,
							   'path_type' => 'attachments',
							   ]);
	}

	function _index_versions($e, $tabs) {
		$account = $tabs->account;
		$versions = Q("account_version[account=$account]:sort(ctime D)");
		$tabs->content = V('account/view.versions', [
							   'account' => $account,
							   'versions' => $versions,
							   ]);
	}

	function add() {
		$me = L('ME');
		if (!$me->is_allowed_to('添加', 'lims_account')) {
			URI::redirect('error/401');
		}

		$account = O('lims_account');
		$form = Form::filter(Input::form());
		$available_types = LIMS_Account_Model::get_available_type_titles();

		if ($form['submit']) {
			$form
				->validate('lab_name', 'not_empty', I18N::T('accounts', '项目名称不能为空！'))
				->validate('lab_id', 'not_empty', I18N::T('accounts', '项目 ID 不能为空！'))
				->validate('code_id', 'not_empty', I18N::T('accounts', '程序代号 不能为空！'))
				->validate('type', 'not_empty', I18N::T('accounts', '项目类型不能为空！'));

            //currency
            if ($form['currency'] && !array_key_exists($form['currency'], Config::get('accounts.currency'))) {
                $form->set_error('currency', I18N::T('accounts', '货币类型选择有误!'));
            }

            //locale
            if ($form['locale'] && !array_key_exists($form['locale'], Config::get('accounts.locales'))) {
                $form->set_error('locale', I18N::T('accounts', '语言选择有误!'));
            }

			if ($form['type'] && !in_array($form['type'], array_keys($available_types))) {
				$form->set_error('type', I18N::T('accounts', '项目类型有误!'));
			}

            //被勾选, 但是错误填写
            if ($form['etime_check'] && (!is_numeric($form['nday']) || $form['nday'] <= 0)) {
                $form->set_error('nday', I18N::T('accounts', '提前提醒时间填写有误!'));
            }

            $lab_id = trim($form['lab_id']);
            $code_id = trim($form['code_id']);

            if (O('lims_account', ['lab_id' => $lab_id])->id) {
                $form->set_error('lab_id', I18N::T('accounts', '已有相同项目编号的客户'));
            }

            if (O('lims_account', ['code_id' => $code_id])->id) {
                $form->set_error('code_id', I18N::T('accounts', '已有相同程序代号的客户'));
            }

			if ($form->no_error) {

                if (!$form['etime_check']) {
                    $form['etime'] = 0;
                }

				$account->lab_name = $form['lab_name'];
				$account->lab_id = $lab_id;
				$account->code_id = $code_id;
				$account->type = $form['type'];
				$account->archive_url = $form['archive_url'];
				$account->url = $form['url'];
                $account->admin_token = $form['admin_token'] ? : Config::get('accounts.admin_token');
				$account->admin_password = $form['admin_password'] ? : Config::get('accounts.admin_password');
				$account->etime = $form['etime'];

                $account->locale = $form['locale'];
                $account->timezone = $form['timezone'] ? : Config::get('accounts.default_timezone');
                $account->currency = $form['currency'];

                //设置了过期日期, 同时设定提前过期提醒时间
                if ($account->etime && $form['nday']) {
                    $account->nday = $form['nday'];
                }

                $account->modules = array_keys(array_filter($form['modules']));

				if ($account->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('accounts', '成功添加LIMS客户！'));

					// TODO capsule comment_log method
					$comment_log = O('comment');
					$comment_log->author = L('ME');
					$comment_log->object = $account;
					$comment_log->content = I18N::T('accounts', '创建客户');
					$comment_log->save();

					URI::redirect($account->url(NULL, NULL, NULL, 'edit'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('accounts', '添加LIMS客户失败！'));
				}
			}
		}

		$tabs = $this->layout->body->primary_tabs;

		$tabs->add_tab('add', [
				'url' => '!accounts/account/add',
				'title' => I18N::HT('accounts', '添加客户'),
			])
			->select('add');

		$tabs->content = V('account/edit.info', [
							   'form' => $form,
							   'account' => $account,
							   'available_types' => $available_types,
							   ]);
	}

	function edit($id, $tab = 'info') {

		$account = O('lims_account', $id);

		if (!$account->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		if (!$me->is_allowed_to('修改', $account)) {
			URI::redirect('error/401');
		}

		$content = V('account/edit');

		$content->secondary_tabs = Widget::factory('tabs')
			->set('class', 'secondary_tabs')
			->set('account', $account)
			->tab_event('account.edit.tab')
			->content_event('account.edit.content');

		Event::bind('account.edit.content', [$this, '_edit_info'], 0, 'info');
		Event::bind('account.edit.content', [$this, '_edit_photo'], 0, 'photo');
		Event::bind('account.edit.content', [$this, '_edit_version'], 0, 'version');

		$content->secondary_tabs
			->add_tab('info', [
						  'url' => $account->url('info', NULL, NULL, 'edit'),
						  'title' => I18N::T('account', '基本'),
						  ])
			->add_tab('photo', [
						  'url' => $account->url('photo', NULL, NULL, 'edit'),
						  'title' => I18N::T('account', '头像'),
						  ])
			->add_tab('version', [
						  'url' => $account->url('version', NULL, NULL, 'edit'),
						  'title' => I18N::T('account', '版本'),
						  ])
			->select($tab);

		$breadcrumb = [
			[
				'url' => $account->url(),
				'title' => H($account->lab_name),
				],
			[
				'url' => $account->url(NULL, NULL, NULL, 'edit'),
				'title' => I18N::T('accounts', '修改'),
				],
			];

		$this->layout->body->primary_tabs
			->add_tab('edit', ['*' => $breadcrumb])
			->select('edit')
			->set('content', $content);
	}


	function _edit_info($e, $tabs) {

		$account = $tabs->account;
		$form = Form::filter(Input::form());
		$available_types = LIMS_Account_Model::get_available_type_titles();

		if ($form['submit']) {
			$form
				->validate('lab_name', 'not_empty', I18N::T('accounts', '项目名称不能为空!'))
				->validate('lab_id', 'not_empty', I18N::T('accounts', '项目 ID 不能为空!'))
				->validate('code_id', 'not_empty', I18N::T('accounts', '程序代号 不能为空!'))
				->validate('type', 'not_empty', I18N::T('accounts', '项目类型不能为空!'));

			if ($form['type'] && !in_array($form['type'], array_keys($available_types))) {
				$form->set_error('type', I18N::T('accounts', '项目类型有误!'));
			}

            //currency
            if ($form['currency'] && !array_key_exists($form['currency'], Config::get('accounts.currency'))) {
                $form->set_error('currency', I18N::T('accounts', '货币类型选择有误!'));
            }

            //locale
            if ($form['locale'] && !array_key_exists($form['locale'], Config::get('accounts.locales'))) {
                $form->set_error('locale', I18N::T('accounts', '语言选择有误!'));
            }

            //被勾选, 但是错误填写
            if ($form['etime_check'] && (!is_numeric($form['nday']) || $form['nday'] <= 0)) {
                $form->set_error('nday', I18N::T('accounts', '提前提醒时间填写有误!'));
            }

            $lab_id = trim($form['lab_id']);
            $code_id = trim($form['code_id']);

            if ($lab_id != $account->lab_id && O('lims_account', ['lab_id' => $lab_id])->id) {
                $form->set_error('lab_id', I18N::T('accounts', '已有相同项目编号的客户'));
            }

            if ($code_id != $account->code_id && O('lims_account', ['code_id' => $code_id])->id) {
                $form->set_error('code_id', I18N::T('accounts', '已有相同程序代号的客户'));
            }

			if ($form->no_error) {

                if (!$form['etime_check']) {
                    $form['etime'] = 0;
                }

				$account->lab_name = $form['lab_name'];
				$account->lab_id = $lab_id;
				$account->code_id = $code_id;
				$account->type = $form['type'];
				$account->archive_url = $form['archive_url'];
				$account->url = $form['url'];
				$account->admin_password = $form['admin_password'] ? : '';
				$account->etime = $form['etime'];
                $account->locale = $form['locale'];
                $account->timezone = $form['timezone'] ? : Config::get('accounts.default_timezone');
                $account->currency = $form['currency'] ? : Config::get('accounts.default_currency');

				$account->timezone = $form['timezone'];
				$account->language = $form['language'];

                $account->modules = array_keys(array_filter($form['modules']));

                //设置了过期日期, 同时设定提前过期提醒时间
                if ($account->etime && $form['nday']) {
                    $account->nday = $form['nday'];
                }

				if ($account->save()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('accounts', '%name信息已经更新！', ['%name'=>$account->lab_name]));

					$comment_log = O('comment');
					$comment_log->author = L('ME');
					$comment_log->object = $account;
					$comment_log->content = I18N::T('accounts', '修改客户信息');
					$comment_log->save();

					// 修改后到期时间延长且已经关闭的试用站点，重新开启
					if ($account->etime > Date::time() && File::exists(Accounts_Sync::get_account_disable_file($account))) {
						Accounts_Sync::site_open($account);
					}
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('accounts', '%name信息更新失败！', ['%name'=>$account->lab_name]));
				}
			}
		}
		else {
			$form['lab_name'] = $account->lab_name;
			$form['lab_id'] = $account->lab_id;
			$form['code_id'] = $account->code_id;
			$form['type'] = $account->type;
			$form['archive_url'] = $account->archive_url;
			$form['url'] = $account->url;
            $form['admin_token'] = $account->admin_token;
			$form['admin_password'] = $account->admin_password;
			$form['etime'] = $account->etime;
		}

		$tabs->content = V('account/edit.info', [
							   'form' => $form,
							   'account' => $account,
							   'available_types' => $available_types,
							   ]);
	}

	function _edit_photo($e, $tabs) {
		$account = $tabs->account;

		if (Input::form('submit')) {
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try{
					$ext = File::extension($file['name']);
					$account->save_icon(Image::load($file['tmp_name'], $ext));
					$me = L('ME');
					Log::add(sprintf('[accounts] %s[%d]修改%s[%d]客户的图标', $me->name, $me->id, $account->name, $account->id), 'journal');
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('accounts', '客户图标已更新'));
				}
				catch(Error_Exception $e){
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('accounts', '客户图标更新失败!'));
				}
			}
			else{
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('accounts', '请选择您要上传的客户图标文件。'));
			}
		}

		$tabs->content = V('account/edit.photo');
	}

	function _edit_version($e, $tabs) {
		$account = $tabs->account;
		$old_version = O('account_version', [
							 'account' => $account,
							 'version' => $account->version,
							 'dtend' => 0,
							 ]);

		$form = Form::filter(Input::form());
		if ($form['submit']) {

			try {
				$now = Date::time();

				$form->validate('version', 'not_empty', HT('版本不能为空!'));

				if (!$form->no_error) {
					throw new Error_Exception;
				}

				if ($old_version->id && $account->version == $form['version']) {
					// 版本未改变时, 修改版本描述

					$old_version->description = $form['description'];
					$old_version->save();

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('accounts', '版本描述已更新'));
				}
				else {
					if ($old_version->id) {
						$old_version->dtend = $now;
						$old_version->save();
					}

					// new version
					$version = O('account_version');

					$version->account = $account;
					$version->dtstart = $now;
					$version->version = $form['version'];
					$version->description = $form['description'];
					$version->save();

					$account->version = $form['version'];
					$account->save();

					$me = L('ME');
					Log::add(sprintf('[accounts] %s[%d]修改了%s[%d]的版本', $me->name, $me->id, $account->name, $account->id), 'journal');
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('accounts', '版本已更新'));
				}

			}
			catch (Error_Exception $e) {
				$msg = $e->getMessage();
				if ($msg) {
					Lab::message(Lab::MESSAGE_ERROR, $msg);
				}
			}

		}
		else {
			$form['version'] = $old_version->version;
			$form['description'] = $old_version->description;
		}

		$versions = Q("account_version[account=$account]:sort(ctime D)");
		$tabs->content = V('account/edit.version', ['form'=>$form, 'versions'=>$versions, 'account'=>$account]);
	}

	function delete_photo($id=0) {

		$account = O('lims_account', $id);

		if (!$account->id) URI::redirect('error/404');

		$me = L('ME');
		if (!$me->is_allowed_to('修改', $account)) {
			URI::redirect('error/401');
		}

		$account->delete_icon();

		URI::redirect($account->url('photo', NULL, NULL, 'edit'));
	}


	function delete($id) {
		$account = O('lims_account', $id);

		if (!$account->id) URI::redirect('error/404');

		$me = L('ME');
		if (!$me->is_allowed_to('删除', $account)) {
			URI::redirect('error/401');
		}

		$account->status = LIMS_Account_Model::STATUS_DELETED;

		if ($account->save()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('accounts', '您成功删除LIMS客户！'));
			URI::redirect($account->url(NULL, NULL, NULL, 'edit'));
		}
		else {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('accounts', '删除LIMS客户失败！'));
		}
	}

	function recovery($id) {
		$account = O('lims_account', $id);

		if (!$account->id) URI::redirect('error/404');

		$me = L('ME');
		if (!$me->is_allowed_to('删除', $account)) {
			URI::redirect('error/401');
		}

		$account->status = LIMS_Account_Model::STATUS_NORMAL;

		if ($account->save()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('accounts', '您成功恢复LIMS客户！'));
			URI::redirect($account->url(NULL, NULL, NULL, 'edit'));
		}
		else {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('accounts', '恢复LIMS客户失败！'));
		}
	}

}
