<?php
class System_Controller extends Layout_Controller {
    function download_summary() {
        $file_name = "report_".SITE_ID."_".LAB_ID.".csv";
        $path = "/volumes/" . $file_name;
        if (file_exists($path)) {
            header("Content-type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: ".filesize($path));
            if (Browser::name() == 'firefox') {
                header("Content-Disposition: attachment; filename*=utf8'" . urlencode($file_name));
            } else {
                header("Content-Disposition: attachment; filename=" . urlencode($file_name));
            }
            ob_clean();

            echo file_get_contents($path);
            exit;
        }
    }
}
class System_AJAX_Controller extends AJAX_Controller {
    function index_system_info_click() {
        $form = Input::form();
        $configs = Config::get('support_system.command_list', []);
        if (!$configs[$form['key']]) JS::refresh();

        $command = strtr($configs[$form['key']]['command'], [
            '%SITE_ID' => SITE_ID,
            '%LAB_ID' => LAB_ID,
        ]);
        if (exec($command, $output)) {
            if (Config::get("hooks.admin.support.system_info.{$form['key']}")) {
                $output = new ArrayIterator($output);
                Event::trigger("admin.support.system_info.{$form['key']}", $output);
            }
            Output::$AJAX['#'.$form['container_id']] = [
				'data'=>(string) V('support:system_info/data',['output'=>$output]),
				'mode'=>'replace',
			];
        }
        else {
            JS::refresh();
        }
    }
}
