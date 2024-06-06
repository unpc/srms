<?php
class Index_Controller extends Controller {

}

class Index_AJAX_Controller extends AJAX_Controller {

	public function index_watcher_view_click () 
	{
		$form = Input::form();
		$equipment = O('equipment', (int)$form['id']);
		if (!$equipment->id) return;
		if (!$equipment->watcher_code) {
			$key = (string) Config::get('equipment.super_key');
			$rand_code = LAB_ID . "#EQ#{$equipment->id}";
			$watcher_code = Cipher::encrypt($rand_code, $key, FALSE, 'des');
			for($i=0;$i<strlen($watcher_code);$i++) {
				$watcher_code[$i] = chr(0x30 + ord($watcher_code[$i])%10);
			}
			$equipment->watcher_code = $watcher_code;
			$equipment->save();
		}
		JS::dialog((string)V('watcher:identify', [
			'equipment' => $equipment
		]), [
			'title' => I18N::T('watcher', '多媒体验证码')
		]);
	}

}