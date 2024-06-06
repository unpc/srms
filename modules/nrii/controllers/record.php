<?php

class record_Controller extends Base_Controller {
    function index() {
        URI::redirect(URI::url('!nrii/nrii.record'));
    }

    function edit($id = 0) {
        $record = O('nrii_record',$id);
        if (!$record->id){
            URI::redirect('error/404');
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form = Form::filter(Input::form())
                ->validate('amounts', 'not_empty', I18N::T('nrii', '请输入正确服务金额!'))
                ->validate('amounts', 'is_numeric', I18N::T('nrii', '请输入正确服务金额!'))
                // ->validate('service_time', 'not_empty', I18N::T('nrii', '请输入实际服务时间!'))
                ->validate('start_time', 'not_empty', I18N::T('nrii', '请输入服务开始时间!'))
                ->validate('service_content', 'not_empty', I18N::T('nrii', '请输入实际服务内容!'))
                // ->validate('service_way', 'not_empty', I18N::T('nrii', '请输入服务方式!'))
                ->validate('service_amount', 'not_empty', I18N::T('nrii', '请输入服务量!'))
                // ->validate('subject_name', 'not_empty', I18N::T('nrii', '请选择课题名称!'))
                ->validate('subject_area', 'not_empty', I18N::T('nrii', '请输入课题主要科学领域!'))
                // ->validate('subject_content', 'not_empty', I18N::T('nrii', '请输入课题研究内容!'))
                ->validate('applicant', 'not_empty', I18N::T('nrii', '请输入申请人!'))
                ->validate('applicant_phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
                ->validate('applicant_email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('applicant_email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('applicant_unit', 'not_empty', I18N::T('nrii', '请输入申请人单位!'))
                // ->validate('comment2', 'not_empty', I18N::T('nrii', '请选择用户评价!'))
                ;
            if(count($form['service_way']) <= 0){
                $form->set_error('service_way', I18N::T('nrii', '请选择服务方式'));
            }
            if($form['amounts'] < 0 ){
                $form->set_error('amounts', I18N::T('nrii', '服务金额不能设置为负数'));
            }
            if(!count($form['subject_income'])){
                $form->set_error('subject_income', I18N::T('nrii', '请选择经费来源!'));
            }
            if (count($form['subject_income']) > 4) {
                $form->set_error('subject_income', I18N::T('nrii', '经费来源最多可选4个!'));
            }
            if($form['service_type'] == -1){
                $form->set_error('service_type', I18N::T('nrii', '请选择服务对象!'));
            }
            if($form['service_direction'] == -1){
                $form->set_error('service_direction', I18N::T('nrii', '请选择服务类型!'));
            }
            if($form['subject_area'] == '{}'){
                $form->set_error('subject_area', I18N::T('nrii', '请输入课题主要科学领域!'));
            }elseif(count(json_decode($form['subject_area'], true)) > 4){
                $form->set_error('subject_area', I18N::T('nrii', '课题主要科学领域填写不能超过4个!'));
            }
            if($form['comment'] == -1){
                $form->set_error('comment', I18N::T('nrii', '请选择用户评价!'));
            }
            if ($form['address_type'] == 1) {
                $form->validate('move_address', 'not_empty', I18N::T('nrii', '请输入对外服务地址!'));
                // TODO 此处有个问题是需要判断非适用简易程序海关《通知书》编号，在仪器进口之后的三年内如果对外服务的话需要填写该编号
            }

            if ($form['service_direction'] == Nrii_Record_Model::SERVICE_DIRECTION_OTHER) {
                $form->validate('tax_record', 'not_empty', I18N::T('nrii', '请输入补税记录!'));
                // TODO 此处有个问题是需要判断非适用简易程序海关《通知书》编号，在仪器进口之后的三年内如果对外服务的话需要填写该编号
            }

            if($form->no_error){
                $service_way = [];
                foreach ($form['service_way'] as $key => $value) {
                    if ($value == 'on') $service_way[] = $key;
                }
                $service_way = implode(',', $service_way);

                $record->amounts = (double)$form['amounts'];
                $record->service_time = $form['service_time'];
                $record->start_time = $form['start_time'];
                $record->end_time = $form['end_time'];
                $record->service_content = mb_substr($form['service_content'], 0, 200, 'utf-8');
                $record->service_way = $service_way;
                $record->service_amount = $form['service_amount'];
                $record->subject_name = $form['subject_name']? : '无';
                $subject_income = [];
                foreach ($form['subject_income'] as $key => $value) {
                    if ($value == 'on') $subject_income[] = $key;
                }
                $subject_income = implode(',', $subject_income);
                $record->subject_income = $subject_income;
                $record->subject_area = $form['subject_area'];
                $record->subject_content = $form['subject_content']? mb_substr($form['subject_content'], 0, 200, 'utf-8') : '无';

                $record->applicant = mb_substr($form['applicant'], 0, 20, 'utf-8');
                $record->applicant_phone = mb_substr($form['applicant_phone'], 0, 20, 'utf-8');
                $record->applicant_email = mb_substr($form['applicant_email'], 0, 50, 'utf-8');
                $record->applicant_unit = mb_substr($form['applicant_unit'], 0, 100, 'utf-8');
                $record->comment = $form['comment'];
                $record->comment2 = $form['comment2']? : '无';
                $record->service_type = $form['service_type'];
                $record->service_direction = $form['service_direction'];
                $record->tax_record = $form['tax_record']? : '无';

                $record->address_type = (int)$form['address_type'];
                $record->move_address = H($form['move_address']);
                $record->service_code = H($form['service_code']);
                $record->sign_agreement = (int)$form['sign_agreement'];

                $record->nrii_status = 1;

                $record->save();

                if ($record->id) {
                    Log::add(strtr('[nrii_record] %user_name[%user_id]编辑[%record_id]服务记录', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%record_id'=> $record->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '编辑服务记录成功!'));

                    URI::redirect(URI::url('!nrii/record/edit.'.$record->id));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '编辑失败! 请与系统管理员联系。'));
                }

            }
        }
        $breadcrumb = [
            [
                'url' => URI::url('!nrii/nrii.record'),
                'title' => I18N::T('nrii', '服务记录')
            ],
            [
                'url' => URI::url('!nrii/record/edit.' . $id),
                'title' => I18N::T('nrii', '编辑')
            ]
        ];

        $this->layout->body->primary_tabs
            ->add_tab('record', ['*' => $breadcrumb])
            ->select('record')
            ->set('content', V('nrii:record/edit', [
                    'form' => $form,
                    'record' => $record
                ]));
    }

    // function delete($id = 0) {
    //     $record = O('nrii_record', $id);

    //     if (!$record->id) {
    //         URI::redirect('error/404');
    //     }

    //     $me = L('ME');
    //     // if (!$me->is_allowed_to('删除', $record)) {
    //     //  URI::redirect('error/401');
    //     // }

    //     Log::add(strtr('[nrii_record] %user_name[%user_id]删除%record_name[%record_id]服务记录', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%record_name'=> $record->cname, '%record_id'=> $record->id]), 'journal');
    //     $record->delete_icon();
    //     if ($record->delete()) {
    //         Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '服务记录删除成功!'));
    //     }
    //     URI::redirect(URI::url('!nrii/nrii.record'));
    // }

    function sync() {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php nrii sync_record';
        //增加传递的参数
        $cmd .= " ".L('ME')->id;
        $cmd .= " >/dev/null 2>&1 &";

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        // $pid = intval($var['pid']) + 1;

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '上传服务记录至国家科技部成功!'));

        URI::redirect(URI::url('!nrii/nrii.record'));
    }
}

