<?php

class Device_Controller extends Base_Controller {
	function index() {
        URI::redirect(URI::url('!nrii/nrii.device'));
    }

	function add() {
        $form = Form::filter(Input::form());

        if ($form['submit']) {
			$form = Form::filter(Input::form())
				->validate('cname', 'not_empty', I18N::T('nrii', '请输入中文名称!'))
				->validate('ename', 'not_empty', I18N::T('nrii', '请输入英文名称!'))
				->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所属单位科学装置编号!'))
				->validate('worth', 'not_empty', I18N::T('nrii', '请输入正确原值!'))
				->validate('worth', 'is_numeric', I18N::T('nrii', '请输入正确原值!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
				->validate('street', 'not_empty', I18N::T('nrii', '请输入安放地址!'))
				->validate('url', 'not_empty', I18N::T('nrii', '请输入装置网站的网址!'))
				->validate('technical', 'not_empty', I18N::T('nrii', '请输入科学技术中心!'))
				->validate('function', 'not_empty', I18N::T('nrii', '请输入主要功能及技术指标!'))
				->validate('requirement', 'not_empty', I18N::T('nrii', '请输入国外主要单位用户!'))
				->validate('service_content', 'not_empty', I18N::T('nrii', '请输入国内主要单位用户!'))
				->validate('achievement', 'not_empty', I18N::T('nrii', '请输入服务典型成果!'))
				->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
				->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
				->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('fill_position', 'not_empty', I18N::T('nrii', '请填写联系人-职务'))
                ->validate('fill_insname', 'not_empty', I18N::T('nrii', '请填写联系人-单位'))
				->validate('competent_dep', 'not_empty', I18N::T('nrii', '请输入主管部门!'))
				->validate('sup_insname', 'not_empty', I18N::T('nrii', '请输入依托单位!'))
				->validate('approval_dep', 'not_empty', I18N::T('nrii', '请输入批复部门!'))
				->validate('video', 'not_empty', I18N::T('nrii', '请输入科普视频网址!'))
				->validate('sci_contact', 'not_empty', I18N::T('nrii', '请输入首席科学家-姓名!'))
				->validate('sci_position', 'not_empty', I18N::T('nrii', '请输入首席科学家-职务!'))
				->validate('sci_insname', 'not_empty', I18N::T('nrii', '请输入首席科学家-单位!'))
				->validate('sci_phone', 'not_empty', I18N::T('nrii', '请输入首席科学家-电话!'))
				->validate('sci_email', 'not_empty', I18N::T('nrii', '首席科学家-邮箱填写有误!'))
				->validate('sci_email', 'is_email', I18N::T('nrii', '首席科学家-邮箱填写有误!'))
				->validate('run_contact', 'not_empty', I18N::T('nrii', '请输入运行负责人-职务!'))
				->validate('run_insname', 'not_empty', I18N::T('nrii', '请输入运行负责人-单位!'))
				->validate('run_phone', 'not_empty', I18N::T('nrii', '请输入运行负责人-电话!'))
				->validate('run_email', 'not_empty', I18N::T('nrii', '运行负责人-邮箱有误!'))
				->validate('run_email', 'is_email', I18N::T('nrii', '运行负责人-邮箱有误!'))
				->validate('layout_image', 'not_empty', I18N::T('nrii', '请输入布局图下载地址!'))
				->validate('key_image', 'not_empty', I18N::T('nrii', '请输入关键部件图下载地址!'))
				->validate('experiment_image', 'not_empty', I18N::T('nrii', '请输入实验操作图下载地址!'))
				->validate('organization_file', 'not_empty', I18N::T('nrii', '请输入组织管理制度下载地址!'))
				->validate('open_file', 'not_empty', I18N::T('nrii', '请输入开放收费制度下载地址!'))
				->validate('apply_file', 'not_empty', I18N::T('nrii', '请输入设施申请制度下载地址!'))
				;



			if($form['device_category'] == -1){
				$form->set_error('device_category', I18N::T('nrii', '请选择设施类别!'));
			}
			if($form['construction'] == -1){
				$form->set_error('construction', I18N::T('nrii', '请选择建设情况!'));
			}

			if($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1){
				$form->set_error('province', I18N::T('nrii', '请选择安放地址!'));
				$form->set_error('city', I18N::T('nrii', ''));
				$form->set_error('area', I18N::T('nrii', ''));
			}

			if($form['worth'] < 0 ){
				$form->set_error('worth', I18N::T('nrii', '建设经费不能设置为负数'));
			}

			if($form['realm'] == '{}'){
				$form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
			}elseif(count(json_decode($form['realm'], true)) > 4){
				$form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
			}

            $device = O('nrii_device', ['inner_id' => $form['innerId']]);
            if ($device->id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所属单位科学装置编号为{$form['innerId']}的大型科学装置!"));
			}

            Event::trigger('nrii.device.form.validate', $form);

			$device = O('nrii_device');

			if($form->no_error){
				$device->cname = mb_substr($form['cname'], 0, 50, 'utf-8');
				$device->ename = mb_substr($form['ename'], 0, 100, 'utf-8');
				$device->inner_id = $form['innerId'];
				$device->worth = (double)$form['worth'];
				$device->begin_date = $form['beginDate'];
				$device->address = $form['area'];
				$device->street = mb_substr($form['street'], 0, 100, 'utf-8');
				$device->realm = $form['realm'];

				$device->url = mb_substr($form['url'], 0, 100, 'utf-8');
                $device->technical = mb_substr($form['technical'], 0, 300, 'utf-8');
                $device->function = mb_substr($form['function'], 0, 300, 'utf-8');
                $device->requirement = mb_substr($form['requirement'], 0, 300, 'utf-8');
                $device->service_content = mb_substr($form['service_content'], 0, 300, 'utf-8');

                $device->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $device->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
				$device->email = mb_substr($form['email'], 0, 50, 'utf-8');

				$device->ename_short = $form['ename_short'];
				$device->competent_dep = $form['competent_dep'];
				$device->sup_insname = $form['sup_insname'];
				$device->device_category = $form['device_category'];
				$device->construction = $form['construction'];
				$device->approval_dep = $form['approval_dep'];
				$device->video = mb_substr($form['video'], 0, 100, 'utf-8');
				$device->sci_contact = $form['sci_contact'];
				$device->sci_position = $form['sci_position'];
				$device->sci_insname = $form['sci_insname'];
				$device->sci_phone = $form['sci_phone'];
				$device->sci_email = $form['sci_email'];
				$device->run_contact = $form['run_contact'];
				$device->run_position = $form['run_position'];
				$device->run_insname = $form['run_insname'];
				$device->run_phone = $form['run_phone'];
				$device->run_email = $form['run_email'];
				$device->fill_position = $form['fill_position'];
				$device->fill_insname = $form['fill_insname'];
				$device->achievement = mb_substr($form['achievement'], 0, 2500, 'utf-8');

				$device->layout_image = $form['layout_image'];
				$device->key_image = $form['key_image'];
				$device->experiment_image = $form['experiment_image'];
				$device->organization_file = $form['organization_file'];
				$device->open_file = $form['open_file'];
				$device->apply_file = $form['apply_file'];
				$device->research_file_one = $form['research_file_one']?:'';
				$device->research_file_two = $form['research_file_two']?:'';
				$device->research_file_three = $form['research_file_three']?:'';
				$device->research_file_four = $form['research_file_four']?:'';
				$device->research_file_five = $form['research_file_five']?:'';

                Event::trigger('nrii.device.form.extra.submit', $form, $device);

				$device->save();

				$file = Input::file('file');
				if ($file['tmp_name']) {
					try{
						$ext = File::extension($file['name']);
						$device->save_icon(Image::load($file['tmp_name'], $ext));
						$me = L('ME');
						Log::add(strtr('[nrii_device] %user_name[%user_id]修改%device_name[%device_id]大型科学装置图标', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%device_name'=> $device->cname, '%device_id'=> $device->id]), 'journal');

						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '大型科学装置图标已更新'));
					}
					catch(Error_Exception $e){
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '大型科学装置图标更新失败!'));
					}
				}


				if ($device->id) {
					Log::add(strtr('[nrii_device] %user_name[%user_id]添加%device_name[%device_id]大型科学装置', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%device_name'=> $device->cname, '%device_id'=> $device->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '设置大型科学装置成功!'));

					URI::redirect(URI::url('!nrii/nrii.device'));
				} else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '添加失败! 请与系统管理员联系。'));
				}

			}
		}

