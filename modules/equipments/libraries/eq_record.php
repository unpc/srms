<?php

class EQ_Record {

	static function eq_record_attachments_ACL($e, $user, $perm, $object, $options) {
		if (!$object->id && !Config::get('eq_record.show_attachment')) {		/* 仅已存在的记录可以有附件 */
			return;
		}

		if ($options['type'] != 'attachments') {
            return;
        }

        if (Equipments::user_is_eq_incharge($user, $object->equipment) ||
            $user->access('管理所有内容')) {
            /* 机主能够在添加和编辑记录的时候上传附件 */
            $e->return_value = TRUE;
            return FALSE;
        }

		switch ($perm) {
		case '列表文件':
		case '下载文件':
			if ($user->is_allowed_to('修改仪器使用记录', $object->equipment)) {
	 			$e->return_value = TRUE;
	 			return FALSE;
 			}
			if ($user->id == $object->user->id) { /* 使用者能够在查看记录时下载附件 */
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '上传文件':
		case '修改文件':
		case '删除文件':
			if ($user->is_allowed_to('修改仪器使用记录', $object->equipment)) {
	 			$e->return_value = TRUE;
	 			return FALSE;
 			}
			break;
		default:
			return;				/* 到这儿说明是$perm有误 */
		}
	}

	static function before_user_save_message($e, $user) {
		/*
			自定义的在用户删除时候提示的信息，可以进行修正。暂时定为只要该用户有相关的使用记录，则不可删除。
		*/
		if (Q("eq_record[user={$user}]")->total_count()) {
			$e->return_value = I18N::T('equipments', '该用户关联了相应的使用记录!');
			return FALSE;
		}

	}

	static function is_timespan_locked($e, $record, $params) {
		$equipment = $record->equipment;
		$dtstart = $params[0] + 1; // 允许和当前已存在的使用记录有1秒钟的重叠
		$dtend = $params[1] == 0 ? 0 : $params[1] - 1;


		$time = Lab::get('transaction_locked_deadline');
		/* Deadline 时间限制, 在transaction_locked_deadline范围内的记录将不予修改 */

		$ori_dtend = $record->get('dtend', TRUE);

        //之前存在dtend
        //并且已被锁定, 则被锁定
        //若之前是使用中, 则可修改
        if (($ori_dtend && $ori_dtend <= $time) && ($dtend && $dtend <= $time)) {
			$e->return_value = TRUE;
			return FALSE;
        }

        //BUG# 13809 自动锁定的时间段内不应该加入使用记录
        if (!$record->id && $dtend && $dtend <= $time) {
			$e->return_value = TRUE;
			return FALSE;
        }

		// 后添加	先添加	case
		// 闭合	闭合	检测后添加的使用记录是否与其他使用记录交错
		// 闭合	使用中	检查闭合的使用记录结束时间是否晚于使用中的开始时间
		// 使用中	使用中	直接报错
		// 使用中	闭合	检查使用中的使用记录的开始时间是否晚于其他使用记录
        if ($dtend) {
            // 使用中的使用记录
			if (Q("eq_record[id!={$record->id}][equipment={$equipment}][dtend=0][dtstart<$dtstart]")->total_count()
            // 包含该dtstart, dtend跨度的使用记录
			|| Q("eq_record[id!={$record->id}][equipment={$equipment}][dtstart=$dtstart~$dtend|dtend=$dtstart~$dtend|dtstart~dtend=$dtstart]")->total_count()
			) {
                $e->return_value = TRUE;
                return FALSE;
            }
        } else {
            //使用中的使用记录
            if (Q("eq_record[id!={$record->id}][equipment={$equipment}][dtend=0]")->total_count()
            //或者包含该dtstart跨度的使用记录
            || Q("eq_record[id!={$record->id}][dtstart~dtend={$dtstart}][dtend>0][equipment={$equipment}]")->total_count()
            ) {
                $e->return_value = TRUE;
                return FALSE;
            }
        }
	}

    //打印仪器使用记录hook
    static function print_equipment_records($e, $records, $form, $group_or_all_equipments = FALSE) {
        if ($group_or_all_equipments) {
            $dt_from = strtotime('midnight -1 month');
            $dtstart = $form['dtstart_check'] ? $form['dtstart'] : $dt_from;
            $dtend = $form['dtend_check'] ? $form['dtend'] : Date::time();
        }
        else {
            $dtstart = $form['dtstart_check'] ? $form['dtstart'] : null;
            $dtend = $form['dtend_check'] ? $form['dtend'] : null;
        }
        $e->return_value = V('records_print', ['records'=>$records, 'dtstart'=>$dtstart, 'dtend'=>$dtend]);
    }

    static function get_array_csv_title($e) {
        $array = [
            I18N::T('equipments', '仪器'),
            I18N::T('equipments', '使用者'),
            I18N::T('equipments', '实验室'),
            I18N::T('equipments', '开始时间'),
            I18N::T('equipments', '结束时间'),
            I18N::T('equipments', '使用时长'),
            I18N::T('equipments', '样品数'),
            I18N::T('equipments', '代开'),
            I18N::T('equipments', '反馈'),
        ];
        $e->return_value = $array;
    }
    //导出仪器使用记录CSV
    static function export_equipment_records($e, $records) {
		$csv = new CSV('php://output', 'w');
		/* 记录日志 */
		$me = L('ME');
		Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了仪器的使用记录', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');
		
		$title = [
	        		'equipment_name' => I18N::T('equipments', '仪器'),
					'user' => I18N::T('equipments', '使用者'),
					'lab_name' => I18N::T('equipments', '实验室'),
					'dtstart' => I18N::T('equipments', '开始时间'),
					'dtend' => I18N::T('equipments', '结束时间'),
					'duration' => I18N::T('equipments', '使用时长'),
					'total_time' => I18N::T('equipments', '时间总计（小时)'),
					'sample_num' => I18N::T('equipments', '样品数'),
					'agent' => I18N::T('equipments', '代开'),
					'status' => I18N::T('equipments', '反馈'),];
		$csv->write($title);

		if ($records->total_count() > 0) {

			$start = 0;
			$per_page = 100;

			while (1) {
				$pp_records = $records->limit($start, $per_page);
				if ($pp_records->length() == 0) break;
				foreach ($pp_records as $record) {

					/* BUG #825::导出csv文件不体现当天日期
					   解决：使用了绝对时间，并将时间分为开始时间和结束时间两列。(kai.wu@2011.7.26) */
					$dtstart = Date::format($record->dtstart);
					$dtend = !$record->dtend ? I18N::T('equipments', '使用中') : Date::format($record->dtend);
					$duration = !$record->dtend ? I18N::T('equipments', '使用中') : Date::format_duration($record->dtstart, $record->dtend);

					if ($record->dtend) {
						$record->dtend = round(($record->dtend - $record->dtstart) / 3600, 4);
					}
					else {
						$record->dtend = I18N::T('equipments', '使用中');
					}
					$total_time = $record->dtend;

					if ($record->status == EQ_Record_Model::FEEDBACK_NORMAL) {
						$status = I18N::T('equipments', '正常');
					}
					elseif ($record->status == EQ_Record_Model::FEEDBACK_PROBLEM) {
						$status = I18N::T('equipments', '故障');
					}
					else {
						$status = I18N::T('equipments', '--');
					}

					$feedback = trim($record->feedback);
					if ($feedback) {
						$status .= "|". preg_replace('/[\r\n]+/', '|', $feedback);
					}
					if ($GLOBALS['preload']['people.multi_lab']) {
						$lab = Q("{$record->project} lab")->current();
					}
					else {
						$lab = Q("{$record->user} lab")->current();
					}
					$csv->write([
						$record->equipment->name,
						$record->user->name,
						$lab,
						$dtstart,
						$dtend,
						$duration,
						$total_time,
						$record->samples,
						$record->agent->id ? $record->agent->name : '',
						$status,
					]);
				}
				$start += $per_page;
			}
		}

		$csv->close();
    }

