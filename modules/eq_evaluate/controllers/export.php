<?php

class Export_Controller extends Layout_Controller {

    function index() {
        $form = Input::form();
        $form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type = $form['type'];
        if ($type == 'print') {
            $this->_print($form_token);
        }
        elseif ($type == 'csv') {
            $this->_csv($form);
        }
        else {
            URI::redirect('error/401');
        }
    }
    private function _print($form_token) {
        $valid_columns = Config::get('eq_evaluate.export_columns.eq_evaluate');
        $visible_columns = Input::form('columns');

        //如果不存在columns
        if (!count($visible_columns)) {
            $visible_columns = (array) $_SESSION[$form_token]['@columns'];
        }

        foreach ($valid_columns as $p => $p_name) {
            if ($p[0] == '-' || !isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        $selector = $_SESSION[$form_token]['selector'];
        $form_submit = $_SESSION[$form_token]['form'];
        $evaluates = Q($selector);
        $this->layout = V('eq_evaluate:report/print', [
            'evaluates' => $evaluates,
            'valid_columns' => $valid_columns,
            'selector' => $selector,
            'form_token' => $form_token,
            'form' => $form_submit,
        ]);
    }

    private function _csv($form) {
        $form_token = $form['form_token'];
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }
        $form = $_SESSION[$form_token] = $new_form + $old_form;
        $valid_columns = Config::get('eq_evaluate.export_columns.eq_evaluate');
        $visible_columns = $form['columns'] ? : $form['@columns'];
 
        if(array_key_exists('use_duration', $visible_columns)) {
            $sum_dtstart = 0;
            $sum_dtend = 0;
            $sum_sample_count = 0;
            if(array_key_exists('fudan_table_summary', $visible_columns)) {
                $has_fudan_summary = true;
                unset($visible_columns['fudan_table_summary']);
            }
        }

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        $selector = $form['selector'];
        $evaluates = Q($selector);

        $csv = new CSV('php://output', 'w');
        /* 记录日志 */
        $me = L('ME');

        Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了仪器的评价记录', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

        $csv->write(I18N::T('equipments',$valid_columns));
        if ($evaluates->total_count() > 0) {

            $start = 0;
            $per_page = 100;

            while (1) {
                $pp_evaluates = $evaluates->limit($start, $per_page);
                if ($pp_evaluates->length() == 0) break;
                foreach ($pp_evaluates as $evaluate) {
                    $equipment = $evaluate->equipment;
                    $user = $evaluate->user;
                    $data = [];

                    if (array_key_exists('equipment', $valid_columns)) {
                        $data[] = H($equipment->name);
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
                    if (array_key_exists('user', $valid_columns)) {
                        $data[] = H($user->name);
                    }
                    if (array_key_exists('login_token', $valid_columns)) {
                        list($t, $b) = Auth::parse_token($user->token);
                        $data[] = H($t);
                    }
                    if (array_key_exists('lab', $valid_columns)) {
                        $labs = Q("$user lab")->to_assoc('id', 'name');
                        $data[] = H(join(',', $labs));
                    }
                    if (array_key_exists('user_group', $valid_columns)) {
                        $data[] = H($user->group->name);
                    }
                    if (array_key_exists('evaluate_ref_no', $valid_columns)) {
                        $data[] = Number::fill(H($evaluate->id),6);
                    }
                    if (array_key_exists('score', $valid_columns)) {
                        $data[] = H(Config::get('eq_evaluate')['rate.tip'][$evaluate->score - 1]);
                    }
                    if (array_key_exists('content', $valid_columns)) {
                        $data[] = H($evaluate->content);
                    }
                    if (array_key_exists('duty_teacher', $valid_columns)) {
                        $record = O('eq_record', ['evaluate' => $evaluate]);
                        $data[] = H($record->duty_teacher->id ? $record->duty_teacher->name : '--');
                    }
                    
                    $data = new ArrayObject($data);
                    Event::trigger('extra.export.columns', $valid_columns, $data, $evaluate);
                    $csv->write((array)$data);
                }
                $start += $per_page;
            }

        }
        $csv->close();
    }
}
