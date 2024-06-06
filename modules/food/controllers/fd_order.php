<?php

class Fd_Order_controller extends Base_Controller {

	function index() {

		$type = strtolower(Input::form('type'));

		$form_token = Input::form('form_token');

		$me = L('ME');
		if (isset($_SESSION[$form_token])) {
			$form = $_SESSION[$form_token];
		}
		else {
			$form_token = Session::temp_token('fd_order_',300);

			$form = Lab::form(function(&$old_form, &$form) {
				if (isset($form['date_filter'])) {
					if (!isset($form['dtstart_check'])) unset($old_form['dtstart_check']);
					if (!isset($form['dtend_check'])) unset($old_form['dtend_check']);
					unset($form['date_filter']);
				}
			});

			$form['form_token'] = $form_token;
			$_SESSION[$form_token] = $form;
		}

		$selector = 'fd_order';

		if ($form['user']) {
			$user = Q::quote($form['user']);
			$selector = "user[name*=$user] ".$selector;
		}


		if (!$form['dtstart_check'] && !$form['dtend_check'] &&
			!$form['date_filter'] && !$form['reset_search']) {
				$form['dtstart_check'] = 1;
				$form['dtstart'] = mktime(0, 0, 0, date('m', time()), date('d', time()), date('Y', time()));
			} /* 默认显示今天 */

		if ($form['dtstart_check'] || $form['dtend_check']) {

			if ($form['dtstart_check']) {
				$dtstart = Q::quote($form['dtstart']);

				if (!$form['dtend_check']) {
					$dtend = mktime(23, 59, 59, date('m', $dtstart), date('d', $dtstart), date('Y', $dtstart));
				}
			}
			if ($form['dtend_check']) {
				$dtend = Q::quote($form['dtend']);

				if (!$form['dtstart_check']) {
					$dtstart = mktime(0, 0, 0, date('m', $dtend), date('d', $dtend), date('Y', $dtend));
				}
			}

			/* 无论dtstart/dtend，若只选一个仅显示该天 */

			$selector .= "[ctime >= {$dtstart}]";
			$selector .= "[ctime <= {$dtend}]";
		}

		$selector .= ':sort(ctime D, supplier D, foods D)';

		$orders = Q($selector);
		$time = $orders->current()->ctime;

		$type = strtolower(Input::form('type'));

		if ($type) {
			switch($type) {
			case 'print':$this->_print_order($form, $orders);break;
			case 'csv':$this->_export_order($form, $orders);break;
			default:break;
			}
		}
		else {
			$panel_buttons = [];
			if ($me->is_allowed_to('查看', 'fd_order')) {
				$panel_buttons['print'] = [
					'url' => URI::url('!food/fd_order/index', ['type'=>'print', 'form_token'=>$form_token]),
					'text'  => I18N::T('food','打印'),
					'extra' => ' class="button button_print  middle" target="_blank"'
				];
				$panel_buttons['csv'] = [
					'url' => URI::url('!food/fd_order/index', ['type'=>'csv', 'form_token'=>$form_token]),
					'text'  => I18N::T('food','导出CSV'),
					'extra' => ' class="button button_save " target="_blank"'
				];
				$panel_buttons['print_sum'] = [
					'url' => URI::url('!food/fd_order/print_sum'),
					'text'  => I18N::T('food','打印汇总报表'),
					'extra' => ' class="button button_print  middle" target="_blank"'
				];
				$panel_buttons['csv_sum'] = [
					'url' => URI::url('!food/fd_order/csv_sum'),
					'text'  => I18N::T('food','导出汇总报表'),
					'extra' => ' class="button button_save " target="_blank"'
				];
			}

			if ($me->is_allowed_to('添加', 'fd_order')) {
				$panel_buttons['add'] = [
					'url' => URI::url('!food/fd_order/add'),
					'text'  => I18N::T('food','添加特殊订单'),
					'extra' => ' class="button button_add"'
				];
			}

			$total_expense = $orders->sum('price');
			$pagination = Lab::pagination($orders, $form['st'], 25);
			$this->add_css('food:common');	
			$this->layout->body->primary_tabs
				->select('fd_order')
				->content = V('fd_order/index',[
					'pagination'=>$pagination,
					'orders'=>$orders,
					'form'=>$form,
					'form_token'=>$form_token,
					'panel_buttons'=>$panel_buttons,
					'time'=>$time,
					'total_expense' => $total_expense,
				]);


		}

	}

