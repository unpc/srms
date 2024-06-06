<?php

class Reset_Controller extends Layout_Controller {

    public function index () {
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) return;

        $form = Lab::form();

        if ($form['submit']) {
            if (!$form['lab_id'] || $form['lab_id'] != LAB_ID) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('support', '请正确填写站点ID'));
                URI::redirect('admin/support.system_reset');
                return;
            }
            if ($form['erase_all'] == 'on') {
                putenv('Q_ROOT_PATH=' . ROOT_PATH);
                $command = 'SITE_ID='.SITE_ID.' LAB_ID='.LAB_ID.' php '.ROOT_PATH.'cli/cli.php reset erase_all>/dev/null 2>&1 &';
                exec($command);
                die(I18N::T('support', '系统正在还原中, 请等待5秒后刷新页面并重新登录。默认genee用户密码为: 83719730'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('support', '请勾选需要的操作'));
                URI::redirect('admin/support.system_reset');
                return;
            }
        }

        if ($form['backup']) {                
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('support', '请联系运维人员进行备份！'));
            URI::redirect('admin/support.system_reset');
            return;
        }

        URI::redirect('/');
    }
}