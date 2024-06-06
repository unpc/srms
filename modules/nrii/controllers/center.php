<?php

class Center_Controller extends Base_Controller {
	function index() {
        URI::redirect(URI::url('!nrii/nrii.center'));
    }

	function add(){
        $form = Form::filter(Input::form());

        if ($form['submit']) {
			$form = Form::filter(Input::form())
				->validate('centname', 'not_empty', I18N::T('nrii', '请输入仪器中心名称	!'))
				->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所在单位仪器中心编号!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
				->validate('worth', 'number(>=0)', I18N::T('nrii', '请输入正确的仪器总值!'))
				->validate('research_area', 'number(>=0)', I18N::T('nrii', '请输入正确的科研用房面积!'))
				->validate('instru_num', 'number(>=0)', I18N::T('nrii', '请输入正确的科研仪器数量!'))
				// ->validate('file', 'not_empty', I18N::T('nrii', '请上传图片!'))
				->validate('service_content', 'not_empty', I18N::T('nrii', '请输入中心介绍!'))
				->validate('equrl', 'not_empty', I18N::T('nrii', '请输入仪器中心网址!'))
				->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
				->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
				->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
				->validate('contact_address', 'not_empty', I18N::T('nrii', '请输入通信地址!'))
				->validate('zip_code', 'not_empty', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'is_numeric', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'length(6)', I18N::T('nrii', '邮政编码输入有误!'))
				;

			if ($form['accept'] == -1) {
				$form->set_error('accept', I18N::T('nrii', '请选择实验室认证认可!'));
			}

			if ($form['realm'] == '{}' || !$form['realm']) {
				$form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
			}
			else if(count(json_decode($form['realm'], true)) > 4) {
				$form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
			}
			if ($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1) {
				$form->set_error('province', I18N::T('nrii', '请选择联系人通信地址!'));
				$form->set_error('city', I18N::T('nrii', ''));
				$form->set_error('area', I18N::T('nrii', ''));
			}

            $center = O('nrii_center', ['inner_id' => $form['innerId']]);
            if ($center->id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所属单位科学装置编号为{$form['innerId']}的科学仪器中心!"));
            }

            Event::trigger('nrii.center.form.validate', $form);

			$center = O('nrii_center');

			if($form->no_error){
				$center->centname = mb_substr($form['centname'], 0, 50, 'utf-8');
				$center->inner_id = $form['innerId'];
				$center->equrl = mb_substr($form['equrl'], 0, 100, 'utf-8');
				$center->worth = (float)$form['worth'];
				$center->begin_date = $form['beginDate'];
				$center->research_area = (float)$form['research_area'];

				$center->address = $form['area'];
				$center->instru_num = (int)$form['instru_num'];
				$center->accept = $form['accept'];

				$center->service_content = mb_substr($form['service_content'], 0, 200, 'utf-8');
				$center->realm = $form['realm'];

                $center->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $center->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
                $center->email = mb_substr($form['email'], 0, 50, 'utf-8');
				$center->contact_address = mb_substr($form['contact_address'], 0, 100, 'utf-8');
                $center->zip_code = $form['zip_code'];

                Event::trigger('nrii.center.form.extra.submit', $form, $center);

				$center->save();

				$file = Input::file('file');
				if ($file['tmp_name']) {
					try{
						$ext = File::extension($file['name']);
						$center->save_icon(Image::load($file['tmp_name'], $ext));
						$me = L('ME');
						Log::add(strtr('[nrii_center] %user_name[%user_id]修改%center_name[%center_id]科学仪器中心图标', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%center_name'=> $center->cname, '%center_id'=> $center->id]), 'journal');

						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '科学仪器中心图标已更新'));
					}
					catch(Error_Exception $e){
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '科学仪器中心图标更新失败!'));
					}
				}

				if ($center->id) {
					Log::add(strtr('[nrii_center] %user_name[%user_id]添加%center_name[%center_id]科学仪器中心', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%center_name'=> $center->cname, '%center_id'=> $center->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '设置科学仪器中心成功!'));

					URI::redirect(URI::url('!nrii/nrii.center'));
				} else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '添加失败! 请与系统管理员联系。'));
				}

			}
		}

		$breadcrumb = [
			[
				'url' => URI::url('!nrii/nrii.center'),
				'title' => I18N::T('nrii', '科学仪器中心')
			],
			[
				'url' => URI::url('!nrii/center/add'),
				'title' => I18N::T('nrii', '添加')
			]
		];

		$this->layout->body->primary_tabs
			->add_tab('center', ['*' => $breadcrumb])
			->select('center')
			->set('content', V('nrii:center/add', [
					'form' => $form,
				]));
	}

	function edit($id = 0) {
		$center = O('nrii_center', $id);
		if (!$center->id){
			URI::redirect('error/404');
		}
        $form = Form::filter(Input::form());

        if ($form['submit']) {
			$form = Form::filter(Input::form())
				->validate('centname', 'not_empty', I18N::T('nrii', '请输入仪器中心名称	!'))
				->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所在单位仪器中心编号!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
				->validate('worth', 'number(>=0)', I18N::T('nrii', '请输入正确的仪器总值!'))
				->validate('research_area', 'number(>=0)', I18N::T('nrii', '请输入正确的科研用房面积!'))
				->validate('instru_num', 'number(>=0)', I18N::T('nrii', '请输入正确的科研仪器数量!'))
				// ->validate('file', 'not_empty', I18N::T('nrii', '请上传图片!'))
				->validate('service_content', 'not_empty', I18N::T('nrii', '请输入中心介绍!'))
				->validate('equrl', 'not_empty', I18N::T('nrii', '请输入仪器中心网址!'))
				->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
				->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
				->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
				->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
				->validate('contact_address', 'not_empty', I18N::T('nrii', '请输入通信地址!'))
				->validate('zip_code', 'not_empty', I18N::T('nrii', '邮政编码输入有误!'))
				->validate('zip_code', 'is_numeric', I18N::T('nrii', '邮政编码输入有误!'))
				->validate('zip_code', 'length(6)', I18N::T('nrii', '邮政编码输入有误!'))
				;

			if ($form['accept'] == -1) {
				$form->set_error('accept', I18N::T('nrii', '请选择实验室认证认可!'));
			}

			if ($form['realm'] == '{}' || !$form['realm']) {
				$form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
			}
			else if(count(json_decode($form['realm'], true)) > 4) {
				$form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
			}
			if ($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1) {
				$form->set_error('province', I18N::T('nrii', '请选择联系人通信地址!'));
				$form->set_error('city', I18N::T('nrii', ''));
				$form->set_error('area', I18N::T('nrii', ''));
			}

			$file = Input::file('file');
			if ($file['tmp_name']) {
				try{
					$ext = File::extension($file['name']);
					$center->save_icon(Image::load($file['tmp_name'], $ext));
					$me = L('ME');
					Log::add(strtr('[nrii_center] %user_name[%user_id]修改%center_name[%center_id]科学仪器中心图标', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%center_name'=> $center->cname, '%center_id'=> $center->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '科学仪器中心图标已更新'));
				}
				catch(Error_Exception $e){
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '科学仪器中心图标更新失败!'));
				}
			}
            $centerOther = O('nrii_center', ['inner_id' => $form['innerId']]);
            if ($centerOther->id && $centerOther->id != $id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所在单位仪器中心编号为{$form['innerId']}的科学仪器中心!"));
            }

            Event::trigger('nrii.center.form.validate', $form);

			if($form->no_error){
				$center->centname = mb_substr($form['centname'], 0, 50, 'utf-8');
				$center->inner_id = $form['innerId'];
				$center->equrl = mb_substr($form['equrl'], 0, 100, 'utf-8');
				$center->worth = (float)$form['worth'];
				$center->begin_date = $form['beginDate'];
				$center->research_area = (float)$form['research_area'];

				$center->address = $form['area'];
				$center->instru_num = (int)$form['instru_num'];
				$center->accept = $form['accept'];

				$center->service_content = mb_substr($form['service_content'], 0, 200, 'utf-8');
				$center->realm = $form['realm'];

                $center->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $center->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
                $center->email = mb_substr($form['email'], 0, 50, 'utf-8');
				$center->contact_address = mb_substr($form['contact_address'], 0, 100, 'utf-8');
                $center->zip_code = $form['zip_code'];

                Event::trigger('nrii.center.form.extra.submit', $form, $center);

				$center->save();

				if ($center->id) {
					Log::add(strtr('[nrii_center] %user_name[%user_id]编辑%center_name[%center_id]科学仪器中心', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%center_name'=> $center->cname, '%center_id'=> $center->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '编辑科学仪器中心成功!'));

					URI::redirect(URI::url('!nrii/nrii.center'));
				} else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '编辑失败! 请与系统管理员联系。'));
				}

			}
		}

		$breadcrumb = [
			[
				'url' => URI::url('!nrii/nrii.center'),
				'title' => I18N::T('nrii', '科学仪器中心')
			],
			[
				'url' => URI::url('!nrii/center/edit.' . $id),
				'title' => I18N::T('nrii', '编辑')
			]
		];

		$this->layout->body->primary_tabs
			->add_tab('center', ['*' => $breadcrumb])
			->select('center')
			->set('content', V('nrii:center/edit', [
					'form' => $form,
					'center' => $center
				]));
	}
	function delete($id = 0) {
		$center = O('nrii_center', $id);

		if (!$center->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		// if (!$me->is_allowed_to('删除', $center)) {
		// 	URI::redirect('error/401');
		// }

		Log::add(strtr('[nrii_center] %user_name[%user_id]删除%center_name[%center_id]科学仪器中心', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%center_name'=> $center->cname, '%center_id'=> $center->id]), 'journal');
		$center->delete_icon();
		if ($center->delete()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '科学仪器中心删除成功!'));
		}
		URI::redirect(URI::url('!nrii/nrii.center'));

	}

	function import() {
		$file = Input::file('file');
		if ($file['tmp_name']) {
			try{
				$import = new Nrii_Import();
				$result = $import->center($file['tmp_name']);
				$me = L('ME');
				Log::add(strtr('[nrii_center] %user_name[%user_id]批量导入科学仪器中心', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

				$_SESSION['center_import'] = $result;

			}
			catch(Error_Exception $e){
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '科学仪器中心导入失败!'));
			}
		}else{
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '请选择您要上传的科学仪器中心csv文件!'));
		}
        // URI::redirect(URI::url('!nrii/nrii.device'));
        exit;
	}

	function sync() {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php nrii sync_center';
        //增加传递的参数
        $cmd .= " ".L('ME')->id;
        $cmd .= " >/dev/null 2>&1 &";

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        // $pid = intval($var['pid']) + 1;

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '上传科学仪器中心至国家科技部成功!'));

        URI::redirect(URI::url('!nrii/nrii.center'));
    }
}

class Center_AJAX_Controller extends AJAX_Controller {

	function index_import_click() {
		JS::dialog((string)V('nrii:upload',[
			'mode' => 'center'
		]));
	}

	function index_center_export_click() {

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $selector = $_SESSION['nrii_center'];

		$pid = $this->_export_csv($selector, $file_name);
		JS::dialog(V('export_wait', [
			'file_name' => $file_name,
			'pid' => $pid
		]), [
			'title' => I18N::T('calendars', '导出等待')
		]);

    }

	private function _export_csv($selector, $file_name) {
		$me = L('ME');
		$form = [
			'form_token' => '',
			'selector' => ''
		];
		$valid_columns = Config::get('columns.export_columns.center');

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
		$samplesp= Q($selector);
		putenv('Q_ROOT_PATH=' . ROOT_PATH);
		$cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_center export ';
		$cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
		// exec($cmd, $output);
		$process = proc_open($cmd, [], $pipes);
		$var = proc_get_status($process);
		proc_close($process);
		$pid = intval($var['pid']) + 1;
		$valid_form = $form['form'];
		unset($valid_form['form_token']);
		unset($valid_form['selector']);
		$_SESSION[$me->id.'-export'][$pid] = $valid_form;
		return $pid;
	}
}

