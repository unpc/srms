<?php

class Dutys_AJAX_Controller extends AJAX_Controller
{
    function index_output_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        $type = $form['type'];
        $columns = Config::get('equipments.export_columns.dutys');
        $columns = new ArrayIterator($columns);

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return FALSE;
        }

        if ($type == 'csv') {
            $title = I18N::T('eq_charge', '请选择要导出Excel的列');
        } else {
            $title = I18N::T('eq_charge', '请选择要打印的列');
        }
        JS::dialog(V('equipments:dutys/output_form', [
            'type' => $type,
            'form_token' => $form_token,
            'columns' => $columns,
            'oid' => $form['oid'],
            'oname' => $form['oname']
        ]), [
            'title' => $title
        ]);
    }

    function index_dutys_export_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type = $form['type'];

        $old_form = (array)$_SESSION[$form_token];
        $new_form = (array)$form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0] . $file_name_arr[1];

        if ('csv' == $type) {
            $pid = $this->_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
                'pid' => $pid
            ]), [
                'title' => I18N::T('equipments', '导出等待')
            ]);
        }
    }

    public function _export_csv($selector, $form, $file_name)
    {
        $me = L('ME');
        $valid_columns = Config::get('equipments.export_columns.dutys');
        $visible_columns = (array)$form['columns'];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        unset($valid_columns['-1']);
        unset($valid_columns['-2']);

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

        // 扩充的打印导出默认勾选功能，将 valid_columns 的 value 改为了 array，此处做兼容
        foreach ($valid_columns as $key => $value) {
            if (is_array($value)) {
                unset($valid_columns[$key]);
                $valid_columns[$key] = $value['name'];
            }
        }

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        unset($form['selector']);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_dutys export ';
        $cmd .= "'" . json_encode($form, JSON_UNESCAPED_UNICODE) . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "'>/dev/null 2>&1 &";
        // error_log($cmd);
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export'][$pid] = $valid_form;
        return $pid;
    }
}