	static function check_dt_error_eq_records() {
		$db = Database::factory();
		$imposible_date = strtotime('2000/01/01');

		$ret = $db->query("SELECT * FROM eq_record WHERE (dtstart < $imposible_date) OR " .
									"(dtend > 0 AND (dtend < $imposible_date OR dtend < dtstart))");

		if (!$ret) {
			return;
		}

		$error_records = $ret->rows();

		$format_date = function($date) {
			return $date ? Date::format($date) : 0;
		};

		$format_object = function($object) {
			return $object->id ? $object->name . '(' . $object->id . ')' : '错误对象';
		};


		if ($n = count($error_records)) {
			$content = "发现 $n 条错误仪器使用记录:\n";

			$output_file = tempnam('/tmp', 'error_eq_records');
			$output = new CSV($output_file, 'w');
			$output->write([
				'记录 ID',
				'最后修改时间',
				'开始时间',
				'开始时间(timestamp)',
				'结束时间',
				'结束时间(timestamp)',
				'使用者',
				'仪器',
				'仪器联系人',
			]);

			foreach ($error_records as $err_rec) {

				$equipment = O('equipment', $err_rec->equipment_id);
				$contacts_strings = [];
				if ($equipment->id) {

					$contacts = Q("{$equipment} user.contact:limit(3)");
					foreach ($contacts as $contact) {
						$contacts_strings[] = $format_object($contact);
					}
				}

				$user = O('user', $err_rec->user_id);

				$output->write([
					$err_rec->id,
					$format_date($err_rec->mtime),
					$format_date($err_rec->dtstart),
					$err_rec->dtstart,
					$format_date($err_rec->dtend),
					$err_rec->dtend,
					$format_object($user),
					$format_object($equipment),
					join(';', $contacts_strings),
				]);
			}

			$output->close();

			$content .= file_get_contents($output_file);

			@unlink($output_file);

			return $content;
		}

	}

