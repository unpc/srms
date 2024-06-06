<?php

class Training_Controller extends Base_Controller
{
    public function me($tab = 'approved'){
        $me = L("ME");
        if(!$me->id){
            URI::redirect('error/401');
        }
        $user = O("user",$me->id);
        
        $this->layout->body->primary_tabs = Widget::factory("tabs");
        $this->layout->body->primary_tabs->user = $user;
        $this->layout->title = null;
        Training::get_list_by_user($this->layout->body->primary_tabs);
    }
    
    public function export_approved()
    {
        $form = Input::form();
        $export_types = ['csv', 'print'];
        $type = $form['type'];
        if (!in_array($type, $export_types)) {
            $type = 'print';
        }

        $selector = $_SESSION[$form['form_token']]['selector'];

        if (!$selector) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            URI::redirect();
            return false;
        }

        $full_columns = Config::get('equipments.export_columns.training.approved');
        $valid_columns = [];

        if (count($form['columns'])) {
            foreach ($form['columns'] as $name=> $value) {
                if ($value == 'on' && array_key_exists($name, $full_columns)) {
                    $valid_columns[$name] = $full_columns[$name];
                }
            }
            $_SESSION[$form_token]['valid_columns'] = $valid_columns;
        } else {
            $valid_columns = $_SESSION[$form_token]['valid_columns'];
        }

