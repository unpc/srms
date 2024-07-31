<?php

abstract class Layout_Controller extends _Layout_Controller {

	function _before_call($method, &$params) {

        $_SESSION['heartbeat_token'] = Auth::token();

		parent::_before_call($method, $params);

        $this->add_css('form public');
		$this->add_css('base lims message dialog tab tooltip button dropdown autocomplete token_box');
		$this->add_css('table list comment tag user switch_checkbox');
		$this->add_css('prop_box');
        $this->add_css('radio');
		$this->add_css('laydate'); // 日期控件
		$this->add_css('selectpicker'); // 多项选择控件
		$this->add_css('ep_dialog');
		$this->add_css('sbmenu_admin');
	
		$this->add_js('jquery.ui jquery.resize jquery.mousewheel');
		$this->add_js('caret hint autogrow autocomplete tab_ok toggle confirm');
        $this->add_js('moment');
		$this->add_js('dropdown token_box number_box tooltip');
		$this->add_js('lims dialog');
		$this->add_js('socket.io');
		$this->add_js('prop_box');
		$this->add_js('laydate'); // 日期控件
		$this->add_js('date_box');
        $this->add_js('selectpicker'); // 多项选择控件
		$this->add_js('setStep'); // 多项选择控件
		$this->add_js('message');
		$this->add_js('sidebar');
		$this->add_js('top_menu');
		$this->add_js('upload_icon'); // 用户、仪器、课题组头像上传

		if (defined('MODULE_ID')) {
			if (! Module::is_installed(MODULE_ID)) {
				URI::redirect('error/404');
			}
		}

		if ($new_layout = Event::trigger('rewrite_base_layout')){
		    $this->layout = $new_layout;
        }

		$this->layout->sidebar = V('sidebar');
		$this->layout->footer = V('footer');
	}

	function _after_call($method, &$params) {
		parent::_after_call($method, $params);

		$this->add_css('theme');
		// Locale相关CSS
		$locale = Config::get('system.locale');
		$this->add_css('locale.'.$locale);
		$this->add_js('locale.'.$locale);
		if (L('ME')->id) {
			if (Config::get('system.heartbeat')) {
				$this->add_js('heartbeat', FALSE);
			} else {
				// 与heartbeat.js仅interval不同，即关闭计时器，不进行heartbeat_check
				// 之所以这样是因为系统其他代码会写Q.heartbeat.bind，如果不进行heartbeat声明会js报错
				$this->add_js('heartbeatoff', FALSE);
			}
		}

		Event::trigger('layout_controller_after_call', $this);

		if (Config::get('debug.i18n_ipe')) {
			$this->add_js('i18n_ipe', FALSE);
		}
	}

}