    static function get_date($e, $record) {
        $e->return_value = (string) V('equipments:records_table/data/date', ['record'=> $record]);
    }

    static function get_samples($e, $record, $samples) {
		if (Config::get('eq_record.must_samples')) {
			$e->return_value = $samples;
		}
		else {
			$e->return_value = (int) max($samples, Config::get('eq_record.record_default_samples'));
		}
    }

    static function get_duration($e, $record) {
        $e->return_value = (string) V('equipments:records_table/data/duration', ['record'=> $record]);
    }

    static function get_total_time($e, $record) {
        $e->return_value = (string) V('equipments:records_table/data/total_time', ['record'=> $record]);
    }

    static function get_total_time_hour($e, $record)
    {
        $e->return_value = (string)V('equipments:records_table/data/total_time_hour', ['record' => $record]);
    }

    static function export_record_columns($e, $valid_columns)
    {
        if (Config::get('equipment.enable_use_type')) {
            $valid_columns['use_type'] = '使用类型';
            $valid_columns['use_type_desc'] = '操作备注';
		}

		if (Config::get('eq_record.duty_teacher')) {
			$valid_columns['duty_teacher'] = '值班老师';
		}
		$valid_columns['charge_amount'] = '收费金额';

        $form = Input::form();
        $form_token = $form['form_token'];
        $equipment_id = $_SESSION[$form_token]['form']['equipment_id'];
        if (!$equipment_id) {
            $equipment_id = $form['eid'];
        }

        if (!O('equipment', $equipment_id)->require_dteacher) {
            unset($valid_columns['duty_teacher']);
        }

        if ( $_SESSION[$form_token] ) {
            $setting = O('extra',['object_name'=>'equipment','object_id'=> $equipment_id,
                'type'=>'use']);
            if($setting->id){
                $valid_columns[-4] = '自定义表单';
                //获取自定义表单
                $extra = json_decode($setting->params_json, TRUE);
                foreach ($extra as $key => $fields) {
                    foreach ($fields as $name => $field) {
                        if ($name != 'count' && $name != 'description')
                            $valid_columns['extra_setting_'.$name] = $field['title'];
                    }
                }
            }
        }
        $e->return_value = $valid_columns;
        return true;
    }