        call_user_func([$this, '_export_approved_'. $type], $valid_columns, $selector);
    }

    public function export_overdue()
    {
        $form = Input::form();
        $export_types = ['csv', 'print'];
        $type = $form['type'];
        if (!in_array($type, $export_types)) {
            $type = 'print';
        }

        $selector = $_SESSION[$form['form_token']]['selector'];

        if (!$selector) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            URI::redirect();
            return false;
        }

        $full_columns = Config::get('equipments.export_columns.training.overdue');
        $valid_columns = [];

        if (count($form['columns'])) {
            foreach ($form['columns'] as $name=> $value) {
                if ($value == 'on' && array_key_exists($name, $full_columns)) {
                    $valid_columns[$name] = $full_columns[$name];
                }
            }
            $_SESSION[$form_token]['valid_columns'] = $valid_columns;
        } else {
            $valid_columns = $_SESSION[$form_token]['valid_columns'];
        }

        call_user_func([$this, '_export_overdue_'. $type], $valid_columns, $selector);
    }

    public function export_applied()
    {
        $form = Input::form();
        $export_types = ['csv', 'print'];
        $type = $form['type'];
        if (!in_array($type, $export_types)) {
            $type = 'print';
        }

        $selector = $_SESSION[$form['form_token']]['selector'];

        if (!$selector) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            URI::redirect();
            return false;
        }

        $full_columns = Config::get('equipments.export_columns.training.applied');
        $equipment = O('equipment', $form['equipment_id']);
        if (!$equipment->id && $equipment->control_mode == 'bluetooth') {
            unset($full_columns['check_time']);
        }
        $valid_columns = [];

        if (count($form['columns'])) {
            foreach ($form['columns'] as $name=> $value) {
                if ($value == 'on' && array_key_exists($name, $full_columns)) {
                    $valid_columns[$name] = $full_columns[$name];
                }
            }
            $_SESSION[$form_token]['valid_columns'] = $valid_columns;
        } else {
            $valid_columns = $_SESSION[$form_token]['valid_columns'];
        }

        call_user_func([$this, '_export_applied_'. $type], $valid_columns, $selector);
    }

    public function export_group()
    {
        $form = Input::form();
        $export_types = ['csv', 'print'];
        $type = $form['type'];
        if (!in_array($type, $export_types)) {
            $type = 'print';
        }

        $selector = $_SESSION[$form['form_token']]['selector'];

        if (!$selector) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            URI::redirect();
            return false;
        }

        $full_columns = Config::get('equipments.export_columns.training.group');
        $valid_columns = [];

        if (count($form['columns'])) {
            foreach ($form['columns'] as $name=> $value) {
                if ($value == 'on' && array_key_exists($name, $full_columns)) {
                    $valid_columns[$name] = $full_columns[$name];
                }
            }
            $_SESSION[$form_token]['valid_columns'] = $valid_columns;
        } else {
            $valid_columns = $_SESSION[$form_token]['valid_columns'];
        }

        call_user_func([$this, '_export_group_'. $type], $valid_columns, $selector);
    }

    private function _export_approved_csv($valid_columns, $selector)
    {
        $csv = new CSV('php://output', 'w');
        $csv->write($valid_columns);
        foreach (Q($selector) as $object) {
            $data = [];
            foreach ($valid_columns as $key=> $value) {
                switch ($key) {
                    case 'user':
                        $data[] = $object->user->name;
                        break;
                    case 'lab':
                        $labs = Q("{$object->user} lab")->to_assoc('id', 'name');
                        $data[] = join(', ', $labs);
                        break;
                    case 'group':
                        $data[] = $object->user->group->name;
                        break;
                    case 'phone':
                        $data[] = $object->user->phone;
                        break;
                    case 'email':
                        $data[] = $object->user->email;
                        break;
                    case 'address':
                        $data[] = $object->user->address;
                        break;
                    case 'ctime' :
                        $data[] = $object->ctime ? Date::format($object->ctime,'Y/m/d') : '--';
                        break;
                    case 'atime' :
                        $data[] = $object->atime ? Date::format($object->atime,'Y/m/d') : I18N::T('equipments', '不过期');
                        break;
                }
            }
            $csv->write($data);
        }
        $csv->close();
    }

    private function _export_applied_csv($valid_columns, $selector)
    {
        $csv = new CSV('php://output', 'w');
        $csv->write($valid_columns);
        foreach (Q($selector) as $object) {
            $data = [];
            foreach ($valid_columns as $key=> $value) {
                switch ($key) {
                    case 'user':
                        $data[] = $object->user->name;
                        break;
                    case 'lab':
                        $labs = Q("{$object->user} lab")->to_assoc('id', 'name');
                        $data[] = join(', ', $labs);
                        break;
                    case 'group':
                        $data[] = $object->user->group->name;
                        break;
                    case 'phone':
                        $data[] = $object->user->phone;
                        break;
                    case 'email':
                        $data[] = $object->user->email;
                        break;
                    case 'address':
                        $data[] = $object->user->address;
                        break;
                    case 'ctime' :
                        $data[] = $object->ctime ? Date::format($object->ctime) : '--';
                        break;
                }
            }
            $csv->write($data);
        }
        $csv->close();
    }

    private function _export_group_csv($valid_columns, $selector)
    {
        $csv = new CSV('php://output', 'w');
        $csv->write($valid_columns);
        foreach (Q($selector) as $object) {
            $data = [];
            foreach ($valid_columns as $key=> $value) {
                switch ($key) {
                    case 'user':
                        $data[] = $object->user->name;
                        break;
                    case 'ntotal':
                        $data[] = $object->ntotal;
                        break;
                    case 'napproved':
                        $data[] = $object->napproved;
                        break;
                    case 'date':
                        $data[] = Date::format($object->date);
                        break;
                    case 'description':
                        $data[] = $object->description;
                        break;
                }
            }
            $csv->write($data);
        }
        $csv->close();
    }

    private function _export_approved_print($valid_columns, $selector)
    {
        $this->layout = V('training/print/approved', [
            'valid_columns' => $valid_columns,
            'objects'=> Q($selector),
        ]);
    }

    private function _export_applied_print($valid_columns, $selector)
    {
        $this->layout = V('training/print/applied', [
            'valid_columns' => $valid_columns,
            'objects'=> Q($selector),
        ]);
    }

    private function _export_overdue_print($valid_columns, $selector)
    {
        $this->layout = V('training/print/overdue', [
            'valid_columns' => $valid_columns,
            'objects'=> Q($selector),
        ]);
    }

    private function _export_group_print($valid_columns, $selector)
    {
        $this->layout = V('training/print/group', [
            'valid_columns' => $valid_columns,
            'objects'=> Q($selector),
        ]);
    }

    public function apply_user($id = 0)
    {
        $equipment = O('equipment', $id);
        if (!$equipment->id || !$equipment->require_training
        || $equipment->status==EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            URI::redirect('error/404');
        }

        //申请
        $user = L('ME');
        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_APPROVED,
            UE_Training_Model::STATUS_AGAIN
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");
        if ($trainings->total_count()) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '您已经申请该设备的培训课程!'));
            URI::redirect($equipment->url());
        }

        $status = implode(',', [
            UE_Training_Model::STATUS_REFUSE,
            UE_Training_Model::STATUS_DELETED,
            UE_Training_Model::STATUS_OVERDUE
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");

        $training = O('ue_training');
        $training->user = $user;
        $training->proposer = $user;
        $training->equipment = $equipment;
        $training->status = $trainings->total_count() ? UE_Training_Model::STATUS_AGAIN : UE_Training_Model::STATUS_APPLIED;
        $training->type = $user->member_type;
        $training->save();

        URI::redirect($equipment->url());
    }

    public function reject_user($id = 0, $type = '')
    {
        //拒绝
        $training = O('ue_training', $id);

        if (!$training->id) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '您的操作有误!'));
        }

        $equipment = $training->equipment;
        if (!$equipment->id || !$equipment->require_training || $equipment->status==EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            URI::redirect('error/404');
        }
        $me = L('ME');
        if (!$me->is_allowed_to('管理培训', $equipment)) {
            URI::redirect('error/401');
        }

        $user = $training->user;
        $training->status = $type == 'delete'
        ? UE_Training_Model::STATUS_DELETED : UE_Training_Model::STATUS_REFUSE;
        $training->save();
        
        $cache = Cache::factory();
        $ids = $cache->get("training_applied_ids") ? : [];
        if (isset($ids[$id])) unset($ids[$id]);
        $cache->set("training_applied_ids", $ids, 300);

        if ($training->status == UE_Training_Model::STATUS_REFUSE) {
            Log::add(strtr('[equipments] %user_name[%user_id]拒绝%equipment_name[%equipment_id]仪器的个人培训申请[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%training_id'=> $training->id]), 'journal');

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '已拒绝%name的仪器使用申请', ['%name'=>H($user->name)]));
        } else {
            Log::add(strtr('[equipments] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的个人培训记录[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%training_id'=> $training->id]), 'journal');

            Notification::send('equipments.training_removed', $user, [
                '%user'=>Markup::encode_Q($user),
                '%equipment'=>Markup::encode_Q($equipment),
            ]);

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '已删除%name的培训/授权过期记录', ['%name'=>H($user->name)]));
        }

        URI::redirect($_SERVER['HTTP_REFERER']);
    }
}

