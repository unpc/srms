<?php

class Unit_Controller extends Base_Controller {
	function index() {
        URI::redirect(URI::url('!nrii/nrii.unit'));
    }

	function add()
	{
		$form = Form::filter(Input::form());

        if ($form['submit']) {
			$form = Form::filter(Input::form())
				->validate('unitname', 'not_empty', I18N::T('nrii', '请输入服务单元名称!'))
				->validate('org', 'not_empty', I18N::T('nrii', '请输入所属单位!'))
				->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所属单位科学装置编号!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
				->validate('serviceUrl', 'not_empty', I18N::T('nrii', '请输入预约服务网址!'))
				// ->validate('file', 'not_empty', I18N::T('nrii', '请上传图片!'))
				->validate('function', 'not_empty', I18N::T('nrii', '请输入主要功能!'))
				->validate('street', 'not_empty', I18N::T('nrii', '请输入安放地址!'))
				->validate('service_content', 'not_empty', I18N::T('nrii', '请输入服务内容!'))
				->validate('achievement', 'not_empty', I18N::T('nrii', '请输入服务典型成果!'))
				->validate('requirement', 'not_empty', I18N::T('nrii', '请输入对外开放共享规定!'))
				->validate('fee', 'not_empty', I18N::T('nrii', '请输入参考收费标准!'))
				->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
				->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
				->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
				->validate('contact_street', 'not_empty', I18N::T('nrii', '请输入通信地址!'))
				->validate('zip_code', 'not_empty', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'length(6)', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'is_numeric', I18N::T('nrii', '邮政编码输入有误!'))
				;
			if($form['category'] == -1){
				$form->set_error('category', I18N::T('nrii', '请选择服务单元类别!'));
			}
			if($form['status'] == -1){
				$form->set_error('status', I18N::T('nrii', '请选择运行状态!'));
			}
			if($form['shareMode'] == -1){
				$form->set_error('shareMode', I18N::T('nrii', '请选择共享模式!'));
			}
			if($form['realm'] == '{}'){
				$form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
			}elseif(count(json_decode($form['realm'], true)) > 4){
				$form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
			}
			if($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1){
				$form->set_error('province', I18N::T('nrii', '请选择安放地址!'));
				$form->set_error('city', I18N::T('nrii', ''));
				$form->set_error('area', I18N::T('nrii', ''));
			}

            $unit = O('nrii_unit', ['inner_id' => $form['innerId']]);
            if ($unit->id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所属单位科学装置编号为{$form['innerId']}的科学仪器服务单元!"));
            }
			$unit = O('nrii_unit');	

			if($form->no_error){
				$unit->unitname = mb_substr($form['unitname'], 0, 50, 'utf-8');
				$unit->org = $form['org'];
				$unit->category = $form['category'];
				$unit->inner_id = $form['innerId'];
				$unit->status = $form['status'];
				$unit->begin_date = $form['beginDate'];
				$unit->share_mode = $form['shareMode'];
				$unit->realm = $form['realm'];
				$unit->service_url = $form['serviceUrl'];
				$unit->address = $form['area'];
				$unit->street = mb_substr($form['street'], 0, 100, 'utf-8');
                $unit->function = mb_substr($form['function'], 0, 300, 'utf-8');
                $unit->requirement = mb_substr($form['requirement'], 0, 500, 'utf-8');
                $unit->service_content = mb_substr($form['service_content'], 0, 200, 'utf-8');
                $unit->achievement = mb_substr($form['achievement'], 0, 500, 'utf-8');
                $unit->fee = mb_substr($form['fee'], 0, 500, 'utf-8');

                $unit->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $unit->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
                $unit->email = mb_substr($form['email'], 0, 50, 'utf-8');
                $unit->contact_street = mb_substr($form['contact_street'], 0, 100, 'utf-8');
                $unit->zip_code = $form['zip_code'];
				
				$unit->save();

				$file = Input::file('file');
				if ($file['tmp_name']) {
					try{
						$ext = File::extension($file['name']);
						$unit->save_icon(Image::load($file['tmp_name'], $ext));
						$me = L('ME');
						Log::add(strtr('[nrii_unit] %user_name[%user_id]修改%unit_name[%unit_id]科学仪器服务单元图标', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%unit_name'=> $unit->cname, '%unit_id'=> $unit->id]), 'journal');

						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '科学仪器服务单元图标已更新'));
					}
					catch(Error_Exception $e){
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '科学仪器服务单元图标更新失败!'));
					}
				}
			
				if ($unit->id) {
					Log::add(strtr('[nrii_unit] %user_name[%user_id]添加%unit_name[%unit_id]科学仪器服务单元', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%unit_name'=> $unit->cname, '%unit_id'=> $unit->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '设置科学仪器服务单元成功!'));

					URI::redirect(URI::url('!nrii/nrii.unit'));
				} else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '添加失败! 请与系统管理员联系。'));
				}

			}
		}
		
		$breadcrumb = [
			[
				'url' => URI::url('!nrii/nrii.unit'),
				'title' => I18N::T('nrii', '科学仪器服务单元')
			],
			[
				'url' => URI::url('!nrii/unit/add'),
				'title' => I18N::T('nrii', '添加')
			]
		];

		$this->layout->body->primary_tabs
			->add_tab('unit', ['*' => $breadcrumb])
			->select('unit')
			->set('content', V('nrii:unit/add', [
					'form' => $form,
				]));
	}

	function edit($id = 0) {
		$unit = O('nrii_unit',$id);
		if (!$unit->id){
			URI::redirect('error/404');
		}

		$form = Form::filter(Input::form());

        if ($form['submit']) {
			$form = Form::filter(Input::form())
				->validate('unitname', 'not_empty', I18N::T('nrii', '请输入服务单元名称!'))
				->validate('org', 'not_empty', I18N::T('nrii', '请输入所属单位!'))
				->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所属单位科学装置编号!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
				->validate('serviceUrl', 'not_empty', I18N::T('nrii', '请输入预约服务网址!'))
				// ->validate('file', 'not_empty', I18N::T('nrii', '请上传图片!'))
				->validate('function', 'not_empty', I18N::T('nrii', '请输入主要功能!'))
				->validate('street', 'not_empty', I18N::T('nrii', '请输入安放地址!'))
				->validate('service_content', 'not_empty', I18N::T('nrii', '请输入服务内容!'))
				->validate('achievement', 'not_empty', I18N::T('nrii', '请输入服务典型成果!'))
				->validate('requirement', 'not_empty', I18N::T('nrii', '请输入对外开放共享规定!'))
				->validate('fee', 'not_empty', I18N::T('nrii', '请输入参考收费标准!'))
				->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
				->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
				->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
				->validate('contact_street', 'not_empty', I18N::T('nrii', '请输入通信地址!'))
				->validate('zip_code', 'not_empty', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'length(6)', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'is_numeric', I18N::T('nrii', '邮政编码输入有误!'))
				;
			if($form['category'] == -1){
				$form->set_error('category', I18N::T('nrii', '请选择服务单元类别!'));
			}
			if($form['status'] == -1){
				$form->set_error('status', I18N::T('nrii', '请选择运行状态!'));
			}
			if($form['shareMode'] == -1){
				$form->set_error('shareMode', I18N::T('nrii', '请选择共享模式!'));
			}
			if($form['realm'] == '{}'){
				$form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
			}elseif(count(json_decode($form['realm'], true)) > 4){
				$form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
			}
			if($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1){
				$form->set_error('province', I18N::T('nrii', '请选择安放地址!'));
				$form->set_error('city', I18N::T('nrii', ''));
				$form->set_error('area', I18N::T('nrii', ''));
			}
			$file = Input::file('file');
			if ($file['tmp_name']) {
				try{
					$ext = File::extension($file['name']);
					$unit->save_icon(Image::load($file['tmp_name'], $ext));
					$me = L('ME');
					Log::add(strtr('[nrii_unit] %user_name[%user_id]修改%unit_name[%unit_id]科学仪器服务单元图标', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%unit_name'=> $unit->cname, '%unit_id'=> $unit->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '科学仪器服务单元图标已更新'));
				}
				catch(Error_Exception $e){
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '科学仪器服务单元图标更新失败!'));
				}
			}

            $unitOther = O('nrii_unit', ['inner_id' => $form['innerId']]);
            if ($unitOther->id && $unitOther->id != $id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所属单位科学编号为{$form['innerId']}的科学仪器服务单元!"));
            }

			if($form->no_error){
				$unit->unitname = mb_substr($form['unitname'], 0, 50, 'utf-8');
				$unit->org = $form['org'];
				$unit->category = $form['category'];
				$unit->inner_id = $form['innerId'];
				$unit->status = $form['status'];
				$unit->begin_date = $form['beginDate'];
				$unit->share_mode = $form['shareMode'];
				$unit->realm = $form['realm'];
				$unit->service_url = $form['serviceUrl'];
				$unit->address = $form['area'];
				$unit->street = mb_substr($form['street'], 0, 100, 'utf-8');
                $unit->function = mb_substr($form['function'], 0, 300, 'utf-8');
                $unit->requirement = mb_substr($form['requirement'], 0, 500, 'utf-8');
                $unit->service_content = mb_substr($form['service_content'], 0, 200, 'utf-8');
                $unit->achievement = mb_substr($form['achievement'], 0, 500, 'utf-8');
                $unit->fee = mb_substr($form['fee'], 0, 500, 'utf-8');

                $unit->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $unit->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
                $unit->email = mb_substr($form['email'], 0, 50, 'utf-8');
                $unit->contact_street = mb_substr($form['contact_street'], 0, 100, 'utf-8');
                $unit->zip_code = $form['zip_code'];
				
				$unit->save();

				if ($unit->id) {
					Log::add(strtr('[nrii_unit] %user_name[%user_id]添加%unit_name[%unit_id]科学仪器服务单元', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%unit_name'=> $unit->cname, '%unit_id'=> $unit->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '设置科学仪器服务单元成功!'));

					URI::redirect(URI::url('!nrii/nrii.unit'));
				} else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '添加失败! 请与系统管理员联系。'));
				}

			}
		}


		$breadcrumb = [
			[
				'url' => URI::url('!nrii/nrii.unit'),
				'title' => I18N::T('nrii', '科学仪器服务单元')
			],
			[
				'url' => URI::url('!nrii/unit/edit.' . $id),
				'title' => I18N::T('nrii', '编辑')
			]
		];

		$this->layout->body->primary_tabs
			->add_tab('unit', ['*' => $breadcrumb])
			->select('unit')
			->set('content', V('nrii:unit/edit', [
					'form' => $form,
					'unit' => $unit
				]));
	}

	function delete($id = 0) {
		$unit = O('nrii_unit', $id);

		if (!$unit->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		// if (!$me->is_allowed_to('删除', $unit)) {
		// 	URI::redirect('error/401');
		// }

		Log::add(strtr('[nrii_unit] %user_name[%user_id]删除%unit_name[%unit_id]科学仪器服务单元', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%unit_name'=> $unit->cname, '%unit_id'=> $unit->id]), 'journal');
		$unit->delete_icon();
		if ($unit->delete()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '科学仪器服务单元删除成功!'));
		}
		URI::redirect(URI::url('!nrii/nrii.unit'));
		
	}

	function import() {
		$file = Input::file('file');
		if ($file['tmp_name']) {
			try{
				$import = new Nrii_Import();
				$result = $import->unit($file['tmp_name']);
				$me = L('ME');
				Log::add(strtr('[nrii_unit] %user_name[%user_id]批量导入科学仪器服务单元', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

				$_SESSION['unit_import'] = $result;

			}
			catch(Error_Exception $e){
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '科学仪器服务单元导入失败!'));
			}
		}else{
			$form->set_error('file', I18N::T('nrii', '请选择您要上传的科学仪器服务单元csv文件!'));
		}
		exit;
	}

	function sync() {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php nrii sync_unit';
        $cmd .= " >/dev/null 2>&1 &";

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        // $pid = intval($var['pid']) + 1;

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '上传科学仪器服务单元至国家科技部成功!'));

        URI::redirect(URI::url('!nrii/nrii.unit'));
    }
}

class Unit_AJAX_Controller extends AJAX_Controller {

	function index_import_click() {
		JS::dialog((string)V('nrii:upload',[
			'mode' => 'unit'
		]));
	}
}