class record_AJAX_Controller extends AJAX_Controller {

    function index_batch_edit_click() {
        JS::dialog((string)V('nrii:record/batch_edit',[
        ]));
    }

    function index_batch_edit_submit() {
        $session = $_SESSION['nrii_record'];
        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form = Form::filter(Input::form())
                ->validate('service_content', 'not_empty', I18N::T('nrii', '请输入实际服务内容!'))
                // ->validate('subject_area', 'not_empty', I18N::T('nrii', '请输入课题主要科学领域!'))
                ;
            $service_way = [];
            foreach ($form['service_way'] as $key => $value) {
                if ($value=='on'){
                    $service_way[$key] = $value;
                }
            }
            $form['subject_income'] = array_merge(array_diff($form['subject_income'], array('null')));
            if(count($service_way) <= 0){
                $form->set_error('service_way', I18N::T('nrii', '请选择服务方式'));
            }
            if(!count($form['subject_income'])){
                $form->set_error('subject_income', I18N::T('nrii', '请选择经费来源!'));
            }
            if (count($form['subject_income']) > 4) {
                $form->set_error('subject_income', I18N::T('nrii', '经费来源最多可选4个!'));
            }
            if($form['subject_area'] == '{}'){
                $form->set_error('subject_area', I18N::T('nrii', '请输入课题主要科学领域!'));
            }elseif(count(json_decode($form['realm'], true)) > 4){
                $form->set_error('subject_area', I18N::T('nrii', '课题主要科学领域填写不能超过4个!'));
            }
            if($form['comment'] == -1){
                $form->set_error('comment', I18N::T('nrii', '请选择用户评价!'));
            }

            if($form->no_error){
                $service_way = [];
                foreach ($form['service_way'] as $key => $value) {
                    if ($value == 'on') $service_way[] = $key;
                }
                $service_way = implode(',', $service_way);

                $cntFail = 0;
                $cntSucc = 0;
                foreach ($session as $value) {
                    $record = O("nrii_record", $value);
                    if (!$record->id){
                        $cntFail++;
                        continue;
                    }
                    $record->service_content = mb_substr($form['service_content'], 0, 200, 'utf-8');
                    $record->service_way = $service_way;
                    $record->subject_name = $form['subject_name']? : '无';
                    $subject_income = [];
                    foreach ($form['subject_income'] as $key => $value) {
                        if ($value == 'on') $subject_income[] = $key;
                    }
                    $subject_income = implode(',', $subject_income);
                    $record->subject_income = $subject_income;
                    $record->subject_area = $form['subject_area'];
                    $record->subject_content = $form['subject_content']? mb_substr($form['subject_content'], 0, 200, 'utf-8') : '无';

                    $record->comment = $form['comment'];
                    $record->comment2 = $form['comment2']? : '无';

                    $record->nrii_status = 1;

                    $record->save();
                    $cntSucc++;
                }
                $_SESSION['nrii_record_massage'] = [$cntSucc, $cntFail];
                unset($_SESSION['nrii_record']);
                JS::refresh();
            }else{
                JS::dialog((string)V('nrii:record/batch_edit',[
                    'form' => $form,
                ]));
            }
        }
    }

    function index_nrii_record_checked(){
        $form = Input::form();

        $token = $form['token'];
        $session = (array) $_SESSION[$token];
        $ids = array_filter($form['ids']);

        if (isset($session)) {
            foreach ($ids as $key => $value) {
                if ($value == 'true') {
                    if (array_search($key ,$session) === FALSE) {
                        array_push($session, $key);
                    }
                }
                else {
                    unset($session[array_search($key ,$session)]);
                }
            }
        }
        else {
            $session = [];
            foreach ($ids as $key => $value) {
                if ($value == 'true') {
                    array_push($session, $key);
                }
            }
        }
        $_SESSION[$token] = $session;
    }

    function index_refresh_click() {
        $me = L('ME');

        if ($me->access('管理所有内容') || $me->access('科技部对接管理')) {
            $res = exec("ps -ef | grep 'nrii import_record'", $o);

            if (count($o) == 2) {
                $command = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php nrii import_record >/dev/null 2>&1 &';
                exec($command, $output);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '数据刷新成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '数据刷新中, 请勿同时多次刷新!'));
            }
        }

        JS::refresh();
    }

    function index_export_click() {

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $selector = $_SESSION['nrii_record'];

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
		$valid_columns = Config::get('columns.export_columns.record');

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
		$cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_nrii_record export ';
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