    static function get_export_record_columns($e, $columns, $type) {
        if (Config::get('equipment.enable_use_type')) {
            $columns['use_type'] = '使用类型';
            $columns['use_type_desc'] = '操作备注';
		}
		
		if (Config::get('eq_record.duty_teacher')) {
			$columns['duty_teacher'] = '值班老师';
		}
		$columns['charge_amount'] = '收费金额';

        $form = Input::form();
        $form_token = $form['form_token'];
        $equipment_id = $_SESSION[$form_token]['form']['equipment_id'];
        if (!$equipment_id) {
            $equipment_id = $form['eid'];
        }

        if (!O('equipment', $equipment_id)->require_dteacher) {
            unset($columns['duty_teacher']);
        }

        if ( $_SESSION[$form_token] ) {
            $setting = O('extra',['object_name'=>'equipment','object_id'=> $equipment_id,
                'type'=>'use']);
            if($setting->id){
                //获取自定义表单
                $extra = json_decode($setting->params_json, TRUE);
                if ($extra && !empty($extra)) $columns[-4] = '自定义表单';
                foreach ($extra as $key => $fields) {
                    foreach ($fields as $name => $field) {
                        if ($name != 'count' && $name != 'description')
                            $columns['extra_setting_'.$name] = $field['title'];
                    }
                }
            }
        }

		$e->return_value = $columns;
        return TRUE;
    }

	static function eq_record_list_columns ($e, $form, $columns) {
		if (Config::get('equipment.enable_use_type')) {
			$columns['use_type'] = [
				'title' => I18N::T('equipments', '使用类型'),
				'filter'=> [
					'form' => V('equipments:records_table/filters/use_type', ['use_type' => $form['use_type']]),
					'value' => $form['use_type'] ? H(EQ_Record_Model::$use_type[$form['use_type']]) : NULL
				],
                'nowrap' => TRUE,
				'weight' => 45
			];
			// $columns['description']['weight'] = 40;
			// $columns['rest']['weight'] = 40;
		}
		return TRUE;
    }

    static function eq_record_list_row ($e, $row, $record) {
		if (Config::get('equipment.enable_use_type')) {
			$row['use_type'] = V('equipments:records_table/data/use_type', ['record' => $record]);
		}
        return TRUE;
    }

    static function glogon_login($e, $struct, $user, $equipment) {
        if (Config::get('equipment.enable_use_type')) {
			$extra = json_decode($struct->extra, TRUE);
			// Mac版的glogon客户端，没有采用自定义loginview，所以不能选取使用类型，只能直接给个默认值
            $enable_use_type_list = Config::get('equipment.enable_use_type_list');
            $use_type = $extra['use_type'] ? : $enable_use_type_list[0];
            if (
                $user->member_type < 10
                &&
                ! $user->is_allowed_to('管理使用', $equipment)
                &&
                ! in_array($use_type, $enable_use_type_list)
            ) {
                throw new EQDevice_Exception(I18N::T('equipments', '您无权选择该使用类型!'));
            }
		}
    }

    static function glogon_switch_to_login_record_saved($e, $record, $data) {
         if (Config::get('equipment.enable_use_type')) {
             $record->use_type = $data['extra']['use_type'];
             $record->use_type_desc = $data['extra']['use_type_desc'];
             $record->save();
         }
         return TRUE;
    }

    static function check_time_heartbeat ()
    {
    	$me = L('ME');
    	if (!$me->id) return;
    	$key = "time_check_user_{$me->id}";
    	if (Lab::get($key)) return;
    	if (!Q("{$me}<incharge equipment[control_mode=power]")->total_count()) return;
    	$time = Date::time();
    	$before_time = $time - 24 * 3600;
    	$equipments = Q("({$me}<incharge, eq_record[dtend<=0][dtstart<={$before_time}]) equipment[control_mode=power]");
    	if ($equipments->total_count()) {
    		JS::run(JS::smart()->jQuery->propbox((string)V('equipments:record/time_check', [
    			'equipments' => $equipments
    		]), 150, 300, 'right_bottom'));
    	}
    }

    static function auth_login ($e, $token)
    {
    	if (Config::get('equipment.check_usetime_of_gemeter', false)) {
    		$user = O('user', ['token' => $token]);
	    	if ($user->id) {
	    		$key = "time_check_user_{$user->id}";
	    		Lab::set($key, NULL);
	    	}
    	}
    }

    static function layout_after_call ($e, $controller)
    {
    	if (Config::get('equipment.check_usetime_of_gemeter', false)) {
    		$controller->add_js('equipments:bind_check_time', FALSE);
    	}
    }