	function _print_order($form, $orders) {

		if ($form && $orders) {
			$this->layout = V('records_print',['orders' => $orders, 'form'=>$form]);
		}
	}

	function _export_order($form, $orders) {

		$csv = new CSV('php://output', 'w');

		$csv->write([
			I18N::T('food', '预订人员'),
			I18N::T('food', '预订时间'),
			I18N::T('food', '菜式名称'),
			I18N::T('food', '菜式价格'),
			I18N::T('food', '备注')
		]);

		if ($orders->total_count() > 0) {

			$start = 0;
			$per_page = 100;

			while (1) {
				$pp_orders = $orders->limit($start, $per_page);
				if ($pp_orders->length() == 0) break;
				foreach ($pp_orders as $record) {	
					$csv->write([
						$record->user->name,               						//预订人员
						V('order_table/data/ctime', ['order'=>$record]),	//预订时间
						V('order_table/data/foods', ['order'=>$record]),	//菜式名称
						I18N::T('food', Number::currency($record->price)),  	//菜式价格
						$record->remarks      									//备注
					]);
				}
				$start += $per_page;	
			}
		}

		$csv->close();

	}


	function edit($order_id) {

		$fd_order = O('fd_order', $order_id);

		if (!$fd_order->id) {
			URI::redirect('error/404');
		}

		if (!L('ME')->is_allowed_to('修改', $fd_order)) {
			URI::redirect('error/401');
		}

		if ($fd_order->mode) {
			$this->_edit_special($fd_order);
		}
		else {
			$this->_edit_normal($fd_order);
		}

		$this->layout->body->primary_tabs
			->add_tab('edit', [
				'title' => I18N::T('food', '修改订餐'),
				'url' => $fd_order->url(NULL, NULL, NULL, 'edit'),
			])
			->select('edit');				
	}

