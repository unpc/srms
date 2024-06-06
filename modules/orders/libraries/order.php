<?php

class Order {

	static function setup_people($e) {
		Event::bind('profile.follow.tab', [__CLASS__, 'index_follow_order_tab'], 100, 'order');
		Event::bind('profile.follow.content', [__CLASS__, 'index_follow_order_content'], 100, 'order');
	}

	static function index_follow_order_tab($e, $tabs) {
		$user = $tabs->user;
		$me = L('ME');
		/*
		NO.TASK#274(guoping.zhang@2010.11.26)
		应用权限判断新规则
		*/
		if ($me->is_allowed_to('列表关注的订单', $user)) {
			$count = $user->get_follows_count('order');
			if ($count > 0) {
				$tabs
				->add_tab('order', [
					'url'=> $user->url('follow.order'),
					'title'=>I18N::T('orders', '订单(%d)', ['%d'=>$count]),
				]);
			}
		}
	}

	static function index_follow_order_content($e, $tabs) {
		$user=$tabs->user;
		$follows = $user->followings('order');
		$pagination = Lab::pagination($follows, Input::form('st'), 20);

		Controller::$CURRENT->add_css('orders:order');

		$tabs->content = V('orders:follow/orders',[
					'follows'=>$follows,
					'pagination'=>$pagination,
				]);
	}

	static function on_enumerate_user_perms($e, $user, $perms) {
		if (!$user->id) return;
        //取消现默认赋予给pi的权限
//		if (Q("$user<pi lab")->total_count()) {
//			$perms['管理负责实验室订单'] = 'on';
//		}
	}

	/**
	 * xiaopei.li@2011.03.01
	 * 设置order对象markup后<a>的文本
	 *
	 * @param e
	 *
	 * @return
	 */
	static function markup_name($e, $order) {
		$format = '%product_name';
		$name = T($format, ['%product_name'=>$order->product_name]);
		$e->return_value = $name;
		return FALSE;
	}

	static function setup() {
		Event::bind('admin.update.tab', 'Order::update_primary_tab');
		Event::bind('admin.update.content', 'Order::update_primary_content', 0, 'order');
	}

	static function setup_update() {
		Event::bind('update.index.tab', 'Order::_update_index_tab');
	}

	static function _update_index_tab($e, $tabs) {
		$tabs->add_tab('order', [
			'url'=>URI::url('!update/index.order'),
			'title'=>I18N::T('orders', '订单更新')
		]);
	}

	static function get_update_parameter($e, $object, array $old_data = [], array $new_data = []) {
		if ($object->name() != 'order' || !$old_data) return;
		$difference = array_diff_assoc($new_data,$old_data);
	  	$old_difference = array_diff_assoc($old_data, $new_data);
	  	$arr = array_keys($difference);
	  	$data = $e->return_value;
	  	if(!count($difference)) {
	  		return;
	  	}
	  	$delta = [];
	  	$subject = L('ME');
  		$delta['subject'] = $subject;
	  	$delta['object'] = $object;
	  	$delta['action'] = 'edit_info';
	  	if (in_array('status', $arr)) {
	  		$difference['status'] = Order_Model::$order_status[$difference['status']];
	  	}
	  	if (in_array('grant_lab', $arr)) {
	  		$lab = $difference['grant_lab'];
	  		if ($lab->id) {
	  			$difference['grant_lab'] = Markup::encode_Q($lab);
	  		}
	  		else {
	  			unset($difference['grant_lab']);
	  		}
	  	}
	  	$delta['new_data'] = $difference;
	  	$delta['old_data'] = $old_difference;

  		$key = Misc::key((string)$subject, $delta['action'], (string)$object);
  		$data[$key] = (array)$data[$key];

  		Misc::array_merge_deep($data[$key], $delta);

  		$e->return_value = $data;
	}

	static $properties = [
		'manufacturer'=>'生产商',
		'vendor'=>'供应商',
		'product_name'=>'订单名称',
		'catalog_no'=>'目录号',
		'unit_price'=>'单价',
		'quantity'=>'数量',
		'unit'=>'单位',
		'note'=>'备注',
		'grant_lab'=>'经费课题名称',
		'status'=>'当前状态'
	];

