<?php

class CLI_Export_Records {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $current_user = $params[1];
        $valid_columns = json_decode($params[3], true);
        $records = Q($selector);

        $start = 0;
        $per_page = 100;

        $excel = new Excel($params[2], 'xls');
        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        while (1) {
            $pp_records = $records->limit($start, $per_page);
            if ($pp_records->length() == 0) {
                break;
            }
            foreach ($pp_records as $record) {
                $dtstart = Date::format($record->dtstart);
                $dtend = !$record->dtend ? I18N::T('equipments', '使用中') : Date::format($record->dtend);
                $duration = !$record->dtend ? I18N::T('equipments', '使用中') : Date::format_duration($record->dtstart, $record->dtend, 'i');

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

                $equipment = $record->equipment;
                $user = $record->user;
                $description = Event::trigger('eq_record.description', $record, $current_user);
                if (count($description) > 0) {
                    $description = implode(';', $description);
                }

                $data = [];

                if (array_key_exists('equipment', $valid_columns)) {
                    $data[] = T($equipment->name) ? T($equipment->name.Event::trigger('extra.equipment.name', $equipment)) : '';
                }
                if (array_key_exists('eq_ref_no', $valid_columns)) {
                    $data[] = $equipment->ref_no;
                }
                if (array_key_exists('eq_cf_id', $valid_columns)) {
                    $data[] = $equipment->id;
                }
                if (array_key_exists('eq_group', $valid_columns)) {
                    $data[] = H($equipment->group->name);
                }
                if (array_key_exists('eq_cat', $valid_columns)) {
                    $cate_root = Tag_Model::root('equipment');
                    $cate = Q($record->equipment. " tag_equipment[id!=".$cate_root->id."]")->to_assoc('id','name');
                    $cate_str = implode(',',$cate);
                    $data[] = H($cate_str);
                }
                if (array_key_exists('eq_incharge', $valid_columns)) {
                    $incharges = Q($record->equipment . " user.incharge")->to_assoc('id','name');
                    $incharge_str = implode(',',$incharges);
                    error_log($incharge_str);
                    $data[] = H($incharge_str);
                }
                if (array_key_exists('user', $valid_columns)) {
                    $data[] = H($user->name);
                }
                if (array_key_exists('login_token', $valid_columns)) {
                    list($t, $b) = Auth::parse_token($user->token);
                    $data[] = H($t);
                }
                if (array_key_exists('member_type', $valid_columns)) {
                    $curr_type = $user->member_type;
                    $members = User_Model::get_members();
                    foreach ($members as $key => $value) {
                        foreach ($value as $k => $v) {
                            if ($k == $curr_type) {
                                $user_role = $key;
                                $user_member = $v;
                                break;
                            }
                        }
                    }
                    $data[] = I18N::T('people', $user_role) . ' - ' . I18N::T('people', $user_member) ? :'--';
                }
                if (array_key_exists('lab', $valid_columns)) {
                    $labs = Q("$user lab")->to_assoc('id', 'name');
                    $data[] = H(join(', ', $labs));
                }
                if (array_key_exists('user_group', $valid_columns)) {
                    $data[] = H($user->group->name);
                }
                if (array_key_exists('record_ref_no', $valid_columns)) {
                    $data[] = Number::fill(H($record->id),6);
                }
                if (array_key_exists('date', $valid_columns)) {
                    $data[] = $record->get_date() ? : '';
                    if ($record->get_date() && $record->dtend) {
                        $sum_dtend += $record->dtend;
                        $sum_dtstart += $record->dtstart;
                    }  
                }
                if (array_key_exists('total_time', $valid_columns)) {
                    $data[] = $record->get_total_time();
                }
                if (array_key_exists('total_time_hour', $valid_columns)) {
                    $data[] = $record->get_total_time_hour();
                }

                //复旦高分子定制的输出项
                $data_fudangao = Event::trigger('eq_record_lab_use.export_list_csv', $record, $data, $valid_columns);
                if(is_array($data_fudangao)) $data = $data_fudangao;

                if (array_key_exists('samples', $valid_columns)) {
                    $data[] = $samples = $record->samples;
                    $sum_sample_count += $samples;
                }

                if (Module::is_installed('eq_charge')) {
                    if (array_key_exists('charge_amount', $valid_columns)) {
                        $charge = O("eq_charge", ['source' => $record]);
                        $amount = $charge->amount;
                        $reserv_charge = O('eq_charge', ['source' => $record->reserv]);
                        if ($reserv_charge->id) {
                            $amount += $reserv_charge->amount;
                        }

                        $data[] = $amount ? : 0;
                    }
                }

                if (array_key_exists('material_amount', $valid_columns)) {
                    if ($record->reserv->id) {
                        $charge = O("eq_charge", ['source' => $record->reserv]);
                    }else {
                        $charge = O("eq_charge", ['source' => $record]);
                    }
                    $amount = $charge->material_amount;
                    $data[] = $amount ? : 0;
                }

                if (array_key_exists('agent', $valid_columns)) {
                    $data[] = $record->agent->id ? $record->agent->name : '';
                }
                if (array_key_exists('status', $valid_columns)) {
                    $data[] = $status;
                }
                if (array_key_exists('description', $valid_columns)) {
                    $data[] = join("\n", array_map(function($v) {
                        return strip_tags($v);
                    }, (array) Event::trigger('eq_record.notes_csv', $record, $current_user)));
                }
                if (Config::get('equipment.enable_use_type')) {
                    if (array_key_exists('use_type', $valid_columns)) {
                        $data[] = EQ_Record_Model::$use_type[$record->use_type];
                    }
                    if (array_key_exists('use_type_desc', $valid_columns)) {
                        $data[] = $record->use_type_desc;
                    }
                }
                if (Config::get('eq_record.duty_teacher')) {
                    if (array_key_exists('duty_teacher', $valid_columns)) {
                        $data[] = $record->duty_teacher->name;
                    }
                }

                if (Module::is_installed('eq_evaluate_user')) {
                    if (array_key_exists('attitude', $valid_columns)) {
                        $data[] = Config::get('eq_evaluate_user')['rate.tip'][$record->evaluate_user->attitude - 1];
                    }
                    if (array_key_exists('attitude_feedback', $valid_columns)) {
                        $data[] = $record->evaluate_user->attitude_feedback;
                    }
                    if (array_key_exists('proficiency', $valid_columns)) {
                        $data[] = Config::get('eq_evaluate_user')['rate.tip'][$record->evaluate_user->proficiency - 1];
                    }
                    if (array_key_exists('proficiency_feedback', $valid_columns)) {
                        $data[] = $record->evaluate_user->proficiency_feedback;
                    }
                    if (array_key_exists('cleanliness', $valid_columns)) {
                        $data[] = Config::get('eq_evaluate_user')['rate.tip'][$record->evaluate_user->cleanliness - 1];
                    }
                    if (array_key_exists('cleanliness_feedback', $valid_columns)) {
                        $data[] = $record->evaluate_user->cleanliness_feedback;
                    }
                }
                if(array_key_exists('university', $valid_columns)) {
                    $data[] = Config::get('university.list')[$record->source_name];
                }

                if (array_key_exists('site', $valid_columns)) {
                    $data[] = Config::get('site.map')[$record->equipment->site];
                }

                $data_custom = Event::trigger('eq_record.export_list_csv', $record, [], $valid_columns);
                if(is_array($data_custom)) foreach ($data_custom as $key => $value){
                    $data[] = $value;
                }
                $excel->write($data, 100, count($data) - 1);
            }
            $start += $per_page;
        }
        Event::trigger('eq_record_lab_use.export_list_csv_sum', $records, $valid_columns, $excel, $sum_sample_count);

        $excel->save();
    }
}