class Training_AJAX_Controller extends AJAX_Controller {

	function index_group_add_click() {
        $equipment = O('equipment', Input::form('equipment_id'));
		//判断权限
		if(!$equipment->id || $equipment->status==EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            JS::alert(I18N::T('equipments', '该仪器已报废！无法添加团体培训！'));
			return;
		}
		if (!L('ME')->is_allowed_to('管理培训', $equipment)) {
            JS::alert(I18N::T('equipments', '权限不足！'));
			return;
		}
 		$training = O('ge_training');
		JS::dialog(V('training/group/edit',['training'=>$training, 'equipment'=>$equipment]));
	}

    public function index_group_edit_click()
    {
        $training = O('ge_training', Input::form('training_id'));
        $equipment = $training -> equipment;
        if (!$equipment->id || $equipment->status==EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return;
        }
        if (!L('ME')->is_allowed_to('管理培训', $equipment)) {
            return;
        }
        JS::dialog(V('training/group/edit', [
            'training'=>$training,
        ]));
    }

    public function index_group_delete_click()
    {
        if (!JS::confirm(I18N::T('equipments', '您确定删除此次培训吗?'))) {
            return;
        }
        $me = L('ME');
        $training = O('ge_training', Input::form('training_id'));
        $equipment = $training -> equipment;
        if (!$me->is_allowed_to('管理培训', $equipment)) {
            return;
        }
        if ($training->id) {
            $training->delete();

            Log::add(strtr('[equipments] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的团体培训[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%training_id'=> $training->id]), 'journal');

            JS::refresh();
        }
    }

	function index_group_edit_submit() {
		$form = Form::filter(Input::form());
		$training = O('ge_training', $form['training_id']);
		$equipment = $form['equipment_id'] ? O('equipment', $form['equipment_id']) : $training->equipment;
		$me = L('ME');
		if (!$me->is_allowed_to('管理培训', $equipment)) {
			return;
		}
		$form
			->validate('user_id', 'number(>0)', I18N::T('equipments', '负责人不能为空!'))
			->validate('ntotal', 'number(>0)', I18N::T('equipments', '培训人数应大于0!'))
			->validate('napproved', 'number(>=0)', I18N::T('equipments', '通过人数不应小于0!'))
			->validate('date', 'not_empty', I18N::T('equipments', '培训通过时间不能为空'))
			->validate('napproved', 'compare(<=ntotal)', I18N::T('equipments', '通过人数不应大于培训人数!'));

		$user = O('user', $form['user_id']);
		if ($form['user_id'] && !$user->id) {
			$form->set_error('user_id', I18N::T('equipments', '请选择有效的用户!'));
		}

		if ($form->no_error) {

			if (!$training->id && $form['equipment_id']) {
				$action = '添加';
				$training->equipment = $equipment;
			}
			else {
				$action = '修改';
			}
			$training->user = $user;
			$training->ntotal = $form['ntotal'];
			$training->napproved = $form['napproved'];
			$training->date = $form['date']? $form['date']:Date::time();
			$training->description = $form['description'];
			$training->save();

            switch($action) {
                case '添加' :
                    Log::add(strtr('[equipments] %user_name[%user_id]添加%equipment_name[%equipment_id]仪器的团体培训[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $training->equipment->name, '%equipment_id'=> $training->equipment->id, '%training_id'=> $training->id]), 'journal');
                    break;
                case '修改':
                    Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的团体培训[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $training->equipment->name, '%equipment_id'=> $training->equipment->id, '%training_id'=> $training->id]), 'journal');
                    break;
            }

			JS::refresh();
		}
		else {
			JS::dialog(V('training/group/edit', [
					'training'=>$training,
					'form'=>$form,
					'equipment'=>$equipment
				]));
		}
	}

    /* NO.TASK#322(xiaopei.li@2011.03.07) */
    public function index_add_approved_user_click()
    {
        $form = Input::form();

        JS::dialog(
            V('training/add_member', ['equipment_id'=>$form['equipment_id']]),
            ['title' => I18N::T('equipments', '添加已通过培训用户')]
        );
    }

    public function index_add_approved_user_submit()
    {
        $form = Input::form();

        $equipment = O('equipment', $form['equipment_id']);
        if (!$equipment->id) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '添加已通过培训用户出错'));
            JS::refresh();
            return;
        }

        $approved_users = json_decode($form['approved_users']);
        if (!count($approved_users)) {
            JS::alert(I18N::T('equipments', '请正确选择用户!'));
            return;
        }

        if ($form['atime']) {
            $today = getdate(time());
            $now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
            $dl = getdate($form['deadline']);
            $deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
            if ($now - $deadline > 0) {
                JS::alert(I18N::T('equipments', '过期时间不能小于当前时间!'));
                return;
            }
        }

        $me = L('ME');
        foreach ($approved_users as $id => $name) {
            $user = O('user', $id);
            if (!$user->id) {
                continue;
            }
            $status = implode(',', [
                UE_Training_Model::STATUS_APPLIED,
                UE_Training_Model::STATUS_APPROVED,
                UE_Training_Model::STATUS_AGAIN,
            ]);
            $training = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]")->current();
            if (!$training->id) {
                $training = O('ue_training');
            }
            $training->user = $user;
            $training->equipment = $equipment;
            $training->status = UE_Training_Model::STATUS_APPROVED;
            $training->type = $user->member_type;
            $training->atime = $form['atime'] ? $form['deadline']: '0';

            $training->save();

            Event::trigger('trigger_scoring_rule', $user, 'qualication', $equipment);

            Log::add(strtr('[equipments] %user_name[%user_id]添加%equipment_name[%equipment_id]仪器的个人培训记录[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%training_id'=> $training->id]), 'journal');
        }

        JS::refresh();
    }

    /* TASK#319(xiaopei.li@2011.03.08) */
    public function index_approve_user_click()
    {
        $form = Input::form();

        JS::dialog(V('training/approve_user', ['tid'=>$form['tid']]));
    }

    public function index_edit_approved_user_click()
    {
        $form = Input::form();

        $training = O('ue_training', $form['tid']);
        if ($training->id) {
            JS::dialog(V('training/approve_user', ['tid'=>$training->id, 'atime'=>$training->atime]));
        } else {
            JS::redirect(URI::url('error/404'));
        }
    }

    public function index_approve_user_submit()
    {
        //批准
        $form = Input::form();

        $training = O('ue_training', $form['tid']);
        if (!$training->id) {
            JS::redirect(URI::url('error/404'));
            return;
        }

        $equipment = $training->equipment;
        $user = $training->user;
        if (!$equipment->id || !$equipment->require_training || $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            JS::redirect(URI::url('error/404'));
            return;
        }

        $status = UE_Training_Model::STATUS_APPROVED;
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}][id!={$training->id}]");
        if ($trainings->total_count()) {
            JS::alert(I18N::T('equipments', '该用户已通过培训!'));
            JS::refresh();
            return;
        }

		$me = L('ME');
		if (!$me->is_allowed_to('管理培训', $equipment)) {
            JS::redirect(URI::url('error/401'));
            return;
        }

        if ($form['atime']) {
            $today = getdate(time());
            $now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
            $dl = getdate($form['deadline']);
            $deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
            if ($now - $deadline > 0) {
                JS::alert(I18N::T('equipments', '过期时间不能小于当前时间!'));
                return;
            }
        }

        $training->user = $user;
        $training->proposer = $training->proposer;
        $training->equipment = $equipment;
        $training->status = UE_Training_Model::STATUS_APPROVED;
        $training->type = $user->member_type;
        $training->atime = $form['atime'] ? $form['deadline']: '0';
        $training->mtime = time();
        $training->save();

        Event::trigger('trigger_scoring_rule', $user, 'qualication', $equipment);

        Log::add(strtr('[equipments] %user_name[%user_id]重新授权%equipment_name[%equipment_id]仪器的个人培训记录[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%training_id'=> $training->id]), 'journal');

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '已重新授权%name的仪器使用申请', ['%name'=>H($user->name)]));
        JS::refresh();
    }

    public function index_incharge_add_approved_user_click()
    {
        $form = Input::form();
        $equipment = O('equipment');
        if (isset($form['eid']) && $form['eid']) {
            $equipment = O('equipment', $form['eid']);
        }
        if ($equipment->id) {
            JS::dialog(
                V('training/incharge_add_member', ['equipment' => $equipment]),
                ['title' => I18N::T('equipments', '添加批量授权/培训')]
            );
        } else {
            JS::dialog(
                V('training/incharge_add_member'),
                ['title' => I18N::T('equipments', '添加批量授权/培训')]
            );
        }
    }

    public function index_incharge_add_approved_user_submit()
    {
        $me = L('ME');
        $form = Input::form();

        $approved_users = json_decode($form['approved_users'], true);

        $file = Input::file('file');

        //进行文件上传
        if ($file['tmp_name']) {
            $tmp_file_type = File::extension($file['name']);
            $tmp_file_name = tempnam(Config::get('system.tmp_dir'), 'incharge_training_');
            if ($tmp_file_type != 'xls' && $tmp_file_type != 'xlsx') {
                JS::alert(I18N::T('equipments', '文件类型错误, 请上传Excel文件!'));
                return;
            } else {
                File::check_path($tmp_file_name);
                move_uploaded_file($file['tmp_name'], $tmp_file_name);
            }

            $autoload = ROOT_PATH.'vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once($autoload);
            }

            $PHPReader = new PHPExcel_Reader_Excel2007;
            if (!$PHPReader->canRead($tmp_file_name)) {
                $PHPReader = new PHPExcel_Reader_Excel5;
                if (!$PHPReader->canRead($tmp_file_name)) {
                    JS::alert(I18N::T('equipments', '读取Excel文件错误，请联系系统管理员!'));
                    return false;
                }
            }

            $PHPExcel = $PHPReader->load($tmp_file_name);
            $currentSheet = $PHPExcel->getSheet(0);
            $columns = 4;
            $start_row = 2;
            $end_row = $currentSheet->getHighestRow();

            for ($j = $start_row; $j <= $end_row; $j++) {
                // 找人时以邮箱、登录账号、学工号一起找；再以邮箱，登录账号找；再以登录账号找
                $token = $currentSheet->getCellByColumnAndRow(1, $j);
                $ref_no = $currentSheet->getCellByColumnAndRow(2, $j);
                $email = $currentSheet->getCellByColumnAndRow(3, $j);
                // 空行检验
                if ($email == '' && $ref_no == '' && $token == '') {
                    continue;
                }

                $db = Database::factory();

                $sql = "select * from user where email='{$email}' and ref_no='{$ref_no}' and token like '{$token}|%' limit 1";

                $result = $db->query($sql);
                
                if (!$result) continue;
                $user =$result->row();
                
                if ($user->id) {
                    $approved_users[$user->id] = $user->name;
                    continue;
                }

                $sql = "select * from user where email='{$email}' and token like '{$token}|%' limit 1";

                $result = $db->query($sql);
                
                if (!$result) continue;
                $user =$result->row();

                if ($user->id) {
                    $approved_users[$user->id] = $user->name;
                    continue;
                }

                $sql = "select * from user where token like '{$token}|%' limit 1";

                $result = $db->query($sql);
                
                if (!$result) continue;
                $user =$result->row();

                if ($user->id) {
                    $approved_users[$user->id] = $user->name;
                    continue;
                }

                $name = $currentSheet->getCellByColumnAndRow(0, $j);
                JS::alert(I18N::T('equipments', "导入表第{$j}行用户({$name})信息不存在!"));
                return;
            }
        }
        if (!count($approved_users)) {
            JS::alert(I18N::T('equipments', '请正确选择用户!'));
            return;
        }

        $equipments = [];
        foreach ($form['equipments'] as $id => $value) {
            if ($value == 'on') {
                $equipments[] = $id;
            }
        }
        if (!count($equipments)) {
            JS::alert(I18N::T('equipments', '请正确选择授权仪器!'));
            return;
        }
        if (mb_strlen($form['description']) > 50) {
            JS::alert(I18N::T('equipments', '授权备注最多填写50个字!'));
            return;
        }

        if ($form['atime']) {
            $today = getdate(time());
            $now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
            $dl = getdate($form['deadline']);
            $deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
            if ($now - $deadline > 0) {
                JS::alert(I18N::T('equipments', '过期时间不能小于当前时间!'));
                return;
            }
        }

        foreach ($equipments as $eqId) {
            $equipment = O('equipment', $eqId);
            if (!$equipment->id) {
                continue;
            }

            foreach ($approved_users as $id => $name) {
                $user = O('user', $id);
                if (!$user->id) {
                    continue;
                }
                $status = implode(',', [
                    UE_Training_Model::STATUS_APPLIED,
                    UE_Training_Model::STATUS_APPROVED,
                    UE_Training_Model::STATUS_AGAIN,
                ]);
                $training = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]")->current();
                if (!$training->id) {
                    $training = O('ue_training');
                }
                $training->user = $user;
                $training->equipment = $equipment;
                $training->status = UE_Training_Model::STATUS_APPROVED;
                $training->type = $user->member_type;
                $training->atime = $form['atime'] ? $form['deadline']: '0';
                $training->description = $form['description'];

                $training->save();
                Event::trigger('trigger_scoring_rule', $user, 'qualication', $equipment);
                Log::add(strtr('[equipments] %user_name[%user_id]添加%equipment_name[%equipment_id]仪器的个人培训记录[%training_id]', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%equipment_name'=> $equipment->name,
                    '%equipment_id'=> $equipment->id,
                    '%training_id'=> $training->id
                ]), 'journal');
            }
        }
        JS::refresh();
    }

    public function index_export_applied_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];

        if (!$form_token) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::refresh();
            return false;
        }

        $type = $form['type'];
        $columns = Config::get('equipments.export_columns.training.applied');
        $equipment = O('equipment', $form['equipment_id']);
        if (!$equipment->id || $equipment->control_mode != 'bluetooth') {
            unset($columns['check_time']);
        }
        $type = $form['type'];
        if ($type == 'csv') {
            $title = I18N::T('people', '请选择要导出Excel的列');
        } else {
            $title = I18N::T('people', '请选择要打印的列');
        }
        JS::dialog(
            V('training/export_form_applied', [
            'form_token' => $form_token,
            'columns' => $columns,
            'equipment' => $equipment,
            'type' => $type
            ]),
        ['title' => I18N::T('people', $title)]
        );
    }

    public function index_export_applied_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $pid = $this->_export_csv($selector, $form, $file_name, 'applied');
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid' => $pid
        ]), [
            'title' => I18N::T('equipments', '导出等待')
        ]);
    }

    public function index_export_approved_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];

        if (!$form_token) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::refresh();
            return false;
        }

        $type = $form['type'];
        $columns = Config::get('equipments.export_columns.training.approved');

        if ($type == 'csv') {
            $title = I18N::T('people', '请选择要导出Excel的列');
        } else {
            $title = I18N::T('people', '请选择要打印的列');
        }
        JS::dialog(
            V('training/export_form_approved', [
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
            ]),
        ['title' => I18N::T('people', $title)]
        );
    }

    public function index_export_approved_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $pid = $this->_export_csv($selector, $form, $file_name, 'approved');
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid' => $pid
        ]), [
            'title' => I18N::T('equipments', '导出等待')
        ]);
    }

    public function index_export_overdue_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];

        if (!$form_token) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::refresh();
            return false;
        }

        $type = $form['type'];
        $columns = Config::get('equipments.export_columns.training.overdue');

        $type = $form['type'];
        if ($type == 'csv') {
            $title = I18N::T('people', '请选择要导出Excel的列');
        } else {
            $title = I18N::T('people', '请选择要打印的列');
        }
        JS::dialog(
            V('training/export_form_overdue', [
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
            ]),
        ['title' => I18N::T('people', $title)]
        );
    }

    public function index_export_overdue_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $pid = $this->_export_csv($selector, $form, $file_name, 'overdue');
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid' => $pid
        ]), [
            'title' => I18N::T('equipments', '导出等待')
        ]);
    }

    public function index_export_group_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];

        if (!$form_token) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::refresh();
            return false;
        }

        $type = $form['type'];
        $columns = Config::get('equipments.export_columns.training.group');

        $type = $form['type'];
        if ($type == 'csv') {
            $title = I18N::T('people', '请选择要导出Excel的列');
        } else {
            $title = I18N::T('people', '请选择要打印的列');
        }

        JS::dialog(
            V('training/export_form_group', [
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
            ]),
        ['title' => I18N::T('people', $title)]
        );
    }

    public function index_export_group_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $pid = $this->_export_csv($selector, $form, $file_name, 'group');
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid' => $pid
        ]), [
            'title' => I18N::T('equipments', '导出等待')
        ]);
    }

    public function index_batch_apply_click()
    {
        JS::dialog(V('profile/incharge_training/form_apply', []), ['title' => I18N::T('equipments', '批量审批授权/培训')]);
    }

    public function index_batch_apply_submit()
    {
        //批准
        $me = L('ME');
        $form = Input::form();

        if ($form['atime']) {
            $today = getdate(time());
            $now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
            $dl = getdate($form['deadline']);
            $deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
            if ($now - $deadline > 0) {
                JS::alert(I18N::T('equipments', '过期时间不能小于当前时间!'));
                return;
            }
        }

        if (mb_strlen($form['description']) > 50) {
            JS::alert(I18N::T('equipments', '授权备注最多填写50个字!'));
            return;
        }

        if (!$form['apply_type']) {
            JS::alert(I18N::T('equipments', '请选择审批操作!'));
            return;
        }

        $cache = Cache::factory();
        $ids = $cache->get("training_applied_ids") ? : [];
        if (!$ids) {
            JS::alert(I18N::T('equipments', '请选择需要审批的培训!'));
            return;
        }

        foreach ($ids as $key => $value) {
            $training = O('ue_training', $key);
            $training->proposer = $me;
            $training->status = $form['apply_type'] == 'apply' ? UE_Training_Model::STATUS_APPROVED : UE_Training_Model::STATUS_REFUSE;
            $training->atime = $form['atime'] ? $form['deadline'] : 0;
            $training->mtime = time();
            $training->description = $form['description'];
            $training->save();
            $equipment = $training->equipment;

            Log::add(strtr('[equipments] %user_name[%user_id]批量授权%equipment_name[%equipment_id]仪器的个人培训记录[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%training_id'=> $training->id]), 'journal');
        }

        $cache->set("training_applied_ids", [], 300);

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '批量授权成功', ['%name'=>H($user->name)]));
        JS::refresh();
    }

    public function index_training_check()
    {
        $me = L('ME');
        $form = Input::form();

        $cache = Cache::factory();
        $array = $cache->get("training_{$form['type']}_ids") ? : [];

        $form_ids = implode(',', array_keys($form['ids']));
        // 先取出来自己负责仪器并id包含在ids中的培训 保证培训全部是自己负责的
        // 再和提交的取并集 过滤掉不是自己负责仪器的id 并且还能保留选择与否的标志
        $ids = array_intersect_key($form['ids'], Q("{$me}<incharge equipment ue_training[id={$form_ids}]")->to_assoc('id', 'id'));

        if ($form) {
            foreach ($ids as $key => $item) {
                if ($item) {
                    $array[$key] = true;
                } else {
                    unset($array[$key]);
                }
            }
        }

        $cache->set("training_{$form['type']}_ids", $array, 300);
        return true;
    }

    public function _export_csv($selector, $form, $file_name, $type)
    {
        $me = L('ME');
        $full_columns = Config::get('equipments.export_columns.training.'.$type);
        $equipment = O('equipment', $form['equipment_id']);
        if (!$equipment->id && $equipment->control_mode == 'bluetooth') {
            unset($full_columns['check_time']);
        }
        $valid_columns = [];

        foreach ($form['columns'] as $name=> $value) {
            if ($value == 'on' && array_key_exists($name, $full_columns)) {
                $valid_columns[$name] = $full_columns[$name];
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

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_training_'.$type.' export ';
        $cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."'>/dev/null 2>&1 &";
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

    public function index_batch_overdue_click()
    {
        $form = Input::form();
        JS::dialog(V('profile/incharge_training/form_apply_overdue', ['form'=>$form]), ['title' => I18N::T('equipments', '重新审批授权/培训')]);
    }

    public function index_batch_overdue_submit()
    {
        //批准
        $me = L('ME');
        $form = Input::form();

        if ($form['atime']) {
            $today = getdate(time());
            $now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
            $dl = getdate($form['deadline']);
            $deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
            if ($now - $deadline > 0) {
                JS::alert(I18N::T('equipments', '过期时间不能小于当前时间!'));
                return;
            }
        }

        $cache = Cache::factory();
        $ids = $cache->get("training_overdue_ids") ? : (isset($form['tid']) ? [$form['tid']=>$form['tid']] : []);
        if (!$ids) {
            JS::alert(I18N::T('equipments', '请选择需要重新审批的培训!'));
            return;
        }

        foreach ($ids as $key => $value) {
            $training = O('ue_training', $key);
            $training->proposer = $me;
            $training->status = UE_Training_Model::STATUS_APPROVED;
            $training->atime = $form['atime'] ? $form['deadline'] : 0;
            $training->description = $form['description'];
            $training->save();
            $equipment = $training->equipment;
            Log::add(strtr('[equipments] %user_name[%user_id]批量重新授权%equipment_name[%equipment_id]仪器的个人培训记录[%training_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%training_id'=> $training->id]), 'journal');
        }

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '批量授权成功'));
        JS::refresh();
    }
}
