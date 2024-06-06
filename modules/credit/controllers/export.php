<?php

class Export_Controller extends Layout_Controller
{
    public function index()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('credit', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type = $form['type'];
        if ($type == 'print') {
            $this->_print($form);
        } elseif ($type == 'csv') {
            $this->_csv($form);
        } else {
            URI::redirect('error/401');
        }
    }

    private function _print($form)
    {
        $form_token      = $form['form_token'];
        $valid_columns   = Config::get('credit.export_columns.credit');
        $visible_columns = Input::form('columns');

        // 如果不存在columns
        if (!count($visible_columns)) {
            $visible_columns = (array) $_SESSION[$form_token]['@columns'];
        }

        foreach ($valid_columns as $p => $p_name) {
            if ($p[0] == '-' || !isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        $selector     = $_SESSION[$form_token]['selector'];
        $form_submit  = $_SESSION[$form_token]['form'];
        $credits      = Q($selector);
        $this->layout = V('credit:report/record_print', [
            'credits'       => $credits,
            'valid_columns' => $valid_columns,
            'selector'      => $selector,
            'form_token'    => $form_token,
            'form_submit'   => $form_submit,
        ]);
    }

    private function _csv($form)
    {
        $form_token = $form['form_token'];
        $old_form   = (array) $_SESSION[$form_token];
        $new_form   = (array) $form;

        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $valid_columns   = Config::get('credit.export_columns.credit');
        $visible_columns = $form['columns'] ?: $form['@columns'];

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }
        $selector = $form['selector'];
        $credits  = Q($selector);

        $me = L('ME');

        Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了系统用户的信用分列表', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');

        //$csv->write(I18N::T('equipments',$valid_columns));
        if ($credits->total_count() > 0) {
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_credit export ';
            $cmd .= "'" . $selector . "' '" . $me->id . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "' >/dev/null 2>&1 &";
            exec($cmd, $output);
        }
        //$csv->close();
    }

    public function credit_records()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('credit', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type = $form['type'];
        if ($type == 'print') {
            $this->_credit_records_print($form);
        } elseif ($type == 'csv') {
            $this->_credit_records_csv($form);
        } else {
            URI::redirect('error/401');
        }
    }

    private function _credit_records_print($form)
    {
        $form_token      = $form['form_token'];
        $valid_columns   = Config::get('credit.export_credit_record_columns.credit');
        $visible_columns = Input::form('columns');

        // 如果不存在columns
        if (!count($visible_columns)) {
            $visible_columns = (array) $_SESSION[$form_token]['@columns'];
        }

        foreach ($valid_columns as $p => $p_name) {
            if ($p[0] == '-' || !isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        $selector     = $_SESSION[$form_token]['selector'];
        $form_submit  = $_SESSION[$form_token]['form'];
        $credits      = Q($selector);
        $this->layout = V('credit:report/credit_records_print', [
            'credits'       => $credits,
            'valid_columns' => $valid_columns,
            'selector'      => $selector,
            'form_token'    => $form_token,
            'form_submit'   => $form_submit,
        ]);
    }
}

class Export_AJAX_Controller extends AJAX_Controller
{
    public function index_export_submit()
    {
        $me   = L('ME');
        $form = Input::form();
        foreach ($form['columns'] as $p => $p_name) {
            if ($p < 0) {
                unset($form['columns'][$p]);
            }
        }

        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type           = $form['type'];
        $file_name_time = microtime(true);
        $file_name_arr  = explode('.', $file_name_time);
        $file_name      = $file_name_arr[0] . $file_name_arr[1];

        if ($type == 'csv') {
            $pid = $this->_csv($form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
                'pid'       => $pid,
            ]), [
                'title' => I18N::T('calendars', '导出等待'),
            ]);
        } else {
            URI::redirect('error/401');
        }
    }

    private function _csv($form, $file_name)
    {
        $form_token = $form['form_token'];
        $old_form   = (array) $_SESSION[$form_token];
        $new_form   = (array) $form;

        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $valid_columns   = Config::get('credit.export_columns.credit');
        $visible_columns = $form['columns'] ?: $form['@columns'];

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        $selector = $form['selector'];
        $me       = L('ME');

        Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了系统用户的信用分列表', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');

        if (isset($_SESSION[$me->id . '-export'])) {
            foreach ($_SESSION[$me->id . '-export'] as $old_pid => $old_form) {
                $new_valid_form = $form['form'];

                unset($new_valid_form['form_token']);
                unset($new_valid_form['selector']);
                if ($old_form == $new_valid_form) {
                    unset($_SESSION[$me->id . '-export'][$old_pid]);
                    proc_close(proc_open('kill -9 ' . $old_pid, [], $pipes));
                }
            }
        }

        //if ($records->total_count() > 0) {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_credit export ';
        $cmd .= "'" . $selector . "' '" . $me->id . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "' >/dev/null 2>&1 &";
        // exec($cmd, $output);
        $process = proc_open($cmd, [], $pipes);
        $var     = proc_get_status($process);
        proc_close($process);
        $pid        = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export'][$pid] = $valid_form;
        return $pid;
        //}
    }

    public function index_credit_records_export_submit()
    {
        $me   = L('ME');
        $form = Input::form();
        foreach ($form['columns'] as $p => $p_name) {
            if ($p < 0) {
                unset($form['columns'][$p]);
            }
        }

        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type           = $form['type'];
        $file_name_time = microtime(true);
        $file_name_arr  = explode('.', $file_name_time);
        $file_name      = $file_name_arr[0] . $file_name_arr[1];

        if ($type == 'csv') {
            $pid = $this->_credit_records_csv($form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
                'pid'       => $pid,
            ]), [
                'title' => I18N::T('calendars', '导出等待'),
            ]);
        } else {
            URI::redirect('error/401');
        }
    }

    private function _credit_records_csv($form, $file_name)
    {
        $form_token = $form['form_token'];
        $old_form   = (array) $_SESSION[$form_token];
        $new_form   = (array) $form;

        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $valid_columns   = Config::get('credit.export_credit_record_columns.credit');
        $visible_columns = $form['columns'] ?: $form['@columns'];

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        $selector = $form['selector'];
        $me       = L('ME');

        Log::add(strtr('[equipments] %user_name[%user_id]以CSV导出了系统用户的信用分明细', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');

        if (isset($_SESSION[$me->id . '-export'])) {
            foreach ($_SESSION[$me->id . '-export'] as $old_pid => $old_form) {
                $new_valid_form = $form['form'];

                unset($new_valid_form['form_token']);
                unset($new_valid_form['selector']);
                if ($old_form == $new_valid_form) {
                    unset($_SESSION[$me->id . '-export'][$old_pid]);
                    proc_close(proc_open('kill -9 ' . $old_pid, [], $pipes));
                }
            }
        }

        //if ($records->total_count() > 0) {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_credit credit_record_export ';
        $cmd .= "'" . $selector . "' '" . $me->id . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "' >/dev/null 2>&1 &";
        // exec($cmd, $output);
        $process = proc_open($cmd, [], $pipes);
        $var     = proc_get_status($process);
        proc_close($process);
        $pid        = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export'][$pid] = $valid_form;
        return $pid;
        //}
    }
}
