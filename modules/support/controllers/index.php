<?php
class Index_AJAX_Controller extends AJAX_Controller {
    function index_lab_calculate_account_click() {
        if (!in_array(L('ME')->token, Config::get('lab.admin'))) return;

        $form = Input::form();
        $labId = $form['lab_id'];
        $lab = O('lab', $labId);
        if ($lab->id) {
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = "SITE_ID=" . SITE_ID . " LAB_ID=" . LAB_ID . " php " . ROOT_PATH . "cli/cli.php support update_billing_account {$labId} >/dev/null 2>&1 &";
            exec($cmd);
            Lab::Message(Lab::MESSAGE_NORMAL, I18N::T('support', '正在重新计算本课题组所有财务明细，请稍后刷新查看') );
        }
        JS::refresh();
    }
}