    // 【通用可配-南开大学】RQ170803 增加机主代开限制
    static function extra_form_validate($e, $object, $type, $form, $category = NULL) {
        $me = L('ME');
        if ($object->name() == 'equipment' && $type == 'use' 
        && $form['user_option'] != 'new_user' 
        && $form['user_id'] != $me->id) {
            $user = O('user', $form['user_id']);
            if (!Q("$user lab[atime>0]")->total_count()) {
                $form->set_error('user_id', I18N::T('equipments', '不允许代开未激活的课题组成员的使用记录!'));
            }
            
            if (Event::trigger('eq_record.judge.balance', $user, $form)
                && (L("gapperTokenOwner") || !JS::confirm(I18N::T('equipments', '使用者所在课题组余额不足，是否继续操作?')))
            ) {
                $form->set_error('user_id', I18N::T('equipments', '使用者所在课题组余额不足!'));
            }
        }
        return TRUE;
    }

    static function judge_balance($e, $user, $form) {
        if ($GLOBALS['preload']['people.multi_lab'] || !Module::is_installed('billing')) {
            $e->return_value = FALSE;
            return FALSE;
        }

        $equipment = O('equipment', $form['equipment_id']);
        $record = O('eq_record');
        $record->user = $user;
        $record->equipment = $equipment;
        $record->samples = $form['samples'];
        $record->dtstart = $form['dtstart'];
        $record->dtend = $form['dtend'];

        $charge = O('eq_charge');
        $charge->source = $record;
        $charge->user = $user;
        if ($form['user_option'] == 'new_user') {
            $lab = Equipments::default_lab();
        }
        else {
            $lab = Q("$user lab")->current();
        }
        $charge->lab = $lab;
        $charge->equipment = $record->equipment;

        if($form['charge_tags']){
            $tags = [];
            foreach((array)$form['charge_tags'] as $k=>$v) {
                if ($v['checked']=='on') {
                    $k = rawurldecode($k);
                    $tags[$k] = $v['value'];
                }
            }
            $charge->charge_tags = $tags;
        }

        $charge->charge_tags = $tags;

        if ($charge->user->id && $charge->user->is_allowed_to('管理使用', $charge->equipment) && !Lab::get('eq_charge.incharges_fee')) {
            $fee = 0;
        }
        else {
            $lua = new EQ_Charge_LUA($charge);

            $result = $lua->run(['fee']);
            $fee = $result['fee'];
        }

        if ($form['record_custom_charge'] == 'on') {
            $fee = $form['record_amount'];
        }
        
        // 如果是编辑，则需将老的计费扣除
        if ($form['record_id']) {
            $record_old = O('eq_record', $form['record_id']);
            $eq_charge = O('eq_charge', ['source' => $record_old]);
        }

        $account = Q("{$lab} billing_account[department={$equipment->billing_dept}]")->current();
        $balance = $account->balance + $eq_charge->amount;

        $e->return_value = ($balance < 0 || $balance - $fee < (float)$equipment->record_balance_required);
        return TRUE;
    }
    
    static function eq_record_list_columns_sorted ($e, $form, $columns, $type = '') {
        $sortable_columns = Config::get('equipments.eq_record.sortable_columns');
        foreach ($columns as $key => $value) {
            if(in_array($key, $sortable_columns)) {
                $value['sortable'] = true;
                $columns[$key] = $value;
           	}

        }
	    return true;
	}

