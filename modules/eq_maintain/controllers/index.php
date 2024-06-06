<?php

class Index_Controller extends Layout_Controller {

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
                        $data[] = EQ_Maintain_Model::$type[$maintain->type] ? : '维修';
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
                        $data[] = Config::get('eq_maintain.fee_source')[$maintain->fee_source] ? : '学校资助';
                    }
                    if (array_key_exists('rate', $valid_columns)) {
                        $data[] = $maintain->rate . '%';
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

class Index_AJAX_Controller extends AJAX_Controller {

    function index_output_click() {
        $form = Input::form();
        $form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            JS::alert(I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return FALSE;
        }
        $type = $form['type'];
        $columns = Config::get('eq_maintain.export_columns.eq_maintain');
        switch ($type) {
            case 'csv':
                $title = I18N::T('equipments', '请选择要导出CSV的列');
                $query = $_SESSION[$form_token]['selector'];
                $total_count = Q($query)->total_count();
                if($total_count > 8000){
                    $description = I18N::T('equipments', '数据量过多, 可能导致导出失败, 请缩小搜索范围!');
                }
                break;
            case 'print':
                $title = I18N::T('equipments', '请选择要打印的列');
                break;
        }
        $view = V('eq_maintain:report/form', [
            'action' => 'index',
            'description' => $description,
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
        ]);
        JS::dialog($view, ['title' => $title]);
    }

    function index_add_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $equipment = O('equipment', $form['id']);

        if(!$me->is_allowed_to('修改维修记录', $equipment)){
            return;
        }

        $maintain = O('eq_maintain');
        $maintain->equipment = $equipment;

        $view = V('edit', [
            'maintain' => $maintain,
            'form' => $form,
        ]);

        JS::dialog($view, [
            'title' => I18N::T('eq_maintain', '添加维修记录')
        ]);
    }

    function index_edit_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $maintain = O('eq_maintain', $form['id']);

        if(!$me->is_allowed_to('修改维修记录', $maintain->equipment)) {
            return;
        }

        $view = V('edit', [
            'maintain' => $maintain,
            'form' => $form,
        ]);

        JS::dialog($view, [
            'title' => I18N::T('eq_maintain', '编辑维修记录')
        ]);
    }

    function index_delete_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $maintain = O('eq_maintain', $form['id']);

        if(!$me->is_allowed_to('修改维修记录', $maintain->equipment)) {
            return;
        }
        
        if (!JS::confirm(I18N::T('eq_maintain', '您确定删除该记录吗?'))) return;
        Log::add(strtr('[eq_maintain] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的维修记录[%maintain_id]', 
            [
                '%user_name'=> $me->name, 
                '%user_id'=> $me->id, 
                '%equipment_name'=> $maintain->equipment->name, 
                '%equipment_id'=> $maintain->equipment->id, 
                '%maintain_id'=> $maintain->id
            ]), 'journal');

        $maintain->delete();
        JS::refresh();
    }

    function index_edit_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $form->validate('maintain_id', 'is_numeric', I18N::T('equipments', '操作有误!'));
        $maintain = O('eq_maintain', $form['maintain_id']);
        $equipment = O('equipment', $form['equipment_id']);
        $action = $maintain->id ? '修改' : '添加' ;

        if ($maintain->id && $form['submit'] == 'delete') {
            if (!JS::confirm(I18N::T('equipments', '您确定删除该记录吗?'))) return;
            if ($me->is_allowed_to('修改维修记录', $equipment)) {
                Log::add(strtr('[eq_maintain] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的维修记录[%maintain_id]', 
                    [
                        '%user_name'=> $me->name, 
                        '%user_id'=> $me->id, 
                        '%equipment_name'=> $maintain->equipment->name, 
                        '%equipment_id'=> $maintain->equipment->id, 
                        '%maintain_id'=> $maintain->id
                    ]), 'journal');

                $maintain->delete();
                JS::refresh();
            }
            else {
                JS::alert(I18N::T('eq_maintain', '您无权删除维修记录!'));
            }
            return;
        }
        
        if (!$maintain->id) $maintain->equipment = $equipment;

        $form
        ->validate('amount', 'number(>=0)', I18N::T('eq_maintain', '维修金额需要大于等于零！'));

        Event::trigger('validate.eq_maintain.extra.fields', $form);

        if ($form->no_error) {
            $maintain->time = $form['time'] ? : Date::time();
            $maintain->amount = (double)$form['amount'];
            $maintain->description = trim($form['description']);
            Event::trigger('set.eq_maintain.extra.fields', $form, $maintain);
            $maintain->save();

            Log::add(strtr('[equipments] %user_name[%user_id]%action了%equipment_name[%equipment_id]仪器的使用记录[%maintain_id]', 
                [
                    '%user_name'=> $me->name, 
                    '%user_id'=> $me->id, 
                    '%action'=> $action,
                    '%equipment_name'=> $equipment->name, 
                    '%equipment_id'=> $equipment->id, 
                    '%maintain_id'=> $maintain->id
                ]), 'journal');

            JS::refresh();
        }
        else {
            if($me->is_allowed_to('修改维修记录', $equipment)){
                $view = V('edit', [
                    'maintain' => $maintain,
                    'form' => $form,
                ]);

                JS::dialog($view, [
                    'title' => I18N::T('eq_maintain', '编辑维修记录')
                ]);
            }
        }
    }

}
