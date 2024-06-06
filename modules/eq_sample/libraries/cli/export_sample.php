<?php

class CLI_Export_Sample {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns = json_decode($params[2], true);
		$samples = Q($selector);

        $excel = new Excel($params[1]);
        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        $excel->write(array_values($valid_columns));

        foreach ($samples as $sample) {
            $equipment = $sample->equipment;
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
               $data[] = $equipment->group->name;
            }
            if (array_key_exists('user', $valid_columns)) {
                $data[] = $sample->sender->name;
            }
            if (array_key_exists('login_token', $valid_columns)) {
                list($t, $b) = Auth::parse_token($sample->sender->token);
                $data[] = $t;
            }
            if (array_key_exists('user_email', $valid_columns)) {
                $data[] = $sample->sender->email;
            }
            if (array_key_exists('user_phone', $valid_columns)) {
                $data[] = $sample->sender->phone;
            }
            if (array_key_exists('lab', $valid_columns)) {
                $data[] = $sample->lab->name;
            }
            if (array_key_exists('phone', $valid_columns)) {
                $data[] = $sample->phone;
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
                $records = Q("$sample eq_record");
                if ($records->total_count()) {
                    $dtstarts = [];
                    foreach ($records as $record) {
                        $dtstarts[] = $record->dtstart ? Date::format($record->dtstart, 'Y/m/d H:i:s') : '--';
                    }
                    $data[] = implode('; ', $dtstarts);
                }else{
                    $data[] = $sample->dtstart ? Date::format($sample->dtstart, 'Y/m/d H:i:s') : '--';
                }
            }
            if (array_key_exists('dtend', $valid_columns)) {
                $records = Q("$sample eq_record");
                if ($records->total_count()) {
                    $dtends = [];
                    foreach ($records as $record) {
                        $dtends[] = $record->dtend ? Date::format($record->dtend, 'Y/m/d H:i:s') : '--';
                    }
                    $data[] = implode('; ', $dtends);
                }else{
                    $data[] = $sample->dtend ? Date::format($sample->dtend, 'Y/m/d H:i:s') : '--';
                }
            }
            if (array_key_exists('dtpickup', $valid_columns)) {
                $data[] = $sample->dtpickup ? Date::format($sample->dtpickup, 'Y/m/d H:i:s') : '--';
            }
            
            // Clh 20191734 西安交通大学定制字段没有输出导致表格错行
            if (array_key_exists('reason', $valid_columns)) {
                $charge = O('eq_charge', ['source' => $sample]);
                $charge_reason = Q("charge_reason[charge=$charge]:sort(ctime D)")->current();
                $data[] = $charge_reason->reason ?: '--';
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
            if (array_key_exists('material_amount', $valid_columns)) {
                $charge = O('eq_charge', ['source'=> $sample]);
                if ($charge->id && $charge->material_amount) {
                    $val = $charge->material_amount;
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
            if (array_key_exists('duty_teacher', $valid_columns)) {
                $data[] = $sample->duty_teacher->id ? $sample->duty_teacher->name : '--';
            }
            if (array_key_exists('site', $valid_columns)) {
                $data[] = Config::get('site.map')[$sample->equipment->site];
            }

            if (Lab::get('eq_sample.response_time')) {
                if (array_key_exists('ctime', $valid_columns)) {
                    $data[] = $sample->ctime ? Date::format($sample->ctime, 'Y/m/d H:i:s') : '--';
                }

                if (array_key_exists('response_time', $valid_columns)) {
                    $response_time = (string)V('eq_sample:samples_table/data/response_time', ['sample' => $sample]);
                    $response_time = empty($response_time) || $response_time == '0' ? $response_time = '0小时' : $response_time;
                    $data[] = $response_time;
                }
            }

            if(array_key_exists('university', $valid_columns)) {
                $data[] =Config::get('university.list')[$sample->source_name];
            }

            //定制的输出项
            $data_extra = Event::trigger('eq_sample.export_list_csv', $sample, $data, $valid_columns);
            if(is_array($data_extra)) $data = $data_extra;

            $excel->write($data);
        }

        $excel->save();
    }
}
