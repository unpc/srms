<?php
/*
NO.TASK282(guoping.zhang@2010.12.02）
仪器送样预约开发
*/
class Index_Controller extends Layout_Controller {

    public function entries($tab) {
        $me = L("ME");

        if(!$me->id){
            URI::redirect('error/401');
        }
        $this->layout->body = V('body');
        $this->layout->title = '送样记录';
        $tabs = Widget::factory("tabs");
        $allow_tabs = [];

        if ($me->is_allowed_to('列表仪器送样', Q("$me lab")->current())) {
            $tabs
                ->add_tab('lab', [
                    'url'   => URI::url('!eq_sample/index/entries.lab'),
                    'title' => I18N::T('eq_sample', '组内送样记录'),
                ]);
            !$tab && $tab = 'lab';
            $allow_tabs[] = 'lab';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Sample::lab_tab_content', 0, 'lab');
            Event::bind('equipment.samples.entries.view.tool_box', 'EQ_Sample::lab_tab_tool', 0, 'lab');
        }
        if (Q("$me<incharge equipment")->total_count() > 0) {
            $tabs
                ->add_tab('incharge', [
                    'url'=>URI::url('!eq_sample/index/entries.incharge'),
                    'title'=>I18N::T('eq_sample', '您负责的所有仪器的送样记录'),
                ]);
            !$tab && $tab = 'incharge';
            $allow_tabs[] = 'incharge';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Sample::sample_primary_tab_content', 100, 'incharge');
        }
        if ($me->access('管理所有内容') || $me->access('添加/修改下属机构的仪器')) {
            $tabs
                ->add_tab('group', [
                    'url'=>URI::url('!eq_sample/index/entries.group'),
                    'title'=>I18N::T('eq_sample', '下属机构所有仪器的送样记录'),
                ]);
            !$tab && $tab = 'group';
            $allow_tabs[] = 'group';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Sample::sample_primary_tab_content', 100, 'group');
        }

        if ($me->access('管理所有内容')) {
            $tabs
                ->add_tab('all', [
                    'url'=>URI::url('!eq_sample/index/entries.all'),
                    'title'=>I18N::T('eq_sample', '所有仪器的送样记录'),
                ]);
            !$tab && $tab = 'all';
            $allow_tabs[] = 'all';
            Event::bind('equipment.samples.entries.view.content', 'EQ_Sample::sample_primary_tab_content', 100, 'all');
        }

        if (!in_array($tab, $allow_tabs)) {
            URI::redirect('error/401');
        }

        switch ($tab) {
            case "lab":
                $tabs->lab = Q("$me lab")->current();
                break;
            case "incharge":
                $tabs->selected = 'sample';
                break;
            case "group":        
                $tabs->group = $me->group;
                break;
            case "all":;break;
        }

        $tabs
            ->tab_event('equipment.samples.entries.view.tab')
            ->content_event('equipment.samples.entries.view.content')
            ->tool_event('equipment.samples.entries.view.tool_box')
            ->select($tab);

        $this->layout->body->primary_tabs = $tabs;

    }

    public function me(){
        $me = L('ME');
        $user = O("user", $me->id);
        if (!$user->id) {
            URI::redirect('error/401');
       }
        $this->layout->body = V('body');
		$this->layout->body->primary_tabs=Widget::factory('tabs');
        $this->layout->body->primary_tabs->user = $user;
        EQ_Sample::user_sample_content(null, $this->layout->body->primary_tabs);
        EQ_Sample::_tool_box_user_view_sample(null, $this->layout->body->primary_tabs);
    }

	//向申请送样者发送消息
	function send($id) {
		$sample = O('eq_sample', $id);
		$me = L('ME');
		if (!$sample->id || !$me->is_allowed_to('发送消息', $sample)|| !Config::get('messages.add_message.switch_on', TRUE)) {
			URI::redirect('error/401');
		}
		$url = O('message')->url([$sample->sender->id, $sample->equipment->id], NULL, NULL, 'add');
		URI::redirect($url);
	}