	static function get_update_message($e, $update) {
		if ($update->object->name() !== 'order')
			return;
		$me = L('ME');
		$subject = $update->subject->name;
		$old_data = json_decode($update->old_data, TRUE);
		$object = $old_data['product_name'] ? $old_data['product_name'] : $update->object->product_name;
		/*
		if ($me->id == $update->subject->id) {
			$subject = I18N::T('orders', '我');
		}*/

		$config = 'orders.order.info.msg.model';
		$opt = Lab::get($config, Config::get($config));
		$msg = I18N::T('orders', $opt['body'], [
						'%subject'=>URI::anchor($update->subject->url(), $subject, 'class="blue label"'),
						'%date'=>'<strong>'.Date::fuzzy($update->ctime, 'TRUE').'</strong>',
						'%order'=>URI::anchor($update->object->url(), $object, 'class="blue label"')
						]);
		$e->return_value = $msg;
		return FALSE;
	}

	static function get_update_message_view($e, $update) {
		$e->return_value = V('orders:update/order_show_msg', ['update'=>$update]);
		return FALSE;
	}

	//保存order后触发消息发送事件
	static function on_order_saved($e, $order, $old_data, $new_data) {

        $user = $order->requester;
        $me = L('ME');

		//如果存在新的状态，并且与原有状态不同，则进行消息发送流程
		if (isset($new_data['deliver_status']) && ($new_data['deliver_status'] != $old_data['deliver_status'])) {
			switch($new_data['deliver_status']) {
				//申购订出后向申请者发送消息
				case Order_Model::NOT_RECEIVED:
                    //从已到货修改为已未到货, 不应该发送消息
                    if ($old_data['deliver_status'] != Order_Model::RECEIVED) {
                        Notification::send('orders.order_confirmed', $user, [
                            '%request_date'=>Date::format($order->request_date, T('Y年m月d日')),
                            '%purchase_date'=>Date::format($order->purchase_date, T('Y年m月d日')),
                            '%user'=>Markup::encode_Q($user),
                            '%order'=>Markup::encode_Q($order),
                        ]);
                    }
				break;

				case Order_Model::CANCELED:
					if ($me->id != $order->requester->id ) {
						//申购被管理员取消后向申请者发送消息，自己取消自己的申购不会发送消息
						Notification::send('orders.order_canceled', $user, [
                            '%request_date'=>Date::format($order->request_date, T('Y年m月d日')),
							'%user'=>Markup::encode_Q($user),
							'%order'=>Markup::encode_Q($order),
                        ]);
                    }
					break;
				//申购到货后向申请者发送消息
				case Order_Model::RECEIVED :
					//发送消息
					Notification::send('orders.order_received', $user,[
                        '%receiver'=>Markup::encode_Q($order->receiver),
						'%user'=>Markup::encode_Q($user),
						'%order'=>Markup::encode_Q($order),
                        '%request_date'=> Date::format($order->request_date, T('Y年m月d日'))
					]);
					break;
			}
		}
		else if(isset($new_data['status']) && ($new_data['status'] != $old_data['status'])){
			switch($new_data['status']) {
				//申购被管理员订出申请者发送消息
				case Order_Model::READY_TO_TRANSFER:
					if( 
						$me->id != $order->requester->id && 
						$order->deliver_status == Order_Model::NOT_RECEIVED
						) {
						Notification::send('orders.order_confirmed', $user, [
							'%request_date'=>Date::format($order->request_date, T('Y年m月d日')),
							'%purchase_date'=>Date::format($order->purchase_date, T('Y年m月d日')),
							'%user'=>Markup::encode_Q($user),
							'%order'=>Markup::encode_Q($order),
						]);
					}
					break;
				case Order_Model::CANCELED:
					if ( $me->id != $order->requester->id ) {
						//申购被管理员取消后向申请者发送消息，自己取消自己的申购不会发送消息
						Notification::send('orders.order_canceled', $user, [
                            '%request_date'=>Date::format($order->request_date, T('Y年m月d日')),
							'%user'=>Markup::encode_Q($user),
							'%order'=>Markup::encode_Q($order),
							'%incharge'=>Markup::encode_Q($me),
							'%reason'=>$new_data['cancel_note']
						]);
					}
					break;
			}
		}

        //针对新添加的order进行at comment的发送
        if (!$old_data['id'] && $new_data['id']) {

            $incharges = json_decode($order->incharges, TRUE);
            if (count($incharges)) {
                $me = L('ME');

                $comment = O('comment');
                $comment->object = $order;
                $comment->author = L('ME');

                $at_users = implode(' ', array_map(function($id) {
                    $user = O('user', $id);
                    return strtr('@{%name|%id}', [
                        '%name' => $user->name,
                        '%id'=> $user->id
                    ]);
                }, array_keys($incharges)));

                $comment->content = I18N::T('orders', Config::get('orders.at_comment_content'), [
                    '%user添加了订单%order %at_users',
                    '%user'=> $me->name,
                    '%order'=> $order->product_name,
                    '%at_users'=> $at_users
                ]);

                $comment->at_link_title = $order->product_name;

                $comment->save();
            }
        }
	}