	static function sort_str_factory($e, $form, $sort_str, $type) {
       	$sort_by = $_GET['sort'] ? : (Config::get('equipment.sort_reserv') ? 'reserv' : '');
        $sort_asc = $_GET['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';
		switch($sort_by){
			case 'user_name':
			   	$sort_str = ":sort(user_abbr {$sort_flag})";
			   	break;
			case 'equipment_name':
			   	$sort_str = ":sort(eq_abbr {$sort_flag})";
			   	break;
			case 'samples':
			   	$sort_str = ":sort(samples {$sort_flag})";
			   	break;
			case 'agent':
			   	$sort_str = ":sort(agent_abbr {$sort_flag})";
			   	break;
			default:
			   	break;
		}

		$e->return_value = $sort_str;
		return true;

	}

	static function eq_record_before_save($e, $record, $new_data)
	{
		//使用记录 冗余标签
		if ($new_data['equipment']->id) {
			$record->eq_abbr = $new_data['equipment']->name_abbr;
		}
		if ($new_data['user']->id) {
			$record->user_abbr = $new_data['user']->name_abbr;
		}
		if ($new_data['agent']->id) {
			$record->agent_abbr = $new_data['agent']->name_abbr;
		}
		

		//仪器增加使用中用户标签
		$equipment = $record->equipment;
		if (!$record->dtend) {
			if($record->agent->id) {
				$equipment->using_abbr = $record->agent->name_abbr;
			}
			else {
				$equipment->using_abbr = $record->user->name_abbr;
			}
		}
		else {
			if (Q("$equipment eq_record[dtend=0]")->total_count()) {
				$equipment->using_abbr = '';
			}
		}
		$equipment->save();
	}

    static function use_extra_display_none($e, $form_token){
        $form = Input::form();
        $display_columns = [];
        if ( $_SESSION[$form_token] ) {
            $setting = O('extra',['object_name'=>'equipment','object_id'=> $_SESSION[$form_token]['form']['equipment_id'],'type'=>'use']);
            if($setting->id){
                $valid_columns[-4] = '自定义表单';
                //获取自定义表单
                $extra = json_decode($setting->params_json, TRUE);
                foreach ($extra as $key => $fields) {
                    foreach ($fields as $name => $field) {
                        $display_columns[] = 'extra_setting_'.$name;
                    }
                }
            }
        }
        $e->return_value = $display_columns;
        return TRUE;
    }

    static function eq_record_export_list_csv($e, $eq_record, $data, $valid_columns) {
        $setting = O('extra',['object'=>$eq_record->equipment,'type'=>'use']);
        if($setting->id){
            $extra = json_decode($setting->params_json, TRUE);
            $extra_value = @json_decode(O('extra_value', ['object' => $eq_record])->values_json, TRUE) ?? [];
            foreach ($extra as $key => $fields) {
                foreach ($fields as $name => $field) {
                    if (array_key_exists('extra_setting_'.$name, $valid_columns))
                    {
                        switch ($field['type']) {
                            case Extra_Model::TYPE_CHECKBOX:
                                $value = [];
                                foreach ($extra_value[$name] as $key => $item) {
                                    if ($item == 'on') {
                                        $value[] = $key;
                                    }
                                }
                                $data[] = implode(",", $value) ?: '--';
                                break;
                            case Extra_Model::TYPE_RANGE:
                                $data[] = implode("~", $extra_value[$name]) ?: '--';
                                break;
                            case Extra_Model::TYPE_DATETIME:
                                $data[] = $extra_value[$name] ? date('Y-m-d H:i:s', $extra_value[$name]) : '--';
                                break;
                            case Extra_Model::TYPE_SELECT:
                                $data[] = $extra_value[$name] != -1 ? $extra_value[$name] : '--';
                                break;
                            default:
                                $data[] = $extra_value[$name] ?: '--';
                        }
                    }
                }
            }
        }
        $e->return_value = $data;
    }

    static function setup_index($e, $controller, $method, $params)
    {
        if ('dutys' != $params[0]) return;

        $me = L('ME');

        if (!$me->id || !$me->is_active() || !$me->access('管理所有内容')) {
            URI::redirect('error/401');
        }

        if ('dutys' == $params[0]) {
            Event::bind('equipments.primary.tab', 'Eq_Record::charges_tab');
            Event::bind('equipments.primary.content', 'Eq_Record::charges_tab_content', 100, 'dutys');
        }
    }

    static function charges_tab($e, $tabs)
    {
        $tabs->add_tab('dutys', [
            'url' => URI::url('!equipments/extra/dutys'),
            'title' => I18N::T('equipments', '所有仪器的值班老师数据'),
        ]);
    }

    static function charges_tab_content($e, $tabs)
    {
        self::charge_view_content($tabs);
    }

    static function charge_view_content($tabs, $obj = NULL)
    {

        $form = Lab::form(function (&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                } else {
                    $form['dtstart'] = strtotime('midnight', $form['dtstart']);
                }
                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                } else {
                    $form['dtend'] = strtotime('midnight', $form['dtend'] + 86400) - 1;
                }
                unset($old_form['date_filter']);
            }
        });

        //生成 session token， 为导出做准备
        $form_token = Session::temp_token('dutys_', 300);

        $params = [
            'form_token' => $form_token,
            'oid' => $obj->id,
        ];

        if ($obj->id) {
            $params['oname'] = $obj->name();
        }

        $links = [];
        $links['excel'] = [
            'text' => I18N::T('equipments', '导出Excel'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/dutys') .
                '" q-static="' . H(['type' => 'csv'] + $params) .
                '" class="button button_save middle"',
        ];
        $new_links = Event::trigger('eq_record.dutys_view.links', $links, $params);
        if ($new_links) $links = $new_links;

        $start = (int)$form['st'];
        $per_page = 20;

        //值班人、使用机时、送样机时、使用样品数、送样样品数、收费金额、服务用户数、服务课题组数，并可以按照时间等搜索项搜索显示数据。
        $sql = self::mksql($form);
        $token = [];
        $token['selector'] = $sql;
        $token['form'] = $form;
        $form['selector'] = $sql;
        //将搜索条件存入session
        $_SESSION[$form_token] = $token;

        $total = count(Database::factory()->query($sql)->rows());
        $sql .= " LIMIT {$start},{$per_page}";
        $dutys = Database::factory()->query($sql)->rows();

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $total,
        ]);

        $view_name = 'dutys/index';
        $tabs->content = V($view_name, [
            'dutys' => $dutys,
            'pagination' => $pagination,
            'form' => $form,
            'links' => $links,
            'obj' => $obj,
        ]);
    }

    static function mksql($form)
    {
        $where = " 1=1 ";

        if ($form['user_name']) {
            $name = trim($form['user_name']);
            $dutyTeachers = Q("user[name*={$name}]")->to_assoc('id', 'id');
            if (count($dutyTeachers))
                $where .= " AND s.duty_teacher_id IN( " . implode(',', $dutyTeachers) . ")";
            else
                $where .= " AND s.duty_teacher_id IN( '-1' )";
        } else {
            $where .= " AND s.duty_teacher_id > 0 ";
        }

        if ($form['dtstart']) {
            $dtstart = (int)$form['dtstart'];
            $where .= " AND s.dtstart>=$dtstart ";
        } else {
            unset($form['dtstart']);
        }

        if ($form['dtend']) {
            $dtend = (int)$form['dtend'] + 86399;
            $where .= " AND s.dtend<=$dtend ";
        } else {
            unset($form['dtend']);
        }

        $sql = "
            SELECT
                al.duty_teacher_id,
                SUM( record_sample_counts ) AS record_sample_counts,
                SUM( record_amount ) AS record_amount,
                SUM( record_used_dur ) AS record_used_dur,
                SUM( sample_dur ) AS sample_dur,
                SUM( sample_counts ) AS sample_counts,
                SUM( amount ) AS sample_amount,
                GROUP_CONCAT( al.user_id ) AS user_id 
            FROM
                (
                SELECT
                    s.duty_teacher_id,
                    0 AS record_sample_counts,
                    0 AS record_amount,
                    0 AS record_used_dur,
                    sum(
                    IF
                    ( s.dtend > 0, s.dtend - s.dtstart, 0 )) AS sample_dur,
                    sum( s.`count` ) AS sample_counts,
                    sum( c.amount ) AS amount,
                    GROUP_CONCAT( s.sender_id ) AS user_id 
                FROM
                    eq_sample s
                    LEFT JOIN eq_charge c ON c.source_id = s.id 
                    AND c.source_name = 'eq_sample' 
                WHERE
                    s.`status` = 5 
                    AND {$where}
                GROUP BY
                    s.duty_teacher_id UNION ALL
                SELECT
                    s.duty_teacher_id,
                    sum(s.samples) AS record_sample_counts,
                    sum(
                    IF
                    ( c.amount, c.amount, 0 )) AS record_amount,
                    SUM( IF ( s.dtend > 0, s.dtend - s.dtstart, 0 ) ) AS record_used_dur,
                    0 AS sample_dur,
                    0 AS sample_counts,
                    0 AS amount,
                    GROUP_CONCAT( s.user_id ) AS user_id 
                FROM
                    eq_record s
                    LEFT JOIN eq_charge c ON ( c.source_id = s.id AND c.source_name = 'eq_record' ) 
                WHERE
                    {$where}
                GROUP BY
                    s.duty_teacher_id 
                ) al 
            GROUP BY
                al.duty_teacher_id
	";

        return $sql;
    }

}