	function _edit_normal($fd_order) {
		//修改正常订单

		$me = L('ME');
		$hour = Config::get('food.lockhour');
		$min = Config::get('food.lockmin');

		$ctime = $fd_order->ctime;
		$d_start = mktime(0, 0, 0, date('m', $ctime), date('d', $ctime));
		$lock_time = $d_start + $hour * 3600 + $min * 60;

		$selector = "food";
		$day = date('w', $ctime);
		$selector .= "[reserve*=$day]";
		$foods = Q($selector);
		$checked = json_decode($fd_order->foods, TRUE);

		if (Input::form('submit')) {

			$form = Form::filter(Input::form())
				->validate('user', 'not_empty', I18N::T('food', '订餐人员不能为空'));

			if ($form->no_error) {
				$user = O('user',$form['user']);
				$fd_ids = $form['fd_id'];

				if (!count($fd_ids)) {
					//勾掉了已经预订的菜式，则删除这个菜式
					if ($fd_order->delete()) {
						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food','订单已删除'));
						unset($fd_order);
					}
				}

				elseif (Config::get('food.only_one_order') && (count($form['fd_id']) > 1)) {
					//只可预订一份菜式设定
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('food', '抱歉，您只可预订一个菜式，请您重新选择'));
				}
				else {

					$fd_order->foods = json_encode($form['fd_id']);
					$fd_order->price = 0;

					//订单供应商初始化为空
					$supplier = [];

					foreach ($form['fd_id'] as $food=>$value) {

						// 订单价格统计
						$food = O('food', $food);
						$fd_order->price += $food->price;

						//订单供应商统计
						if (!in_array($food->supplier, $supplier)) {
							$supplier[] = $food->supplier;
						}
					}
					if (Config::get('food.only_one_supplier') && (count($supplier) >1)) {
						//只可预订一个供应商提供的菜式设定
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('food', '抱歉，您只可预订一个供应商的菜式，请您重新选择'));	
					}
					else {
						$fd_order->supplier = implode(' ', $supplier);
						$fd_order->user = $form['user'] ? O('user', $form['user']) : $me;
						$fd_order->remarks = $form['remarks'];
						$fd_order->ctime = $d_start;

						if ($fd_order->save()) {
							Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food','修改成功!'));
						}
						else {
							Lab::message(Lab::MESSAGE_ERROR, I18N::T('food','修改失败！'));
						}
					}	
				}	
				URI::redirect(URI::url('!food/fd_order'));
			}	
		}

		$suppliers = [];
		foreach ($foods as $food) {
			if (!in_array($food->supplier, $suppliers)) {
				array_push($suppliers, $food->supplier);
			}
		}

		$this->layout->body->primary_tabs
			->set('content', V('fd_order/edit.normal', [
				'time'=>$d_start,
				'foods'=>$foods, 
				'form'=>$form,
				'order'=>$fd_order, 
				'checked'=>$checked,
				'lock_time'=>$lock_time,
				'suppliers'=>$suppliers
								]));
		
		$this->add_css('food:index');
		
	}
	
	function _edit_special($fd_order) {
		//修改特殊订单
		$me = L('ME');
		$hour = Config::get('food.lockhour');
		$min = Config::get('food.lockmin');
		
		$ctime = $fd_order->ctime;
		$d_start = mktime(0, 0, 0, date('m', $ctime), date('d', $ctime));
		$lock_time = $d_start + $hour * 3600 + $min * 60;
		
		$selector = "food";
		$day = date('w', $ctime);
		$selector .= "[reserve*=$day]";
		$foods = Q($selector);
		$checked = json_decode($fd_order->foods, TRUE);
		
		if (Input::form('submit')) {
		
			$form = Form::filter(Input::form())
					->validate('user', 'not_empty', I18N::T('food', '订餐人员不能为空'));
					
			if ($form->no_error) {
				$user = O('user',$form['user']);
				
				
				//价格修改后增加记录
				if ($fd_order->price != $form['price']) {
				
					$fd_order_log = O('fd_order_log');
					$fd_order_log->user = $me;
					$fd_order_log->old_price = $fd_order->price;
					$fd_order_log->new_price = $form['price'];
					$fd_order_log->fd_order = $fd_order;
					
					$fd_order_log->save();	
				}
				
				$fd_order->foods = $form['mode'] ? $form['fd_id'] : json_encode($form['fd_id']);	
				$fd_order->supplier = $form['supplier'] ? : H('未知'); 
				$fd_order->user = $user;
				$fd_order->price = $form['price'];
				$fd_order->ctime = $form['time'];
				$fd_order->remarks = $form['remarks'];
				$fd_order->mode = 1;	
				
				if ($fd_order->save()) {
					Lab::message(Lab::MESSAGE_NORMAL,I18N::T('food','修改成功!'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR,I18N::T('food','修改失败！'));
				}
				URI::redirect(URI::url('!food/fd_order'));
			}	
		}
		$this->layout->body->primary_tabs
				->set('content', V('fd_order/edit.special', [
									'time'=>$d_start,
									'foods'=>$foods, 
									'form'=>$form,
									'order'=>$fd_order,
									'checked'=>$checked,
									'lock_time'=>$lock_time
									]));
		
		
	}
	
	
	function add() {
		
		if (!L('ME')->is_allowed_to('添加', 'fd_order')) {
			URI::redirect('error/401');
		}
		
		$fd_order = O('fd_order');
		if (Input::form('submit')) {
			$form = Form::filter(Input::form())
				->validate('user', 'not_empty', I18N::T('food', '订餐人员不能为空'));
				
			if ($form['price'] == 0 ) {
				$form->set_error('price', I18N::T('food', '消费金额不能为零'));
				
			}	
			if ($form->no_error) {
				$fd_order->supplier = $form['supplier'] ? : H('未知'); 
				$fd_order->user = $form['user'] ? O('user', $form['user']) : L('ME');
				$fd_order->price = $form['price'];
				$fd_order->foods = $form['foods'];
				$fd_order->ctime = mktime(0, 0, 0, date('m', $form['time']), date('d', $form['time']));
				$fd_order->remarks = $form['remarks']; 
				$fd_order->mode = 1;
				
				if ($fd_order->save()) {
					Lab::message(Lab::MESSAGE_NORMAL,I18N::T('food','新增成功!'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR,I18N::T('food','新增失败！'));
				}
				
			}	
			URI::redirect('!food/fd_order');	
		}
		
		$this->layout->body->primary_tabs
				->add_tab('add', [
						'title' => I18N::T('food', '增加订单'),
						'url' => $fd_order->url(NULL, NULL, NULL, 'add'),
				])
				->select('add')
				->set('content', V('fd_order/add', [
									'form'=>$form,
									]));
		
		
	
	}
	
	
	function delete($order_id) {
	
		$order = O('fd_order', $order_id);

		if (!L('ME')->is_allowed_to('删除', $order)) {
			URI::redirect('error/401');
		}
				
		if ($order->delete()) {
			Lab::message(Lab::MESSAGE_NORMAL,I18N::T('food','删除成功'));
		}	
		else {
			Lab::message(Lab::MESSAGE_ERROR,I18N::T('food','删除失败'));
		}
		
		URI::redirect(URI::url('!food/fd_order'));
		
	}	
	
	function print_sum() {
		
		if (!L('ME')->is_allowed_to('查看', 'fd_order')) {
			URI::redirect('error/401');
		}
		
		$orders = Q('fd_order:sort(ctime A):limit(1)');
		$start_time = $orders->current()->ctime;
		$orders = Q('fd_order:sort(ctime D):limit(1)');
		$end_time = $orders->current()->ctime;
		$this->layout = V('sum_orders', ['records' => $orders, 'start_time'=>$start_time, 'end_time'=>$end_time]);
		
	}
	
	function csv_sum() {
			
		if (!L('ME')->is_allowed_to('查看', 'fd_order')) {
			URI::redirect('error/401');
		}	
			
		$orders = Q('fd_order:sort(ctime A):limit(1)');
		$start_time = $orders->current()->ctime;
		
		$orders = Q('fd_order:sort(ctime D):limit(1)');
		$end_time = $orders->current()->ctime;
		
		$csv = new CSV('php://output', 'w');
				
		$csv->write([
				I18N::T('food', '订餐年费及月份'),
				I18N::T('food', '菜式份数总和（份）'),
				I18N::T('food', '菜式价格（元）')
			]);
		
		$time = $start_time;

		while ($time <= $end_time) {
		
			//获取记录当月的第一秒
			$first_second = mktime('0', '0', '0', date('m', $time), '1', date('Y', $time));
		
			//获取记录当月的最后一秒	
			$last_second = mktime('0', '0', '0', date('m', $time) + 1, '1', date('Y', $time)) - 1;
			
			$selector = 'fd_order';
			$selector .= "[ctime>$first_second][ctime<$last_second]";
			//设置时间为下月的第一秒
			$time = $last_second + 1;
			
			$price = Q($selector)->SUM('price');
			$length = count(Q($selector));
			
			$csv->write([
							H(date('Y-m', $first_second)), 	//订餐年费及月份
							$length,	//菜式份数总和 	
							H($price),		//菜式价格
							]);
		}
		
		$csv->close();	
		
	}
		
}
