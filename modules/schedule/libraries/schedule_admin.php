<?php
class Schedule_Admin {
	static function setup() {
		if (L('ME')->access('管理所有成员的日程安排')) {
			Event::bind('admin.index.tab', 'Schedule_Admin::_primary_tab');
		}
	}

	static function _primary_tab($e, $tabs) {
		Event::bind('admin.index.content', 'Schedule_Admin::_primary_content', 0, 'schedule');
		$tabs->add_tab('schedule', [
						   'url' => URI::url('admin/schedule'),
						   'title' => I18N::T('schedule', '日程管理')
					   ]);
	}

	static function _primary_content($e, $tabs) {
		$tabs->content = V('admin/view');
		Event::bind('admin.schedule.content', 'Schedule_Admin::_secondary_notification_content', 0, 'message');
		
		$tabs->content->secondary_tabs = Widget::factory('tabs')
										 ->set('class', 'secondary_tabs')
										 ->add_tab('message', [
													   'url'=>URI::url('admin/schedule.message'),
													   'title'=> I18N::T('schedule', '通知提醒'),
												   ])
										 ->tab_event('admin.schedule.tab')
										 ->content_event('admin.schedule.content');
		$params = Config::get('system.controller_params');
		$tabs->content->secondary_tabs->select($params[1]);
	}

	static function _secondary_notification_content($e, $tabs) {
		$configs = [
			'notification.schedule.lab.add_event.to_people',
			'notification.schedule.lab.delete_event.to_people',
			'notification.schedule.user.add_event.to_organizer'
		];

		$vars = [];
		$form = Form::filter(Input::form());

		if (in_array($form['type'], $configs)) {
			if ($form['submit']) {
				$form
					->validate('title', 'not_empty', I18N::T('schedule', '消息标题不能为空！'))
					->validate('body', 'not_empty', I18N::T('schedule', '消息内容不能为空！'));
				$vars['form'] = $form;
				if ($form->no_error) {
					$config = Lab::get($form['type'], Config::get($form['type']));
					$tmp = [
						'description' => $config['description'],
						'strtr' => $config['strtr'],
						'title' => $form['title'],
						'body' => $form['body']
					];
					foreach (Lab::get('notification.handlers') as $k => $v) {
						if (isset($form['send_by_' . $k])) {
							$value = $form['send_by_' . $k];
						}
						else {
							$value = 0;
						}
						$tmp['send_by'][$k] = $value;
					}
					Lab::set($form['type'], $tmp);
				}
				if ($form->no_error) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('schedule', '内容修改成功'));
				}
			}
			elseif ($form['restore']) {
				Lab::set($form['type'], NULL);
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('schedule', '恢复系统默认设置成功'));
			}
		}
		$views = Notification::preference_views($configs, $vars, 'schedule');
		$tabs->content = $views;
	}
}
