<?php

class Monitor_Controller extends Base_Controller {

	function index() {

        $me = L('ME');
        if (!$me->is_allowed_to('多屏监控', 'vidcam')) URI::redirect('error/401');
		
        $selector = 'vidcam';

        //如果用户不允许直接查看,说明用户为负责人
        if (!$me->access('监控视频设备')) {
            $selector = "({$me}<incharge|{$me}<incharge equipment<camera) ". $selector;
        }
		$selector = Event::trigger('vidmon.vidcam.extra_selector', $selector) ? : $selector;

		$vidcams = Q($selector);
		$primary_tabs = $this->layout->body->primary_tabs->select('monitor');
		$primary_tabs->content = V('vidmon:monitor', ['vidcams' => $vidcams]);
        $this->add_css('common');
        
        if (Config::get('stream')['use_stream']) {
            $this->add_js('vidmon:hls');
        }
	}
}

class Monitor_AJAX_Controller extends AJAX_Controller {

	function index_alarm_get() {

		$form = Input::form();
        $now = Date::time();
        $alarm_capture_start = $now - Config::get('vidmon.capture_duration');

        foreach($form['ids'] as $id) {
            $vidcam = O('vidcam', $id);
            $alarm = Q("vidcam_alarm[vidcam={$vidcam}][ctime={$alarm_capture_start}~{$now}]")->total_count();

            Output::$AJAX['vidcam_alarm'][$id] = (bool) $alarm;
        }
    }
}
