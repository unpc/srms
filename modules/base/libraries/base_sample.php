<?php

class Base_Sample extends Base_Action {

	static function on_eq_sample_saved ($e, $sample, $old_data, $new_data) {
		$me = L('ME');
        // 需要在nginx开启 HTTP_X_REAL_IP 配置
        $ip = $_SERVER['HTTP_X_REAL_IP'];
        $result = Base_point::getIp();
        $action = O('action');
		$action->source = $sample;
		$action->user = $me;
		$action->ip = $ip;
		$action->area = $result['status'] ? '无' : explode('|', $result['address'])[1];
		$action->date = time();
        $action->save();
	}

}