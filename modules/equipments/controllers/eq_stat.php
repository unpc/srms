<?php

class EQ_Stat_AJAX_Controller extends AJAX_Controller {

    public function index_export_sj_tri_click(){
        $form = Form::filter(Input::form());
        $id = $form['id'];
        $equipment = O('equipment', $id);

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        if($equipment->id) {
            $pid = $this->_export_excel($equipment, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
                'pid' => $pid
            ]), [
                'title' => I18N::T('labs', '导出等待')
            ]);
        }
    }

    public function _export_excel($equipment, $file_name) {
        $me = L('ME');
        
        if (isset($_SESSION[$me->id.'-export'])) {
            foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_equipment_id) {
                $new_equipment_id = $equipment->id;

                if ($old_equipment_id == $new_equipment_id) {
                    unset($_SESSION[$me->id.'-export'][$old_pid]);
                    proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
                }
            }
        }

        Log::add(strtr('[EQ_Stat] %user_name[%user_id]以Excel下载了%equipment_name[%equipment_id]仪器统计数据', [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_name' => $equipment->name,
                '%equipment_id' => $equipment->id
                ]),'journal');

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php eq_stat_export export_sj_tri ';
        $cmd .= "'".$equipment->id."' '".$file_name."'>/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $_SESSION[$me->id.'-export'][$pid] = $equipment->id;
        return $pid;
    }
}
