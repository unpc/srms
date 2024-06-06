<?php

class CLI_Export_Nrii_Record {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $columns = json_decode($params[2], true);

        $excel = new Excel($params[1]);
        $excel->write(array_values($columns));
        $nrii_records = Q($selector);
        
        $columns = Config::get('columns.export_columns.record');
        
        foreach($nrii_records as $nrii_record) {
            $data = [];
            foreach ($columns as $key => $val) {
                switch ($key) {
                    /* case 'service_time':
                        $data[] =  $nrii_record->service_time ? date('Y/m/d H', $nrii_record->service_time) : '--';
                        break; */
                    case 'service_way':
                        $sw_string = [];
                        $sw = explode(',', $nrii_record->service_way);
                        foreach ($sw as $s) {
                            $sw_string[] = Nrii_Record_Model::$service_way[$s];
                        }
                        $data[] = join(',', $sw_string);
                        break;
                    case 'start_time':
                        $data[] =  $nrii_record->start_time ? date('Y/m/d H', $nrii_record->start_time) : '--';
                        break;
                    case 'end_time':
                        $data[] =  $nrii_record->end_time ? date('Y/m/d H', $nrii_record->end_time) : '--';
                        break;
                    case 'service_way':
                        $data[] = H(Nrii_Record_Model::$service_way[$nrii_record->service_way] ? : '--');
                        break;
                    case 'subject_income':
                        $si_string = [];
                        $si = explode(',', $nrii_record->subject_income);
                        foreach ($si as $s) {
                            $si_string[] = Nrii_Record_Model::$subject_income[$s];
                        }
                        $data[] = join(',', $si_string);
                        break;
                    case 'subject_area':
                        $data[] = $nrii_record->subject_area ? join(',', json_decode($nrii_record->subject_area, TRUE)) : '--';
                        break;
                    case 'service_type':
                        $data[] = H(Nrii_Record_Model::$service_types[$nrii_record->service_type] ? : '--');
                        break;
                    case 'service_direction':
                        $data[] = H(Nrii_Record_Model::$service_directions[$nrii_record->service_direction] ? : '--');
                        break;
                    case 'address_type':
                        $data[] = H(Nrii_Record_Model::$address_types[$nrii_record->address_type] ? : '--');
                        break;
                    case 'sign_agreement':
                        $data[] = H(Nrii_Record_Model::$sign_agreements[$nrii_record->sign_agreement] ? : '--');
                        break;
                    case 'comment':
                        $data[] = H((Nrii_Record_Model::$comment[$nrii_record->comment] . ' - ' . $nrii_record->comment2) ? : '--');
                        break;
                    default:
                        $data[] = $nrii_record->$key ? : '--';
                        break;
                }
            }
            // error_log(print_r($data, 1));exit(0);
            $excel->write($data);
        }

        $excel->save();
    }
}
