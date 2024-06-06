<?php

class Keep_Controller extends Layout_Controller {

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
        $valid_columns = Config::get('eq_maintain.export_columns.eq_keep');
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
        $keeps = Q($selector);
        $this->layout = V('eq_maintain:keep/print', [
            'keeps' => $keeps,
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
        $valid_columns = Config::get('eq_maintain.export_columns.eq_keep');
        $visible_columns = $form['columns'] ? : $form['@columns'];

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        $selector = $form['selector'];
        $keeps = Q($selector);

        $csv = new CSV('php://output', 'w');
        /* 记录日志 */
        $me = L('ME');

        Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了仪器保养记录', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

        $csv->write(I18N::T('equipments',$valid_columns));
        if ($keeps->total_count() > 0) {

            $start = 0;
            $per_page = 100;
            $amount = 0;
            $m_amount = 0;
            $m_fund = 0;
            $m_income = 0;
            $m_outlay = 0;
            $i = [];

            while (true) {
                $pp_keeps = $keeps->limit($start, $per_page);
                if ($pp_keeps->length() == 0) break;
                foreach ($pp_keeps as $keep) {
                    $equipment = $keep->equipment;
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
                        $data[] = Number::fill(H($keep->id), 6);
                        $i['maintain_ref_no'] = 1;
                    }
                    if (array_key_exists('time', $valid_columns)) {
                        $data[] = Date('Y-m-d H:i:s', $keep->time);
                        $i['time'] = 1;
                    }
                    if (array_key_exists('amount', $valid_columns)) {
                        $data[] = Number::currency($keep->amount);
                    }
                    if (array_key_exists('rate', $valid_columns)) {
                        $data[] = $keep->rate . '%';
                    }
                    if (array_key_exists('description', $valid_columns)) {
                        $data[] = $keep->description;
                    }
                    $csv->write($data);
                    $amount += $keep->amount;
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
            $csv->write($data);
        }
        $csv->close();
    }
    
}

class Keep_AJAX_Controller extends AJAX_Controller {

    function index_output_click() {
        $form = Input::form();
        $form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            JS::alert(I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return FALSE;
        }
        $type = $form['type'];
        $columns = Config::get('eq_maintain.export_columns.eq_keep');
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
            'action' => 'keep',
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

        $keep = O('eq_keep');
        $keep->equipment = $equipment;

        $view = V('keep/edit', [
            'keep' => $keep,
            'form' => $form,
        ]);

        JS::dialog($view, [
            'title' => I18N::T('eq_maintain', '添加保养记录')
        ]);
    }

    function index_edit_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $keep = O('eq_keep', $form['id']);

        if(!$me->is_allowed_to('修改维修记录', $keep->equipment)) {
            return;
        }

        $view = V('keep/edit', [
            'keep' => $keep,
            'form' => $form,
        ]);

        JS::dialog($view, [
            'title' => I18N::T('eq_maintain', '编辑保养记录')
        ]);
    }

    function index_delete_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $keep = O('eq_keep', $form['id']);

        if (!$me->is_allowed_to('修改维修记录', $keep->equipment)) return;
        
        if (!JS::confirm(I18N::T('equipments', '您确定删除该记录吗?'))) return;
        if ($keep->delete()) {
            Log::add(
            strtr('[eq_maintain] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的保养记录[%keep_id]', [
                '%user_name'=> $me->name, 
                '%user_id'=> $me->id, 
                '%equipment_name'=> $keep->equipment->name, 
                '%equipment_id'=> $keep->equipment->id, 
                '%keep_id'=> $keep->id
            ]), 'journal');
        }

        $keep->delete();
        JS::refresh();
    }

    function index_edit_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $form->validate('keep_id', 'is_numeric', I18N::T('equipments', '操作有误!'));
        $keep = O('eq_keep', $form['keep_id']);
        $equipment = O('equipment', $form['equipment_id']);
        $action = $keep->id ? '修改' : '添加' ;

        if ($keep->id && $form['submit'] == 'delete') {
            if (!JS::confirm(I18N::T('equipments', '您确定删除该记录吗?'))) return;
            if ($me->is_allowed_to('修改维修记录', $equipment)) {
                Log::add(strtr('[eq_maintain] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的保养记录[%keep_id]', 
                    [
                        '%user_name'=> $me->name, 
                        '%user_id'=> $me->id, 
                        '%equipment_name'=> $keep->equipment->name, 
                        '%equipment_id'=> $keep->equipment->id, 
                        '%keep_id'=> $keep->id
                    ]), 'journal');

                $keep->delete();
                JS::refresh();
            }
            else {
                JS::alert(I18N::T('equipments', '您无权删除保养记录!'));
            }
            return;
        }
        
        if (!$keep->id) $keep->equipment = $equipment;

        $form
        ->validate('rate', 'number(>=0)', I18N::T('eq_maintain', '学校资助比例需要大于等于零！'))
        ->validate('amount', 'number(>=0)', I18N::T('eq_maintain', '保养金额需要大于等于零！'))
        ->validate('description', 'not_empty', I18N::T('eq_maintain', '请填写描述'));

        if ($form->no_error) {
            $keep->time = $form['time'] ? : Date::time();
            $keep->rate = $form['rate'];
            $keep->amount = (double)$form['amount'];
            $keep->description = trim($form['description']);
            $keep->save();

            Log::add(
            strtr('[equipments] %user_name[%user_id]%action了%equipment_name[%equipment_id]仪器的使用记录[%keep_id]', [
                '%user_name'=> $me->name, 
                '%user_id'=> $me->id, 
                '%action'=> $action,
                '%equipment_name'=> $equipment->name, 
                '%equipment_id'=> $equipment->id, 
                '%keep_id'=> $keep->id
            ]), 'journal');

            JS::refresh();
        }
        else {
            if($me->is_allowed_to('修改维修记录', $equipment)){
                $view = V('keep/edit', [
                    'keep' => $keep,
                    'form' => $form,
                ]);

                JS::dialog($view, [
                    'title' => I18N::T('equipments', '编辑保养记录')
                ]);
            }
        }
    }

}
