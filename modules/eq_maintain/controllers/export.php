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
        $valid_columns = Config::get('eq_maintain.export_columns.eq_maintain');
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

        if (array_key_exists('description', $valid_columns)) {
            unset($valid_columns['description']);
            $valid_columns['description'] = '描述';
        }

        $selector = $_SESSION[$form_token]['selector'];
        $form_submit = $_SESSION[$form_token]['form'];
        $maintains = Q($selector);
        $this->layout = V('eq_maintain:report/print', [
            'maintains' => $maintains,
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
        $valid_columns = Config::get('eq_maintain.export_columns.eq_maintain');
        $visible_columns = $form['columns'] ? : $form['@columns'];

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        if (array_key_exists('description', $valid_columns)) {
            unset($valid_columns['description']);
            $valid_columns['description'] = '描述';
        }

        $selector = $form['selector'];
        $maintains = Q($selector);

        $csv = new CSV('php://output', 'w');
        /* 记录日志 */
        $me = L('ME');

        Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了仪器的维修记录', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

        $csv->write(I18N::T('equipments',$valid_columns));
        if ($maintains->total_count() > 0) {

            $start = 0;
            $per_page = 100;
            $amount = 0;
            $m_amount = 0;
            $m_fund = 0;
            $m_income = 0;
            $m_outlay = 0;
            $i = [];

            while (1) {
                $pp_maintains = $maintains->limit($start, $per_page);
                if ($pp_maintains->length() == 0) break;
                foreach ($pp_maintains as $maintain) {
                    $equipment = $maintain->equipment;
                    $data = [];

                    if (array_key_exists('equipment', $valid_columns)) {
                        $data[] = H($equipment->name);
                        $i['equipment'] = 1;
                    }
                    if (array_key_exists('eq_ref_no', $valid_columns)) {
                        $data[] = $equipment->ref_no;
                        $i['eq_ref_no'] = 1;
                    }
                    if (array_key_exists('eq_cf_id', $valid_columns)) {
                        $data[] = $equipment->id;
                        $i['eq_cf_id'] = 1;
                    }
                    if (array_key_exists('eq_group', $valid_columns)) {
                        $data[] = H($equipment->group->name);
                        $i['eq_group'] = 1;
                    }
                    if (array_key_exists('maintain_ref_no', $valid_columns)) {
                        $data[] = Number::fill(H($maintain->id), 6);
                        $i['maintain_ref_no'] = 1;
                    }
                    if (array_key_exists('time', $valid_columns)) {
                        $data[] = Date('Y-m-d H:i:s', $maintain->time);
                        $i['time'] = 1;
                    }
                    if (array_key_exists('type', $valid_columns)) {
                        $data[] = EQ_Maintain_Model::$type[$maintain->type];
                        $i['type'] = 1;
                    }
                    if (array_key_exists('amount', $valid_columns)) {
                        $data[] = Number::currency($maintain->amount);
                    }
                    if (array_key_exists('m_amount', $valid_columns)) {
                        $data[] = Number::currency($maintain->m_amount);
                    }
                    if (array_key_exists('m_fund', $valid_columns)) {
                        $data[] = Number::currency($maintain->m_fund);
                    }
                    if (array_key_exists('m_income', $valid_columns)) {
                        $data[] = Number::currency($maintain->m_income);
                    }
                    if (array_key_exists('m_outlay', $valid_columns)) {
                        $data[] = Number::currency($maintain->m_outlay);
                    }
                    if (array_key_exists('description', $valid_columns)) {
                        $data[] = $maintain->description;
                    }
                    if (array_key_exists('fee_source', $valid_columns)) {
                        $data[] = Config::get('eq_maintain.fee_source')[$maintain->fee_source];
                    }
                    if (array_key_exists('content', $valid_columns)) {
                        $data[] = $maintain->content;
                    }
                    $csv->write($data);
                    $amount += $maintain->amount;
                    $m_amount += $maintain->m_amount;
                    $m_fund += $maintain->m_fund;
                    $m_income += $maintain->m_income;
                    $m_outlay += $maintain->m_outlay;
                }
                $start += $per_page;
            }

            $data = [];
            $data[] = '总计';
            for ($j=0; $j < count($i) - 1; $j++) { 
                $data[] = '';
            }
            if (array_key_exists('amount', $valid_columns)) {
                $data[] = Number::currency($amount);
            }
            if (array_key_exists('m_amount', $valid_columns)) {
                $data[] = Number::currency($m_amount);
            }
            if (array_key_exists('m_fund', $valid_columns)) {
                $data[] = Number::currency($m_fund);
            }
            if (array_key_exists('m_income', $valid_columns)) {
                $data[] = Number::currency($m_income);
            }
            if (array_key_exists('m_outlay', $valid_columns)) {
                $data[] = Number::currency($m_outlay);
            }
            $csv->write($data);
        }
        $csv->close();
    }
}
