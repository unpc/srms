<?php

class Stream_AJAX_Controller extends AJAX_Controller {

	function index_refresh_list_click() {
		$config = Config::get('stream');

        if ($config['use_stream']) {
            if (Stream::refresh_list()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('vidmon', '列表刷新成功!'));
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('vidmon', '列表刷新失败!'));
            }

            JS::refresh();
        }
    }
}
