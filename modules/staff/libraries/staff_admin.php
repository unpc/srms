<?php
class Staff_Admin {

	static function admin_people_tab($e, $tab) {
		Event::bind('admin.people.content', 'Staff_Admin::admin_people_content',100,'staff');
		$tab->add_tab('staff', [
			'url'=>URI::url('admin/people.staff'),
			'title'=> I18N::T('staff', '人事信息')
		]);

	}

	static function admin_people_content($e, $tab) {
	
		$dropdown = [
			'0'=>'不提醒到期',
			'1'=>'提前1个月',
			'2'=>'提前2个月',
			'3'=>'提前3个月',
			'4'=>'提前4个月',
			'5'=>'提前5个月',
			'6'=>'提前6个月'
		];

		if( Input::form('submit') ){
			$form = Form::filter(Input::form());
			Lab::set('staff.remind_time', $form['time']);
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('staff', '内容修改成功') );
		}

		$tab->content = V('staff:admin/staff', ['dropdown'=>$dropdown,'time'=>Lab::get('staff.remind_time')]);
	}

}
?>