		$breadcrumb = [
			[
				'url' => URI::url('!nrii/nrii.device'),
				'title' => I18N::T('nrii', '大型科学装置')
			],
			[
				'url' => URI::url('!nrii/device/add'),
				'title' => I18N::T('nrii', '添加')
			]
		];

		$this->layout->body->primary_tabs
			->add_tab('device', ['*' => $breadcrumb])
			->select('device')
			->set('content', V('nrii:device/add', [
					'form' => $form,
				]));
	}

	function edit($id = 0) {
		$device = O('nrii_device',$id);
		if (!$device->id){
			URI::redirect('error/404');
		}

		$form = Form::filter(Input::form());

        if ($form['submit']) {
			$form = Form::filter(Input::form())
				->validate('cname', 'not_empty', I18N::T('nrii', '请输入中文名称!'))
				->validate('ename', 'not_empty', I18N::T('nrii', '请输入英文名称!'))
				->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所属单位科学装置编号!'))
				->validate('worth', 'not_empty', I18N::T('nrii', '请输入正确原值!'))
				->validate('worth', 'is_numeric', I18N::T('nrii', '请输入正确原值!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
				->validate('street', 'not_empty', I18N::T('nrii', '请输入安放地址!'))
				->validate('url', 'not_empty', I18N::T('nrii', '请输入装置网站的网址!'))
				->validate('technical', 'not_empty', I18N::T('nrii', '请输入科学技术中心!'))
				->validate('function', 'not_empty', I18N::T('nrii', '请输入主要功能及技术指标!'))
				->validate('requirement', 'not_empty', I18N::T('nrii', '请输入国外主要单位用户!'))
				->validate('service_content', 'not_empty', I18N::T('nrii', '请输入国内主要单位用户!'))
				->validate('achievement', 'not_empty', I18N::T('nrii', '请输入服务典型成果!'))
				->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
				->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
				->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('fill_position', 'not_empty', I18N::T('nrii', '请填写联系人-职务'))
                ->validate('fill_insname', 'not_empty', I18N::T('nrii', '请填写联系人-单位'))
				->validate('competent_dep', 'not_empty', I18N::T('nrii', '请输入主管部门!'))
				->validate('sup_insname', 'not_empty', I18N::T('nrii', '请输入依托单位!'))
				->validate('approval_dep', 'not_empty', I18N::T('nrii', '请输入批复部门!'))
				->validate('video', 'not_empty', I18N::T('nrii', '请输入科普视频网址!'))
				->validate('sci_contact', 'not_empty', I18N::T('nrii', '请输入首席科学家-姓名!'))
				->validate('sci_position', 'not_empty', I18N::T('nrii', '请输入首席科学家-职务!'))
				->validate('sci_insname', 'not_empty', I18N::T('nrii', '请输入首席科学家-单位!'))
				->validate('sci_phone', 'not_empty', I18N::T('nrii', '请输入首席科学家-电话!'))
				->validate('sci_email', 'not_empty', I18N::T('nrii', '首席科学家-邮箱填写有误!'))
				->validate('sci_email', 'is_email', I18N::T('nrii', '首席科学家-邮箱填写有误!'))
				->validate('run_contact', 'not_empty', I18N::T('nrii', '请输入运行负责人-职务!'))
				->validate('run_insname', 'not_empty', I18N::T('nrii', '请输入运行负责人-单位!'))
				->validate('run_phone', 'not_empty', I18N::T('nrii', '请输入运行负责人-电话!'))
				->validate('run_email', 'not_empty', I18N::T('nrii', '运行负责人-邮箱有误!'))
				->validate('run_email', 'is_email', I18N::T('nrii', '运行负责人-邮箱有误!'))
				->validate('layout_image', 'not_empty', I18N::T('nrii', '请输入布局图下载地址!'))
				->validate('key_image', 'not_empty', I18N::T('nrii', '请输入关键部件图下载地址!'))
				->validate('experiment_image', 'not_empty', I18N::T('nrii', '请输入实验操作图下载地址!'))
				->validate('organization_file', 'not_empty', I18N::T('nrii', '请输入组织管理制度下载地址!'))
				->validate('open_file', 'not_empty', I18N::T('nrii', '请输入开放收费制度下载地址!'))
				->validate('apply_file', 'not_empty', I18N::T('nrii', '请输入设施申请制度下载地址!'))
				;

			if($form['device_category'] == -1){
				$form->set_error('device_category', I18N::T('nrii', '请选择设施类别!'));
			}
			if($form['construction'] == -1){
				$form->set_error('construction', I18N::T('nrii', '请选择建设情况!'));
			}

			if($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1){
				$form->set_error('province', I18N::T('nrii', '请选择安放地址!'));
				$form->set_error('city', I18N::T('nrii', ''));
				$form->set_error('area', I18N::T('nrii', ''));
			}

			if($form['worth'] < 0 ){
				$form->set_error('worth', I18N::T('nrii', '建设经费不能设置为负数'));
			}

			if($form['realm'] == '{}'){
				$form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
			}elseif(count(json_decode($form['realm'], true)) > 4){
				$form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
			}

			$file = Input::file('file');
			if ($file['tmp_name']) {
				try{
					$ext = File::extension($file['name']);
					$device->save_icon(Image::load($file['tmp_name'], $ext));
					$me = L('ME');
					Log::add(strtr('[nrii_device] %user_name[%user_id]修改%device_name[%device_id]大型科学装置图标', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%device_name'=> $device->cname, '%device_id'=> $device->id]), 'journal');

					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '大型科学装置图标已更新'));
				}
				catch(Error_Exception $e){
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '大型科学装置图标更新失败!'));
				}
			}
            $deviceOther = O('nrii_device', ['inner_id' => $form['innerId']]);
            if ($deviceOther->id && $deviceOther->id != $id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所属单位科学装置编号为{$form['innerId']}的大型科学装置!"));
            }

            Event::trigger('nrii.device.form.validate', $form);

			if($form->no_error){
				$device->cname = mb_substr($form['cname'], 0, 50, 'utf-8');
				$device->ename = mb_substr($form['ename'], 0, 100, 'utf-8');
				$device->inner_id = $form['innerId'];
				$device->worth = (double)$form['worth'];
				$device->begin_date = $form['beginDate'];
				$device->address = $form['area'];
				$device->street = mb_substr($form['street'], 0, 100, 'utf-8');
				$device->realm = $form['realm'];

				$device->url = mb_substr($form['url'], 0, 100, 'utf-8');
                $device->technical = mb_substr($form['technical'], 0, 300, 'utf-8');
                $device->function = mb_substr($form['function'], 0, 300, 'utf-8');
                $device->requirement = mb_substr($form['requirement'], 0, 300, 'utf-8');
                $device->service_content = mb_substr($form['service_content'], 0, 300, 'utf-8');

                $device->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $device->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
				$device->email = mb_substr($form['email'], 0, 50, 'utf-8');

				$device->ename_short = $form['ename_short'];
				$device->competent_dep = $form['competent_dep'];
				$device->sup_insname = $form['sup_insname'];
				$device->device_category = $form['device_category'];
				$device->construction = $form['construction'];
				$device->approval_dep = $form['approval_dep'];
				$device->video = mb_substr($form['video'], 0, 100, 'utf-8');
				$device->sci_contact = $form['sci_contact'];
				$device->sci_position = $form['sci_position'];
				$device->sci_insname = $form['sci_insname'];
				$device->sci_phone = $form['sci_phone'];
				$device->sci_email = $form['sci_email'];
				$device->run_contact = $form['run_contact'];
				$device->run_position = $form['run_position'];
				$device->run_insname = $form['run_insname'];
				$device->run_phone = $form['run_phone'];
				$device->run_email = $form['run_email'];
				$device->fill_position = $form['fill_position'];
				$device->fill_insname = $form['fill_insname'];
				$device->achievement = mb_substr($form['achievement'], 0, 2500, 'utf-8');

				$device->layout_image = $form['layout_image'];
				$device->key_image = $form['key_image'];
				$device->experiment_image = $form['experiment_image'];
				$device->organization_file = $form['organization_file'];
				$device->open_file = $form['open_file'];
				$device->apply_file = $form['apply_file'];
				$device->research_file_one = $form['research_file_one']?:'';
				$device->research_file_two = $form['research_file_two']?:'';
				$device->research_file_three = $form['research_file_three']?:'';
				$device->research_file_four = $form['research_file_four']?:'';
				$device->research_file_five = $form['research_file_five']?:'';

                Event::trigger('nrii.device.form.extra.submit', $form, $device);
				$device->save();

				if ( $device->save()) {
					Log::add(strtr('[nrii_device] %user_name[%user_id]编辑%device_name[%device_id]大型科学装置', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%device_name'=> $device->cname, '%device_id'=> $device->id]), 'journal');
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '编辑大型科学装置成功!'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '编辑失败! 请与系统管理员联系。'));
				}

			}
		}
		$breadcrumb = [
			[
				'url' => URI::url('!nrii/nrii.device'),
				'title' => I18N::T('nrii', '大型科学装置')
			],
			[
				'url' => URI::url('!nrii/device/edit.' . $id),
				'title' => I18N::T('nrii', '编辑')
			]
		];

		$this->layout->body->primary_tabs
			->add_tab('device', ['*' => $breadcrumb])
			->select('device')
			->set('content', V('nrii:device/edit', [
					'form' => $form,
					'device' => $device
				]));
	}

	function delete($id = 0) {
		$device = O('nrii_device', $id);

		if (!$device->id) {
			URI::redirect('error/404');
		}

		$me = L('ME');
		// if (!$me->is_allowed_to('删除', $device)) {
		// 	URI::redirect('error/401');
		// }

		Log::add(strtr('[nrii_device] %user_name[%user_id]删除%device_name[%device_id]大型科学装置', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%device_name'=> $device->cname, '%device_id'=> $device->id]), 'journal');
		$device->delete_icon();
		if ($device->delete()) {
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '大型科学装置删除成功!'));
		}
		URI::redirect(URI::url('!nrii/nrii.device'));
	}

	function import() {
		$file = Input::file('file');
		if ($file['tmp_name']) {
			try{
				$import = new Nrii_Import();
				$result = $import->device($file['tmp_name']);
				$me = L('ME');
				Log::add(strtr('[nrii_device] %user_name[%user_id]批量导入大型科学装置', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

				$_SESSION['device_import'] = $result;

			}
			catch(Error_Exception $e){
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '大型科学装置导入失败!'));
			}
		}else{
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '请选择您要上传的科学仪器中心csv文件!'));
		}
		exit;
	}

	function sync() {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php nrii sync_device';
        //增加传递的参数
        $cmd .= " ".L('ME')->id;
        $cmd .= " >/dev/null 2>&1 &";

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        // $pid = intval($var['pid']) + 1;

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '上传大型科学装置至国家科技部成功!'));

        URI::redirect(URI::url('!nrii/nrii.device'));
	}
}

class Device_AJAX_Controller extends AJAX_Controller {

	function index_import_click() {
		JS::dialog((string)V('nrii:upload',[
			'mode' => 'device'
		]));
	}

	function index_device_export_click() {

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $selector = $_SESSION['nrii_device'];

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
		$valid_columns = Config::get('columns.export_columns.device');

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
		$cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_device export ';
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
