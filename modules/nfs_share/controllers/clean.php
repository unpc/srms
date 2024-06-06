<?php

class Clean_Controller extends Base_Controller {

	function index(){
		
		if (!L('ME')->access('清理文件系统')) URI::redirect('error/401');
		$clean = Lab::get('nfs_share.clean_seeting');

		$this->layout->body->primary_tabs
			->select('clean')
			->set('content', V('clean/index',
			[
				'form' => $form,
				'clean' => $clean
			]));
							
	}

}

class Clean_AJAX_Controller extends AJAX_Controller {
	
	function index_click_submit(){
		if (!L('ME')->access('清理文件系统')) URI::redirect('error/401');
		$clean = Lab::get('nfs_share.clean_seeting');
		
		$form = Form::filter(Input::form());
		if($form['submit'] == 'submit') {
			
			if (Date::get_day_start($form['dtstart']) > Date::get_day_end($form['dtend'])) {
				JS::alert(I18N::T('nfs_share','清理范围起始日期不能大于结束日期!'));
				return false;
			}
			if (Date::get_minute_end($form['clean_time']) < Date::get_day_end(Date::time()) ) {
				JS::alert(I18N::T('nfs_share','清理时间最早只能从当前日期的后一天开始设置!'));
				return false;
			}
			
			if (!JS::confirm(I18N::T('nfs_share', '系统将在'.date('Y-m-d H:i', $form['clean_time']).'自动执行清理操作， 在此之前请及时通知系统用户尽快备份，文件一旦被清理，将无法恢复。您确定要清理吗？'))) return;

		
			$setting = [];
			$setting['dtstart'] = Date::get_day_start($form['dtstart']);
			$setting['dtend'] = Date::get_day_end($form['dtend']);
			$setting['clean_time'] = Date::get_minute_start($form['clean_time']);
			
			Lab::set('nfs_share.clean_seeting', $setting);
			$clean = $setting;
			$users = Q('user[atime]');

			foreach($users as $user) {
				Notification::send('clean_setting', $user, [
					'%cleancharge' => Date::format($setting['clean_time']),
					'%dtstart'=> Date::format($setting['dtstart']),
					'%dtend'=> Date::format($setting['dtend'])
				]);
			}
						
		}

		if($form['submit'] == 'cancel') {

			$setting = [];
			$setting['dtstart'] = 0;
			$setting['dtend'] = 0;
			$setting['clean_time'] = 0;

			Lab::set('nfs_share.clean_seeting', $setting);

			$clean = $setting;
		}
		JS::refresh();
	}
}

