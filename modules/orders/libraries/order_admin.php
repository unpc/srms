<?php
class Order_Admin {
	static function setup() {
		if (L('ME')->access('管理订单和供应商')) {
			Event::bind('admin.index.tab', 'Order_Admin::_primary_tab');
		}
	}

	static function _primary_tab($e, $tabs) {
		Event::bind('admin.index.content', 'Order_Admin::_primary_content', 0, 'order');
		$tabs->add_tab('order', [
						   'url' => URI::url('admin/order'),
						   'title' => I18N::T('orders', '订单管理')
					   ]);
	}

	static function _primary_content($e, $tabs) {
		$tabs->content = V('admin/view');
		Event::bind('admin.order.content', 'Order_Admin::_secondary_notification_content', 0, 'message');
		Event::bind('admin.order.content', 'Order_Admin::_secondary_other_content', 0, 'other');
		
		$tabs->content->secondary_tabs = Widget::factory('tabs')
										 ->set('class', 'secondary_tabs')
                                         ->add_tab('message', [
                                             'url'=>URI::url('admin/order.message'),
                                             'title'=> I18N::T('orders', '通知提醒'),
                                         ])
                                         ->add_tab('other', [
                                            'url' => URI::url('admin/order.other'),
                                            'title'=> I18N::T('orders', '订单设置')
                                         ])
										 ->tab_event('admin.order.tab')
										 ->content_event('admin.order.content');
		$params = Config::get('system.controller_params');
		$tabs->content->secondary_tabs->select($params[1]);
	}

    static function _secondary_other_content($e, $tabs) {
        $form = Input::form();

        if ($form['submit']) {

            Lab::set('orders.incharges', $form['incharges']);
            Lab::set('orders.receive_address', $form['address']);
            Lab::set('orders.receive_postcode', $form['postcode']);
            Lab::set('orders.receive_phone', $form['phone']);
            Lab::set('orders.receive_email', $form['email']);

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('orders', '默认订单负责人设置成功!'));
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('orders', '默认收货地址设置成功!'));
        }

        $incharges = Lab::get('orders.incharges');

        $tabs->content = V('orders:admin/other', ['form'=>$form, 'incharges'=>$incharges]);
    }

	static function _secondary_notification_content($e, $tabs) {
		$configs = [
			'notification.orders.order_confirmed',
			'notification.orders.order_canceled',
			'notification.orders.order_received'
		];

		$vars = [];
		$form = Form::filter(Input::form());

		if (in_array($form['type'], $configs)) {
			if ($form['submit']) {
				$form
					->validate('title', 'not_empty', I18N::T('orders', '消息标题不能为空！'))
					->validate('body', 'not_empty', I18N::T('orders', '消息内容不能为空！'));
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
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('orders', '内容修改成功'));
				}
			}
			elseif ($form['restore']) {
				Lab::set($form['type'], NULL);
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('orders', '恢复系统默认设置成功'));
			}
		}
		$views = Notification::preference_views($configs, $vars, 'orders');
		$tabs->content = $views;
	}
}
