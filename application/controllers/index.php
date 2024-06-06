<?php

class Index_Controller extends Layout_Controller
{
    public function index()
    {
        $me = L('ME');
        if ($me->id && $me->is_active()) {
            $url = $me->home_url();
            if ($url && $url != 'index') {
                URI::redirect($url);
            }
        }
        $this->layout->body = V('body');
    }

    function download($file_name, $suffix) {
        $file_name = FILE::fix_path($file_name);
        $path = Config::get('system.excel_path');
        $new_file_name = date('ymdHi', substr($file_name, 0, 10));
        if (Event::trigger('fudan_gao_mini.excel.name', $file_name)) {
            $new_file_name = Event::trigger('fudan_gao_mini.excel.name', $file_name);
        }

        if (file_exists($path.'/'.$file_name.'.'.$suffix)) {
            //Downloader::download($path.'/'.$file_name.'.'.$suffix, TRUE);
            header("Content-type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: ".filesize($path.'/'.$file_name.'.'.$suffix));
            if (Browser::name() == 'firefox') {
                header("Content-Disposition: attachment; filename*=utf8'" . urlencode($new_file_name) . '.' . $suffix);
            } else {
                header("Content-Disposition: attachment; filename=" . urlencode($new_file_name) . '.' . $suffix);
            }
            ob_clean();

            echo file_get_contents($path.'/'.$file_name.'.'.$suffix);
            unlink($path.'/'.$file_name.'.'.$suffix);
            exit;
        }
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{
    public function index_sidebar_lock_toggle()
    {
        $form = Input::form();
        $_SESSION['sidebar_unlock'] = $form['unlock'];
    }

    public function index_sbmenu_mode_click()
    {
        $form = Input::form();

        $me = L('ME');
        if ($me->id) {
            $me->sbmenu_mode = $form['mode'];
            $me->save();
        }

        Output::$AJAX['view'] = (string) V('application:sidebar/menu');
    }

    public function index_notip_click()
    {
        $me = L('ME');

        if (Input::form('path')) {
            $tip_path = Input::form('path');

            if ($me->id) {
                $hidden_tips = $me->hidden_tips;
                $hidden_tips[$tip_path] = true;
                $me->hidden_tips = $hidden_tips;
                $me->save();
            } else {
                $_SESSION['hidden_tips'][$tip_path] = true;
            }
        }
    }

    public function index_download_click()
    {
        $form = Input::form();
        $path = Config::get('system.excel_path');
        $file_name = $form['file_name'];
        if (file_exists($path.'/'.$file_name.'.xlsx')) {
            JS::close_dialog();
            Output::$AJAX['res'] = 'xlsx';
        } elseif (file_exists($path.'/'.$file_name.'.xls')) {
            JS::close_dialog();
            Output::$AJAX['res'] = 'xls';
        } else {
            Output::$AJAX['res'] = 'not found';
        }
    }

    public function index_export_wait_click()
    {
        $form = Input::form();
        $pid = $form['pid'];
        unset($_SESSION[$me->id.'-export'][$pid]);
        proc_close(proc_open('kill -9 '.$pid, [], $pipes));
    }

    function index_save_language_change() {
        $form = Input::form();
        $language = $form['language'];
        
        $me = L('ME');
        if ($me->id) {
            $me->locale = $language;
            if($me->save()){
                I18N::shutdown();
                if (L('ME')->id == $me->id) {
                    $_SESSION['system.locale'] = $me->locale;
                    Config::set('system.locale',$me->locale);
                }
                I18N::setup();
            }
        } else {
            I18N::shutdown();
            $_SESSION['system.locale'] = $language;
            Config::set('system.locale',$language);
            I18N::setup();
        }
        Output::$AJAX = false;
    }

}
