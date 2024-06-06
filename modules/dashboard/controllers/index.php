<?php

class Index_Controller extends Layout_Controller {

    public function index() {
        $me = L('ME');

        if (!$me->is_allowed_to('查看', 'dashboard_new')) {
            URI::redirect('error/401');
        }
		$this->layout->title = I18N::T('achievements', '数据总览');
        $this->layout = V('dashboard:layout');
        $this->layout->body = V('dashboard:index');
    }
}