	function export() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $form['selector'];
		if ('csv' == $type) {
			$this->_export_csv($selector, $form);
		}
		elseif ('print' == $type) {
			$this->_export_print($selector, $form);
		}
	}

	private function _export_csv($selector, $form) {
        $con_valid_columns = Config::get('eq_sample.export_columns.eq_sample');
		$valid_columns = Event::trigger('eq_sample.extra.export_columns', $con_valid_columns,$form) ? : $con_valid_columns;
        $visible_columns = (array)$form['columns'];
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$samples = Q($selector);
		$csv = new CSV('php://output', 'w');
		$csv->write(I18N::T('eq_sample',$valid_columns));
		if ($samples->total_count()) {
			foreach ($samples as $sample) {
				$equipment = $sample->equipment;
				$data = [];
				if (array_key_exists('equipment', $valid_columns)) {
                    $data[] = $equipment->name;
                }
                if (array_key_exists('eq_ref_no', $valid_columns)) {
                    $data[] = $equipment->ref_no;
                }
                if (array_key_exists('eq_cf_id', $valid_columns)) {
                    $data[] = $equipment->id;
                }
                if (array_key_exists('eq_group', $valid_columns)) {
                   $data[] = $equipment->group->name;
                }
                if (array_key_exists('user', $valid_columns)) {
                    $data[] = $sample->sender->name;
                }
                if (array_key_exists('lab', $valid_columns)) {
                    $data[] = Q("$sample project lab")->current()->name ? : Q("{$sample->sender} lab")->current()->name;
                }
                if (array_key_exists('user_group', $valid_columns)) {
                    $data[] = $sample->sender->group->name;
                }
                if (array_key_exists('sample_ref_no', $valid_columns)) {
                    $data[] = Number::fill($sample->id,6);
                }
                if (array_key_exists('dtsubmit', $valid_columns)) {
                    $data[] = $sample->dtsubmit ? Date::format($sample->dtsubmit, 'Y/m/d H:i:s') : '--';
                }
                if (array_key_exists('dtstart', $valid_columns)) {
                    $data[] = $sample->dtstart ? Date::format($sample->dtstart, 'Y/m/d H:i:s') : '--';
                }
                if (array_key_exists('dtend', $valid_columns)) {
                    $data[] = $sample->dtend ? Date::format($sample->dtend, 'Y/m/d H:i:s') : '--';
                }
                if (array_key_exists('dtpickup', $valid_columns)) {
                    $data[] = $sample->dtpickup ? Date::format($sample->dtpickup, 'Y/m/d H:i:s') : '--';
                }
                if (array_key_exists('status', $valid_columns)) {
                    $data[] = (string) V('eq_sample:incharge/print/status', ['sample'=> $sample]) ? : '--';
                }
                if (array_key_exists('samples', $valid_columns)) {
                    $data[] = $sample->count;
                }
                if (array_key_exists('success_samples', $valid_columns)) {
                    $data[] = ($sample->status == EQ_Sample_Model::STATUS_TESTED) ? $sample->success_samples : '--';
                }
                if (array_key_exists('handlers', $valid_columns)) {
                    $data[] = $sample->operator->name;
                }
                if (array_key_exists('amount', $valid_columns)) {
                    $charge = O('eq_charge', ['source'=> $sample]);
                    if ($charge->id && $charge->amount) {
                        $val = $charge->amount;
                    }
                    else {
                        $val = '--';
                    }
                    $data[] = $val;
                }
                if (array_key_exists('info', $valid_columns)) {
                    $data[] = $sample->description ? $sample->description : '--';
                }
                if (array_key_exists('note', $valid_columns)) {
                    $data[] = $sample->note ? $sample->note : '--';
                }

				$csv->write($data);
		    }
        }
		$csv->close();
	}

	private function _export_print($selector, $form) {
        $valid_columns = Config::get('eq_sample.export_columns.eq_sample');
        $valid_columns = new ArrayIterator($valid_columns);
        $valid_columns = Event::trigger('eq_sample.extra.export_columns', $valid_columns,$form) ?: $valid_columns;
		$visible_columns = Input::form('columns');

        $valid_columns = (array) $valid_columns;
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$samples = Q($selector);
		$this->layout = V('eq_sample:incharge/sample_print', ['form' => $form, 'samples' => $samples,'valid_columns'=>$valid_columns]);
	}

    public function sample_print($id = 0) {
        $sample = O('eq_sample', $id);
        if (!$sample->id) return;
        $this->layout = V('eq_sample:sample_print', [
            'sample'=> $sample
        ]);
    }
}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_output_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
        $columns = Config::get('eq_sample.export_columns.eq_sample');
        $columns = new ArrayIterator($columns);
        $columns = Event::trigger('eq_sample.extra.export_columns', $columns,$form) ?: $columns;
		switch ($type) {
			case 'csv':
				$title = I18N::T('eq_sample', '请选择要导出Excel的列');
				break;
			case 'print':
				$title = I18N::T('eq_sample', '请选择要打印的列');
				break;
		}
		JS::dialog(V('eq_sample:report/output_form', [
						  'form_token' => $form_token,
						  'columns' => $columns,
						  'type' => $type,
					]), [
						'title' => $title
					]);
	}

	function index_add_sample_click() {
        $form = Form::filter(Input::form());
        $equipment = $form['equipment_id'] ? O('equipment', $form['equipment_id']) : O('equipment', $form['id']);
		$me = L('ME');

		if(!$equipment->id) return;

		if(!$me->is_allowed_to('添加送样请求', $equipment)){
            $messages = Lab::messages('sample');
			JS::alert($messages[0]);
			return;
        }

        // check working time
        if (Module::is_installed('eq_sample_time')) {
            if ($form['dtstart'] || $form['dtend']) {
                // 如果设定了时间, 直接提示问题
                $user_in_working_time = EQ_Sample_Time::user_in_working_time($me, $equipment, $form['dtstart'], $form['dtend']);
                if (!$user_in_working_time) {
                    JS::alert(I18N::T('eq_sample_time', '测样时间不能在非工作时段!'));
                    return;
                }
            }
        }

        if ($form['start'] || $form['dtend']) {
            // check overlap
            if (EQ_Sample::check_overlap($equipment, $form['dtstart'], $form['dtend'])) {
                JS::alert(I18N::T('eq_sample', '测样时间和已存在预约冲突，无法预约!'));
                return;
            }
        }

        if ($form['dtstart'] || $form['dtend']) {
            $now = Date::time();
            if ($form['dtstart'] < $now) {
                JS::alert(I18N::T('eq_sample', '测样开始时间必须大于当前时间!'));
                return;
            }

            if ($form['dtsubmit'] > $form['dtstart']) {
                JS::alert(I18N::T('eq_sample', '送样时间不能大于测样开始时间!'));
                return;
            }
        }

        // 保证自定义表单在更换送样者或课题组时表单信息保留
        preg_match_all('/(.+?)=(.*?)(?:&|$)/', $form['form'], $matches);
        foreach ($matches[1] as $key => $value) {
            $form_key = urldecode($value);
            preg_match_all('/^extra_fields\[(.+?)\]$/', $form_key, $m);
            if ($m[0]) {
                $form['extra_fields'][$m[1][0]] = urldecode($matches[2][$key]);
            }
            $form[$form_key] = urldecode($matches[2][$key]);
        }
        unset($form['form']);

        //弹出dialog编辑送样
        JS::dialog(
            V('eq_sample:edit/add',
                [
                    'user' => $me,
                    'form' => $form,
                    'equipment' => $equipment,
                    'message' => $message
                ]
            ),
            ['title'=>I18N::T('eq_sample', '添加申请送样')]
        );
	}


    function index_add_again_click(){
        $form = Input::form();
        $sample = O('eq_sample', $form['id']);
        if(!$sample->id) return;
        $equipment = $sample->equipment;
        $form['count'] = $sample->count;

        $me = L('ME');
        JS::dialog(
            V('eq_sample:edit/add',
                [
                    'user' => $me,
                    'form' => $form,
                    'equipment'=>$equipment,
                    'sample' => $sample
                ]
            ),
            ['title'=>I18N::T('eq_sample', '添加申请送样')]
        );
    }

	function index_add_sample_submit() {
		$form = Form::filter(Input::form());

		$equipment = O('equipment',$form['equipment_id']);
        $now = Date::time();

		$me = L('ME');
		if (!$equipment->id || !$me->is_allowed_to('添加送样请求', $equipment)) {
            $messages = Lab::messages('sample');
            JS::alert($messages[0]);
            return;
		}

		if ($form['submit']) {
			try {
				if (!is_numeric($form['count']) || intval($form['count'])<=0 || intval($form['count'])!=$form['count']) {
					$form->set_error('count',  I18N::T('eq_sample', '样品数 填写有误, 请填写大于0的整数!'));
				}

                if ($form['dtsubmit'] < $now && !Config::get('eq_sample.dtsubmit_allowed_in_the_past', false)) {
                    $form->set_error('dtsubmit',  I18N::T('eq_sample', '送样时间必须大于当前时间!'));
                }

                if ($form['dtstart'] || $form['dtend']) {
                    $now = Date::time();
                    if ($form['dtstart'] < $now) {
                        $form->set_error('dtstart', I18N::T('eq_sample', '测样开始时间必须大于当前时间!'));
                    }
    
                    if ($form['dtsubmit'] > $form['dtstart']) {
                        $form->set_error('dtsubmit', I18N::T('eq_sample', '送样时间不能大于测样开始时间!'));
                    }
                }

				Event::trigger('extra.form.validate', $equipment,'eq_sample', $form);

				$must_connect_project = Config::get('eq_sample.must_connect_lab_project');
				if ( $must_connect_project && !$form['project'] ) {
					$form->set_error('project', I18N::T('eq_sample', '"关联项目" 不能为空!') );
				}

                if (Module::is_installed('nfs')) {
                    if (Event::trigger('nfs.submit_require_file_has_uploaded', 'eq_sample', 0) === false) {
                        $form->set_error('file', I18N::T('announces', '请等待附件上传完成!'));
                    }
                }

				if (!$form->no_error) {
					throw new Error_Exception;
				}

                $sample = O('eq_sample');
                if ($equipment->id) {
					$sample->equipment = $equipment;
				}
				if ($form['count']) {
					$sample->count = (int)max($form['count'], 1);
				}
				if ($form['dtsubmit']) {
					$sample->dtsubmit = $form['dtsubmit'];
				}

				if ($form['description']) {
					$sample->description = $form['description'];
				}

				if ( $form['project'] ) {
					$sample->project = O('lab_project', $form['project']);
				}

				if ($sample->is_locked()) {
					JS::alert(I18N::T('eq_sample', '您设置的时段已被锁定!'));
					return;
				}

                Event::trigger('sample.form.submit', $sample, $form);

				$sample->status = EQ_Sample_Model::STATUS_APPLIED;
                $sample->sender = $me;
                
                if($GLOBALS['preload']['people.multi_lab']){
                    $sample->lab = $form['project_lab'] ? O('lab',$form['project_lab']) : Q("$me lab")->current();
                }else{
                    $sample->lab = $sample->project->lab->id ? $sample->project->lab : Q("$me lab")->current();
                }

                //自定义送样表单存储供lua计算
                if (Module::is_installed('extra')) {
                    $sample->extra_fields = $form['extra_fields'];
                }

				if ($sample->save()) {
					Event::trigger('extra.form.post_submit', $sample, $form);
					/* 记录日志 */
					Log::add(strtr('[eq_sample] %sender_name[%sender_id]申请了%equipment_name[%equipment_id]的送样[%sample_id]', [
                                   '%sender_name' => $sample->sender->name,
                                   '%sender_id' => $sample->sender->id,
                                   '%equipment_name' => $sample->equipment->name,
                                   '%equipment_id' => $sample->equipment->id,
                                   '%sample_id' => $sample->id]), 'journal');
                     JS::dialog(V('eq_sample:edit/confirm_add_again', ['sample'=>$sample]), ['title'=>I18N::T('eq_sample', '继续申请')]);
				}
                else {
                    JS::refresh();
                }
			}
			catch (Error_Exception $e) {
				JS::dialog(V('eq_sample:edit/add', ['equipment'=>$equipment, 'form' => $form]), ['title'=>I18N::T('eq_sample', '添加申请送样')]);
			}
		}
	}

    /*
     * 具有添加送样记录权限的用户进行送样记录添加使用方法
     *
     */
    function index_add_sample_record_click() {
        $form = Input::form();
        // 保证自定义表单在更换送样者或课题组时表单信息保留
        preg_match_all('/(.+?)=(.*?)&/', $form['form'], $matches);
        foreach ($matches[1] as $key => $value) {
            $form_key = urldecode($value);
            preg_match_all('/^extra_fields\[(.+?)\]$/', $form_key, $m);
            if ($m[0]) {
                $form['extra_fields'][$m[1][0]] = urldecode($matches[2][$key]);
            }
            $form[$form_key] = urldecode($matches[2][$key]);
        }
        unset($form['form']);

		$equipment = $form['equipment_id'] ? O('equipment', $form['equipment_id']) : O('equipment', $form['id']);
		$me = L('ME');
		if (!$equipment->id || !$me->is_allowed_to('添加送样记录', $equipment)) return FALSE;

        $sample_status_id = 'sample_status_' . uniqid();
        $extra_content = Event::trigger('eq_sample.get_contents[add_sample_dialog]', $sample_status_id, $equipment, 'eq_sample');
        $view = V('eq_sample:edit/add_sample_record',[
            'user' => $form['sender'] ? O('user', $form['sender']) : $me,
            'form' => $form,
            'status' => $equipment->status,
            'equipment' => $equipment,
            'extra_content' => $extra_content,
            'sample_status_id' => $sample_status_id,
        ]);

		JS::dialog($view, ['title'=>I18N::T('eq_sample', '添加送样记录')]);
    }

    function index_add_sample_record_again_click(){
        $form = Input::form();
        $sample = O('eq_sample', $form['id']);

        if(!$sample->id) return;
        $equipment = $sample->equipment;
        $form['count'] = $sample->count;

        $sample_status_id = 'sample_status_' . uniqid();
        $extra_content = Event::trigger('eq_sample.get_contents[add_sample_dialog]', $sample_status_id, $equipment, $sample);

        $view = V('eq_sample:edit/add_sample_record',[
            'user' => $sample->sender,
            'form' => $form,
            'status' => $equipment->status,
            'equipment' => $equipment,
            'extra_content' => $extra_content,
            'sample_status_id' => $sample_status_id,
            'sample' => $sample
        ]);

        JS::dialog($view, ['title'=>I18N::T('eq_sample', '添加送样记录')]);
    }

    function index_add_sample_record_submit() {
        $form = Form::filter(Input::form());

        $equipment = O('equipment', $form['equipment_id']);
        $me = L('ME');

        if (!$equipment->id || !$me->is_allowed_to('添加送样记录', $equipment)) return FALSE;
        $sample = O('sample');

        Event::trigger('sample.form.submit', $sample, $form);

        try {
            
            //验证是否为整数
			if (!is_numeric($form['count']) || intval($form['count'])<=0 || intval($form['count'])!=$form['count']) {
				if (!Event::trigger('sample.count.save_except_validate', $equipment)) {
                    $form->set_error('count',  I18N::T('eq_sample', '样品数 填写有误, 请填写大于0的整数!'));
                }
			}
            
            Event::trigger('extra.form.validate', $equipment,'eq_sample', $form);
            
            if($form['status'] == EQ_Sample_Model::STATUS_TESTED){
                if(intval($form['success_samples'])<0 || intval($form['success_samples'])!=$form['success_samples']) {
                    $form->set_error('success_samples',  I18N::T('eq_sample', '测样成功数填写有误, 请重新填写!'));
				}
                if(Config::get('equipment.success_samples_incident_from_samples', true) && $form['success_samples'] > $form['count']){
                    $form->set_error('success_samples',  I18N::T('eq_sample', '测样成功数须小于样品数!'));
				}
			}
            
			$must_connect_project = Config::get('eq_sample.must_connect_lab_project');
			if ( $must_connect_project && !$form['project'] ) {
                $form->set_error('project', I18N::T('eq_sample', '关联项目 不能为空!') );
			}
            if ($GLOBALS['preload']['people.multi_lab'] && !$form['project_lab']) {
                $form->set_error('project_lab', I18N::T('eq_sample', '"实验室" 不能为空!') );
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '"实验室" 不能为空!'));
            }

            if ($form['status'] == EQ_Sample_Model::STATUS_TESTED && Config::get('eq_sample.sample_time_required', true)) {
                if (!$form['dtstart']) {
                    $form->set_error('dtstart', I18N::T('eq_sample', '测样开始时间不能为空!'));
                }
            }
            
            //如果进行测样设定
            if ($form['dtrial_check'] == 'on') {
                if ($form['dtend'] < $form['dtstart']) {
                    $form->set_error('dtend', I18N::T('eq_sample', '截止时间不能小于开始时间!'));
                }
                
                if ($form['dtend'] == $form['dtstart']) {
                    $form->set_error('dtend', I18N::T('eq_sample', '测样起止时间不能相同!'));
                }
            }

            if (Module::is_installed('nfs')) {
                if (Event::trigger('nfs.submit_require_file_has_uploaded', 'eq_sample', 0) === false) {
                    $form->set_error('file', I18N::T('announces', '请等待附件上传完成!'));
                }
            }

        	if ($form['user_option'] == 'new_user') {
                
                $form->validate('user_name', 'not_empty', I18N::T('eq_sample', '用户姓名 不能为空!'));
                
                if (!$form['user_email']) {
                    $form->set_error('user_email', I18N::T('eq_sample', '电子邮箱 不能为空!'));
                }
                else {
                    $form->validate('user_email', 'is_email', I18N::T('eq_sample', '电子邮箱 填写有误!'));
                    
                    //如果user_email都没错
                    //比对是否系统中包含
                    if (!count($form->errors['user_email'])) {
                        //系统中存在已有该user_email的用户了
                        if (O('user', ['email'=> trim($form['user_email'])])->id) {
                            $form->set_error('user_email', I18N::T('eq_sample', '电子邮箱 已存在!'));
                        }
                    }
                }
                
                $form
                ->validate('phone', 'not_empty', I18N::T('eq_sample', '联系电话 不能为空!'))
                ->validate('user_org', 'not_empty', I18N::T('eq_sample', '单位名称 不能为空!'));
                
                if (Config::get('people.temp_user.tax_no.required', FALSE)) {
                    $form->validate('tax_no', 'not_empty', I18N::T('eq_sample', '税务登记号 不能为空!'));
                }
                
				if (!$form->no_error) throw new Error_Exception;
                
                $sender = O('user');
                $sender->creator = $me;
                $sender->ref_no = NULL;
                $sender->name = $form['user_name'];
                $sender->email = $form['user_email'];
                $sender->organization = $form['user_org'];
                $sender->tax_no = $form['tax_no'];
                $sender->phone = $form['phone'];
                
                Event::trigger('equipments.record.create_user_before_save', $sender, $form);
                
				$sender->save();
                
                if (!$sender->id) $form->set_error('user_name', I18N::T('eq_sample', '用户添加失败'));
                $sender->connect(Equipments::default_lab());
                Event::trigger('eq_sample.tmpuser_register',$sender,$form);
            }
            else{
                $sender = O('user', $form['sender']);
				if (!$sender->id) $form->set_error('sender', I18N::T('eq_sample', '申请人不能为空!'));
            }
            
            if (!$form->no_error) {
                throw new Error_Exception;
            }
            
            $sample = O('eq_sample');
            
            $sample->sender = $sender;

            if ($equipment->id) {
				$sample->equipment = $equipment;
            }

			if ($form['count']) {
				$sample->count = (int) max($form['count'], 1);
            }

            if ( $form['project'] ) {
	            $sample->project = O('lab_project', $form[project]);
            }

			if ($form['dtsubmit']) {
				$sample->dtsubmit = $form['dtsubmit'];
			}

            if ($form['dtrial_check'] == 'on') {
                $sample->dtstart = $form['dtstart'];
                $sample->dtend = $form['dtend'];
            }
            else {
                $sample->dtstart = 0;
                $sample->dtend = 0;
            }

            if ($form['dtpickup_check'] == 'on') {
                $sample->dtpickup = $form['dtpickup'];
            }
            else {
                $sample->dtpickup = 0;
            }

			if ($form['description']) {
				$sample->description = $form['description'];
            }

            if ($form['note']) {
                $sample->note = $form['note'];
            }

            if($form['status'] == EQ_Sample_Model::STATUS_TESTED){
				$sample->success_samples = (int)max($form['success_samples'], 0);
			}

            if ($sample->is_locked()) {
            	JS::alert(I18N::T('eq_sample', '您设置的时段已被锁定!'));
            	return;
            }
            Event::trigger('sample.form.submit', $sample, $form);
            
			$sample->status = $form['status'];
            $sample->operator = $me;

            if($GLOBALS['preload']['people.multi_lab']){
                $sample->lab = $form['project_lab'] ? O('lab',$form['project_lab']) : Q("$sender lab")->current();
            }else{
                $sample->lab = $sample->project->lab->id ? $sample->project->lab : Q("$sender lab")->current();
            }

            //自定义送样表单存储供lua计算
            if (Module::is_installed('extra')) {
                $sample->extra_fields = $form['extra_fields'];
            }

            if ($sample->save()) {
            	Event::trigger('extra.form.post_submit', $sample, $form);

				/* 记录日志TODO */
				Log::add(strtr('[eq_sample] %user_name[%user_id]申请了%equipment_name[%equipment_id]的送样[%sample_id]', [
                               '%user_name' => $sample->sender->name,
                               '%user_id' => $sample->sender->id,
                               '%equipment_name' => $sample->equipment->name,
                               '%equipment_id' => $sample->equipment->id,
                               '%sample_id' => $sample->id]), 'journal');

                JS::dialog(V('eq_sample:edit/confirm_add_again', ['sample'=>$sample]), ['title'=>I18N::T('eq_sample', '继续申请')]);
			}
            else {
                JS::refresh();
            }
        }
        catch(Error_Exception $e) {
            $sample_status_id = 'sample_status_' . uniqid();
            $extra_content = Event::trigger('eq_sample.get_contents[add_sample_dialog]', $sample_status_id, $equipment, $sample);
            JS::dialog(V('eq_sample:edit/add_sample_record', [
                'user' => $form['sender'] ? O('user', $form['sender']) : $me,
                'equipment' => $equipment, 
                'form' => $form,
                'sample_status_id' => $sample_status_id,
                'extra_content' => $extra_content
            ]), ['title'=>I18N::T('eq_sample', '添加送样记录')]);
        }
    }

    /*
     *    guoping.zhang@2010.12.04
     *    送样计费
     *
     */
	function index_edit_sample_click() {
		$form = Form::filter(Input::form());
        preg_match_all('/(.+?)=(.*?)&/', $form['form'], $matches);
        foreach ($matches[1] as $key => $value) {
            $form[$value] = urldecode($matches[2][$key]);
        }
        unset($form['form']);
		$sample = O('eq_sample', $form['id']);
		$me = L('ME');
		$is_general = $me->id == $sample->sender->id && $sample->status == EQ_Sample_Model::STATUS_APPLIED;
		if (!$sample->id || !($me->is_allowed_to('管理', $sample) || $is_general)) return;

        $sample_status_id = 'sample_status_' . uniqid();
        $extra_content = Event::trigger('eq_sample.get_contents[edit_sample_dialog]', $sample_status_id, $sample->equipment, $sample);

        //弹出dialog编辑送样
		JS::dialog(V('eq_sample:edit/edit', [
            'user' => $form['sender'] ? O('user', $form['sender']) : $sample->sender,
            'lab' => $form['lab'] ? O('lab', $form['lab']) : $sample->project->lab,
            'sample' => $sample,
            'sample_status_id' => $sample_status_id, 
            'extra_content' => $extra_content, 
            'message' => $message, 
            'form' => $form]
        ), ['title'=>I18N::T('eq_sample', '编辑送样记录')]);
	}

	//送样计费提交功能
	function index_edit_sample_submit() {
		$form = Form::filter(Input::form());
		$sample = O('eq_sample', $form['id']);
        $equipment = $sample->equipment;
        $now = Date::time();
		$me = L('ME');
		/*
		NO.BUG#344(guoping.zhang@2011.01.27)
		1.用户有【修改所有仪器的送样】权限可编辑送样
		2.用户有【修改负责仪器的送样】权限且时仪器负责人可编辑送样
		3.用户为送样申请者且送样状态为申请中时可编辑自己的送样!$sample->id
		*/
		$is_admin = $me->is_allowed_to('管理', $sample);
		$is_general = $me->id == $sample->sender->id && $sample->status == EQ_Sample_Model::STATUS_APPLIED;
		if (!$sample->id || !($is_admin || $is_general)) return;
		if ($form['submit']) {
			try {

                Event::trigger('extra.form.validate', $equipment,'eq_sample', $form);

                //验证是否为整数
                if (!is_numeric($form['count']) || intval($form['count'])<=0 || intval($form['count'])!=$form['count']) {
                    if (!Event::trigger('sample.count.save_except_validate', $equipment)) {
                        $form->set_error('count',  I18N::T('eq_sample', '样品数 填写有误, 请填写大于0的整数!'));
                    }
                }

                if (!$is_admin && $form['dtsubmit'] < $now && !Config::get('eq_sample.dtsubmit_allowed_in_the_past', false)) {
                    $form->set_error('dtsubmit',  I18N::T('eq_sample', '送样时间必须大于当前时间!'));
                }

                if($form['status'] == EQ_Sample_Model::STATUS_TESTED){
                    if(intval($form['success_samples'])<0 || intval($form['success_samples'])!=$form['success_samples']) {
                        $form->set_error('success_samples',  I18N::T('eq_sample', '测样成功数填写有误, 请重新填写!'));
                    }

				    if(Config::get('equipment.success_samples_incident_from_samples', true) && $form['success_samples'] > $form['count']){
                        $form->set_error('success_samples',  I18N::T('eq_sample', '测样成功数须小于样品数!'));
                    }

                    $has_records = Q("$sample eq_record")->total_count();
                    if (!$has_records && $form['dtrial_check'] != 'on' && Config::get('eq_sample.sample_time_required', true)) {
                        $form->set_error('dtstart', I18N::T('eq_sample', '测样开始时间不能为空!'));
                    }
                }

                //如果进行测样设定
                if ($form['dtrial_check'] == 'on') {
                    if ($form['dtend'] < $form['dtstart']) {
                        $form->set_error('dtend', I18N::T('eq_sample', '截止时间不能小于开始时间!'));
                    }

                    if ($form['dtend'] == $form['dtstart']) {
                        $form->set_error('dtend', I18N::T('eq_sample', '测样起止时间不能相同!'));
                    }
                }

                $must_connect_project = Config::get('eq_sample.must_connect_lab_project');
                if ($must_connect_project && $GLOBALS['preload']['people.multi_lab'] && !$form['project_lab']) {
                    $form->set_error('project_lab', I18N::T('eq_sample', '该送样申请必须关联实验室, 请关联实验室!') );
                }
                if ($must_connect_project && !$form['project']) {
                    $form->set_error('project', I18N::T('eq_sample', '该送样申请必须关联项目, 请关联项目!') );
                }

                if ($form['user_option'] == 'new_user') {

                    $form->validate('user_name', 'not_empty', I18N::T('equipments', '用户姓名 不能为空!'));

                    if (!$form['user_email']) {
                        $form->set_error('user_email', '电子邮箱 不能为空!');
                    }
                    else {
                        $form->validate('user_email', 'is_email', I18N::T('equipments', '电子邮箱 填写有误!'));

                        //如果user_email都没错
                        //比对是否系统中包含
                        if (!count($form->errors['user_email'])) {
                            //系统中存在已有该user_email的用户了
                            if (O('user', ['email'=> trim($form['user_email'])])->id) {
                                $form->set_error('user_email', I18N::T('equipments', '电子邮箱 已存在!'));
                            }
                        }
                    }

                    $form
                        ->validate('phone', 'not_empty', I18N::T('equipments', '联系电话 不能为空!'))
                        ->validate('user_org', 'not_empty', I18N::T('equipments', '单位名称 不能为空!'));

                    if (Config::get('people.temp_user.tax_no.required', FALSE)) {
                        $form->validate('tax_no', 'not_empty', I18N::T('equipments', '税务登记号 不能为空!'));
                    }
                    Event::trigger('signup.save_extra_field_validate', $form);

                    if ($form->no_error) {

                        $sender = O('user');
                        $sender->creator = $me;
                        $sender->ref_no = NULL;
                        $sender->name = $form['user_name'];
                        $sender->email = $form['user_email'];
                        $sender->organization = $form['user_org'];
                        $sender->tax_no = $form['tax_no'];
                        $sender->phone = $form['phone'];
                        
                        Event::trigger('signup.save_extra_field', $sender, $form);
                        
                        $sender->save();
                        if (!$sender->id) {
                            $form->set_error('user_name', I18N::T('eq_sample', '用户添加失败'));
                            throw new Error_Exception;
                        }
                        $sender->connect(Equipments::default_lab());
                        Event::trigger('eq_sample.tmpuser_register',$sender,$form);
                    }
                }
                else {
                    if ($is_admin) {
                        $sender = O('user', $form['sender']);
                    }
                    elseif ($is_general) {
                        $sender = $me;
                    }

                    if (!$sender->id) {
                        $form->set_error('sender', I18N::T('eq_sample', '申请人不能为空!'));
                    }
                }

                if (!$form->no_error) {
                    throw new Error_Exception;
                }

                if (isset($form['project'])) {
		            $sample->project = O('lab_project', $form['project']);
	            }

                if($GLOBALS['preload']['people.multi_lab']){
                    $sample->lab = $form['project_lab'] ? O('lab',$form['project_lab']) : Q("$sender lab")->current();
                }else{
                    $sample->lab = $sample->project->lab->id ? $sample->project->lab : Q("$sender lab")->current();
                } 

                $sample->sender = $sender;

                $sample->dtsubmit = $form['dtsubmit'] ? : $sample->dtsubmit;

                if ($form['dtrial_check'] == 'on') {
                    $sample->dtstart = $form['dtstart'];
                    $sample->dtend = $form['dtend'];
                }
                else {
                    $sample->dtstart = 0;
                    $sample->dtend = 0;
                }

                if ($form['dtpickup_check'] == 'on') {
                    $sample->dtpickup = $form['dtpickup'];
                }
                else {
                    $sample->dtpickup = 0;
                }

				if ($sample->is_locked()) {
					JS::alert(I18N::T('eq_sample', '您设置的时段已被锁定!'));
					return;
				}

				if($form['status'] == EQ_Sample_Model::STATUS_TESTED){
					$sample->success_samples = (int)max($form['success_samples'], 0);
				}

				//用户修改自己的sample时，不会修改仪器的状态，则sample的operator不应修改，就应该为空
				//BUG #1070::仪器送样申请后如果编辑自己的送样申请，操作人则会变为自己

                //管理员进行sample信息修改, 就会设定操作者
                if ($me->is_allowed_to('修改', $sample)) $sample->operator = $me;

				$sample->count = (int)max($form['count'], 1);
				$sample->description = $me->id == $sample->sender->id ? $form['description'] : $sample->description;

				Event::trigger('sample.form.submit', $sample, $form);

				//系统管理员/本仪器负责人编辑送样请求
				if ($is_admin) {
					$sample->status = $form['status'];
					$sample->note = $form['note'];

					/*
					  除"申请中"，都可设置取样时间
					  (xiaopei.li@2011.09.06)
					*/
					if ($sample->status == EQ_Sample_Model::STATUS_APPLIED) {
						$sample->dtpickup = 0;
					}
				}

                $moniter_array = [
                    'sender',
                    'dtsubmit',
                    'dtpickup',
                    'count',
                    'status'
                ];

                //未进行修改
                $edit_flag = FALSE;

                //比对是否进行了修改
                foreach ($moniter_array as $key) {
                    if (is_object($sample->$key)) {
                        if ($sample->get($key, TRUE)-> id != $sample->$key->id) {
                            $edit_flag = TRUE;
                            break;
                        }
                    }
                    else {
                        if ($sample->get($key, TRUE) != $sample->$key) {
                            $edit_flag = TRUE;
                            break;
                        }
                    }
                }

                if ($edit_flag) {
                    $new_sender = $sample->sender;
                    $old_sender = $sample->get('sender', TRUE);

                    $new_pi = $sample->lab->owner;
                    $old_pi = $sample->get('lab', TRUE)->owner;

                    $old_count = $sample->get('count', TRUE);
                    $new_count = $sample->count;

                    $old_dtsubmit = $sample->get('dtsubmit', TRUE);
                    $new_dtsubmit = $sample->dtsubmit;

                    $old_dtpickup = $sample->get('dtpickup', TRUE);
                    $new_dtpickup = $sample->dtpickup;

                    $old_status = $sample->get('status', TRUE);
                    $new_status = $sample->status;

                    $old_note = $sample->get('note', TRUE);
                    $new_note = $sample->note;

                    Notification::send('eq_sample.edit_sample.sender', $old_sender, [
                        '%eq_name'=>Markup::encode_Q($equipment),
                        '%id'=> Number::fill($sample->id),
                        '%time'=>Date::format($now, 'Y/m/d H:i:s'),
                        '%user'=>Markup::encode_Q($me),
                        '%old_sender'=> Markup::encode_Q($old_sender),
                        '%old_count'=>$old_count,
                        '%old_dtsubmit'=> Date::format($old_dtsubmit, 'Y/m/d H:i:s'),
                        '%old_dtpickup'=>$old_dtpickup ? Date::format($old_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                        '%old_status'=> I18N::T('eq_sample', Event::trigger('sample.status')[$old_status]),
                        '%old_note' => $old_note ?: '无',
                        '%new_sender'=> Markup::encode_Q($new_sender),
                        '%new_count'=>$new_count,
                        '%new_dtsubmit'=> Date::format($new_dtsubmit, 'Y/m/d H:i:s'),
                        '%new_dtpickup'=>$new_dtpickup ? Date::format($new_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                        '%new_status'=>I18N::T('eq_sample', Event::trigger('sample.status')[$new_status]),
                        '%new_note' => $new_note ?: '无'
                    ]);

                    Notification::send('eq_sample.edit_sample.pi', $old_pi, [
                        '%eq_name'=>Markup::encode_Q($equipment),
                        '%id'=> Number::fill($sample->id),
                        '%time'=>Date::format($now, 'Y/m/d H:i:s'),
                        '%user'=> Markup::encode_Q($me),
                        '%old_sender'=>Markup::encode_Q($old_sender),
                        '%old_count'=>$old_count,
                        '%old_dtsubmit'=> Date::format($old_dtsubmit, 'Y/m/d H:i:s'),
                        '%old_dtpickup'=>$old_dtpickup ? Date::format($old_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                        '%old_status'=> I18N::T('eq_sample', Event::trigger('sample.status')[$old_status]),
                        '%old_note' => $old_note ?: '无',
                        '%new_sender'=>Markup::encode_Q($new_sender),
                        '%new_count'=>$new_count,
                        '%new_dtsubmit'=> Date::format($new_dtsubmit, 'Y/m/d H:i:s'),
                        '%new_dtpickup'=>$new_dtpickup ? Date::format($new_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                        '%new_status'=> I18N::T('eq_sample', Event::trigger('sample.status')[$new_status]),
                        '%new_note' => $new_note ?: '无'
                    ]);

                    if ($new_sender->id != $old_sender->id) {

                        Notification::send('eq_sample.edit_sample.sender', $new_sender, [
                            '%eq_name'=>Markup::encode_Q($equipment),
                            '%id'=> Number::fill($sample->id),
                            '%time'=>Date::format($now, 'Y/m/d H:i:s'),
                            '%user'=> Markup::encode_Q($me),
                            '%old_sender'=>Markup::encode_Q($old_sender),
                            '%old_count'=>$old_count,
                            '%old_dtsubmit'=> Date::format($old_dtsubmit, 'Y/m/d H:i:s'),
                            '%old_dtpickup'=>$old_dtpickup ? Date::format($old_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                            '%old_status'=> I18N::T('eq_sample', Event::trigger('sample.status')[$old_status]),
                            '%old_note' => $old_note ?: '无',
                            '%new_sender'=>Markup::encode_Q($new_sender),
                            '%new_count'=>$new_count,
                            '%new_dtsubmit'=> Date::format($new_dtsubmit, 'Y/m/d H:i:s'),
                            '%new_dtpickup'=>$new_dtpickup ? Date::format($new_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                            '%new_status'=> I18N::T('eq_sample', Event::trigger('sample.status')[$new_status]),
                            '%new_note' => $new_note ?: '无'
                        ]);

                        if ($sample->lab->id != $sample->get('lab', TRUE)->lab->id) {

                            Notification::send('eq_sample.edit_sample.pi', $new_pi, [
                                '%eq_name'=>Markup::encode_Q($equipment),
                                '%id'=> Number::fill($sample->id),
                                '%time'=>Date::format($now, 'Y/m/d H:i:s'),
                                '%user'=> Markup::encode_Q($me),
                                '%old_sender'=>Markup::encode_Q($old_sender),
                                '%old_count'=>$old_count,
                                '%old_dtsubmit'=> Date::format($old_dtsubmit, 'Y/m/d H:i:s'),
                                '%old_dtpickup'=>$old_dtpickup ? Date::format($old_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                                '%old_status'=> I18N::T('eq_sample', Event::trigger('sample.status')[$old_status]),
                                '%old_note' => $old_note ?: '无',
                                '%new_sender'=>Markup::encode_Q($new_sender),
                                '%new_count'=>$new_count,
                                '%new_dtsubmit'=> Date::format($new_dtsubmit, 'Y/m/d H:i:s'),
                                '%new_dtpickup'=>$new_dtpickup ? Date::format($new_dtpickup, 'Y/m/d H:i:s') : I18N::T('eq_sample', '未设取样'),
                                '%new_status'=> I18N::T('eq_sample', Event::trigger('sample.status')[$new_status]),
                                '%new_note' => $new_note ?: '无'
                        ]);
                        }
                    }
                }
                
                //自定义送样表单存储供lua计算
                if (Module::is_installed('extra')) {
                    $sample->extra_fields = $form['extra_fields'];
                }
				if ($sample->save()) {
					Event::trigger('extra.form.post_submit', $sample, $form);

					/* 记录日志 */
					Log::add(strtr('[eq_sample] %user_name[%user_id]修改了%equipment_name[%equipment_id]的送样[%sample_id]', [
                                   '%user_name' => $me->name,
                                   '%user_id' => $me->id,
                                   '%equipment_name' => $sample->equipment->name,
                                   '%equipment_id' => $sample->equipment->id,
                                   '%sample_id' => $sample->id]), 'journal');
				}

				JS::close_dialog();
				JS::refresh();
			}
			catch (Error_Exception $e) {
                $sample_status_id = 'sample_status_' . uniqid();
                $extra_content = Event::trigger('eq_sample.get_contents[edit_sample_dialog]', $sample_status_id, $sample->equipment, $sample);
		        JS::dialog(V('eq_sample:edit/edit', [
                    'user' => $sample->sender,
                    'sample' => $sample,
                    'sample_status_id' => $sample_status_id, 
                    'extra_content' => $extra_content, 
                    'message' => $message, 
                    'form' => $form
                ]), ['title'=>I18N::T('eq_sample', '编辑送样记录')]);
			}
		}
	}

	function index_delete_sample_click() {
		$form = Form::filter(Input::form());
		$sample = O('eq_sample', $form['id']);
		$me = L('ME');
        $equipment = $sample->equipment;
        $sender = $sample->sender;
        $now = Date::time();
		$is_admin = $me->is_allowed_to('修改', $sample);
		$is_general = ($me->id == $sample->sender->id) && ($sample->status == EQ_Sample_Model::STATUS_APPLIED);
		if (!$sample->id || !($is_admin || $is_general)) return;

		$confirm = JS::confirm(I18N::T('eq_sample', '您确认删除该条送样记录吗？'));
		if (!$confirm) return;

		$sample_attachments_dir_path = NFS::get_path($sample, '', 'attachments', TRUE);

		if ($sample->delete()) {
			/* 记录日志 */
			Log::add(strtr('[eq_sample] %user_name[%user_id]删除了%equipment_name[%equipment_id]的送样[%sample_id]', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%equipment_name' => $sample->equipment->name,
                        '%equipment_id' => $sample->equipment->id,
                        '%sample_id'=> $sample->id]), 'journal');
			File::rmdir($sample_attachments_dir_path);

            //sender
            Notification::send('eq_sample.delete_sample.sender', $sender, [
                '%eq_name'=> Markup::encode_Q($equipment),
                '%id'=> Number::fill($sample->id),
                '%time'=>Date::format($now, 'Y/m/d H:i:s'),
                '%user'=> Markup::encode_Q($me)
            ]);

            // incharge
            $incharges = Q("{$equipment} user.incharge");
            foreach ($incharges as $incharge) {
                Notification::send('eq_sample.delete_sample.eq_contact', $incharge, [
                    '%sender' =>Markup::encode_Q($sender),
                    '%eq_name'=> Markup::encode_Q($equipment),
                    '%id'=> Number::fill($sample->id),
                    '%time'=>Date::format($now, 'Y/m/d H:i:s'),
                    '%user'=> Markup::encode_Q($me)
                ]);
            }

            //pi
            Notification::send('eq_sample.delete_sample.pi', $sample->lab->owner, [
                '%sender' =>Markup::encode_Q($sender),
                '%eq_name'=>Markup::encode_Q($equipment),
                '%id'=>Number::fill($sample->id),
                '%time'=>Date::format($now, 'Y/m/d H:i:s'),
                '%user'=>Markup::encode_Q($me)
            ]);
		}

		JS::refresh();
	}

    //calendar中preview
    function index_preview_click() {
        $form = Input::form();
        $sample = O('eq_sample', $form['sid']);
        if (!$sample->id) return FALSE;

        Output::$AJAX['preview'] = (string) V('eq_sample:preview', ['sample' => $sample]);
    }

    function index_sample_attachement_change() {
    	$form = Input::form();
    	$sample = O('eq_sample', $form['id']);
    	$me = L('ME');
    	if( !$me->is_allowed_to('下载文件', $sample, ['type' => 'attachments']) || !$sample->id ) {
    		Output::$AJAX['attachments'] = '';
    		return;
    	}

    	Output::$AJAX['sample_view'] = (string)V('eq_sample:samples_table/data/view', ['sample'=>$sample]);
        Output::$AJAX['sample_id'] = $sample->id;

    }

    function index_send_report_click() {
    	$form = Input::form();
    	$sample = O('eq_sample', $form['id']);
    	if(!$sample->id) return;
    	JS::dialog(V('eq_sample:mail/receiver', ['sample' => $sample]), ['title'=>I18N::T('eq_sample', '发送报告')]);
    }

    function index_send_report_submit() {
    	$form = Form::filter(Input::form());
    	$sample = O('eq_sample', $form['id']);
    	if(!$sample->id) return;

        $form->validate('receiver', 'not_empty', I18N::T('eq_sample', '电子邮箱不能为空！'));
        if($form->no_error){
            $form->validate('receiver', 'is_email', I18N::T('eq_sample', '电子邮箱输入有误！'));
        }

        if($form->no_error){
            $extra = Extra_Model::fetch($sample->equipment, 'eq_sample');
            $extra_value = O('extra_value', ['object'=>$sample]);

            $view = (string)V('eq_sample:mail/report', ['sample'=>$sample, 'receiver'=>$form['receiver'], 'extra'=>$extra, 'extra_value'=>$extra_value]);
            $view = wordwrap($view, 900, "\n");
            $mail = new Email(L('ME'));
            $mail->to($form['receiver']);
            $mail->subject(I18N::T('eq_sample', '送样检测报告'));
            $mail->body(null, $view);

            $file_path = NFS::get_attachments_path($sample);
            $mail->attachment(glob($file_path. '/*'));

            if($mail->send()){
                JS::alert(I18N::T('eq_sample', '发送成功'));
                JS::close_dialog();
            }
            else{
                JS::alert(I18N::T('eq_sample', '发送失败'));
            }
        }
        else{
            JS::dialog(V('eq_sample:mail/receiver', ['sample' => $sample, 'form'=>$form]), ['title'=>I18N::T('eq_sample', '发送报告')]);
        }
    }

	function index_sample_export_submit() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '操作超时, 请重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $selector = $form['selector'];

		if ('csv' == $type) {
			$pid = $this->_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('calendars', '导出等待')
            ]);
		}
    }

    function index_download_attachments_click() {
        $sample = O('eq_sample', Input::form('id'));
        if (!$sample->id) return;

        JS::dialog(V('eq_sample:sample_attachments', [
            'sample' => $sample
        ]), ['width' => 150]);
    }

	private function _export_csv($selector, $form, $file_name) {
		$me = L('ME');
        $valid_columns = Config::get('eq_sample.export_columns.eq_sample');
        $valid_columns = new ArrayIterator($valid_columns);
        $valid_columns = Event::trigger('eq_sample.extra.export_columns', $valid_columns,$form) ?: $valid_columns;
		$visible_columns = (array)$form['columns'];

		$valid_columns = (array)$valid_columns;
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}
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
		//if ($samples->total_count()) {
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_sample export ';
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
        //}
	}

    function index_lock_click($id=0) {
        $lock_no_confirm = Config::get('eq_sample.lock_no_confirm', FALSE);//是否关闭提示
        if ($lock_no_confirm || JS::confirm(I18N::T('equipments', '您确定锁定该条送样记录吗?'))) {
            $form = Input::form();
            $sample = O('eq_sample', $form['id']);
            if ($sample->id && L('ME')->is_allowed_to('管理', $sample)) {
                $sample->is_locked = TRUE;
                $sample->save();
                JS::refresh();
            }
        }
    }

    function index_unlock_click($id=0) {
        $lock_no_confirm = Config::get('eq_sample.lock_no_confirm', FALSE);//是否关闭提示
        if ($lock_no_confirm || JS::confirm(I18N::T('equipments', '您确定解锁该条送样记录吗?'))) {
            $form = Input::form();
            $sample = O('eq_sample', $form['id']);
            if ($sample->id && L('ME')->is_allowed_to('管理', $sample)) {
                $sample->is_locked = FALSE;
                $sample->save();
                JS::refresh();
            }
        }
    }
}