	static function extend_stock_links($e, $stock, $links, $mode) {
		$me = L('ME');

		switch ($mode) {
		case 'view':
			if ($me->is_allowed_to('添加申购', 'order')) {
				$links['reorder'] = [
					'url' => URI::URL('!orders/order/from_stock.' . $stock->id),
					'text'  => I18N::T('inventory', '申购'),
					'extra' => 'class="button button_cancel"
							q-event="click"
							q-object="from_stock"
							q-src="'.URI::URL('!orders/order/index.' . $stock->id).'"',
                        ];
			}
			break;
		case 'record':
			break;
		case 'index':
		default:
			if ($me->is_allowed_to('添加申购', 'order')) {
				$links['reorder'] = [
					'url' => URI::URL('!orders/order/from_stock.' . $stock->id),
					'text'  => I18N::T('inventory', '申购'),
					'extra'=>'class="blue"
						q-event="click"
						q-object="from_stock"
						q-src="'.URI::URL('!orders/order/index.' . $stock->id).'"',
					];
			}
		}
	}

	static function extend_stock_view($e, $stock) {
		if ($stock->order->id) {
			$e->return_value = (string) V('orders:order/stock_link',
										  ['stock' => $stock]);
		}
	}

   static function order_newsletter_content($e, $user) {

		$dtstart = strtotime(date('Y-m-d')) - 86400;
		$dtend = strtotime(date('Y-m-d'));
		$templates = Config::get('newsletter.template');
		$db = Database::factory();

		$template = $templates['finance']['requesting_orders_count'];
		$sql = "SELECT COUNT(*) FROM `order` WHERE status=".Order_Model::REQUESTING." AND request_date>%d AND request_date<%d";
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('orders:newsletter/requesting_orders_count', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['not_received_orders_count'];
		$sql = "SELECT COUNT(*) FROM `order` WHERE deliver_status=".Order_Model::NOT_RECEIVED." AND purchase_date>%d AND purchase_date<%d";
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('orders:newsletter/not_received_orders_count', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['not_received_orders_price'];
		$sql = "SELECT SUM(price) FROM `order` WHERE deliver_status=".Order_Model::NOT_RECEIVED." AND purchase_date>%d AND purchase_date<%d";
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('orders:newsletter/not_received_orders_price', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['received_stocks_count'];
		$sql = "SELECT COUNT(*) FROM `order` WHERE deliver_status=".Order_Model::RECEIVED." AND receive_date>%d AND receive_date<%d";
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('orders:newsletter/received_stocks_count', [
				'count' => $count,
				'template' => $template,
			]);
		}

		$template = $templates['finance']['add_stock'];
		$sql = "SELECT COUNT(*) FROM `stock` LEFT JOIN `order` ON `order`.`id`=`stock`.`order_id` WHERE `order`.status=".Order_Model::RECEIVED." AND `stock`.ctime>%d AND `stock`.ctime<%d";
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('orders:newsletter/add_stock', [
				'count' => $count,
				'template' => $template,
			]);
		}

		if (strlen($str) > 0) {
			$view = V('orders:newsletter/view', [
					'str' => $str,
			]);
			$e->return_value .= $view;
		}
    }

    static function get_extra_view($e, $form, $order) {
    	if ( Module::is_installed('vendor') ) {
    		return TRUE;
    	}

    	$disabled = '';

    	if ($order->status == Order_Model::READY_TO_ORDER || $order->status == Order_Model::NEED_VENDOR_APPROVE) {
    		$disabled = 'disabled';
    	}

    	$e->return_value = V('orders:order/vendor', ['form' => $form, 'order' => $order, 'disabled' => $disabled]);
    	return FALSE;
    }

    static function get_extra_edit_view($e, $form, $order) {
    	if ( Module::is_installed('vendor') ) {
    		return TRUE;
    	}

    	$e->return_value = V('orders:order/vendor', ['form' => $form, 'order' => $order, 'disabled' => '']);
    	return FALSE;
    }

    static function extra_basic_form_to_order($e, $order, $form) {

    	if ( !Module::is_installed('vendor') ) {
    		$order->vendor = $form['vendor'];
    	}
    }
}
