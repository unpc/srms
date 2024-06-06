<?php

class Index_Controller extends Base_Controller {

	function index() {
		
		$form = Input::form();
		$me = $form['user'] ? O('user', Q::quote($form['user'])) : L('ME');

		//查看订餐人有没有预订记录
		$d_start = $form['time'] ? $form['time'] : mktime(0, 0, 0);
		$d_end = $d_start + 86400;
		
		//增加mode=0防止出现修改了特殊订单的情况发生
		$selector = "fd_order[user={$me}][ctime>=$d_start][ctime<$d_end][mode=0]";
		$order = Q($selector)->current();
		
		if (Input::form('submit')) {
			$fd_ids = $form['fd_id'];
			
			if (!count($fd_ids) && !$order->id) {
				//order->id为0，并且没有fd_ids，提示选择预订的菜式
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('food', '请您选择您要预订的菜式！'));
			}
			elseif (!count($fd_ids)) {
				//勾掉了已经预订的菜式，则删除这个菜式
				if ($order->delete()) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food','订单已删除'));
					unset($order);
				}
			}
			
			elseif (Config::get('food.only_one_order') && (count($form['fd_id']) > 1)) {
				//只可预订一份菜式设定
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('food', '抱歉，您只可预订一个菜式，请您重新选择'));
			}
			else {
			
				//新增订单
				$fd_order = $order->id ? $order : O('fd_order');
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
						Lab::message(Lab::MESSAGE_NORMAL, I18N::T('food','订餐成功!'));
					}
					else {
						Lab::message(Lab::MESSAGE_ERROR, I18N::T('food','订餐失败！'));
					}
				}
			}
		}
		
		$real_time = $fd_order->id ? $fd_order->ctime : $d_start;
		
		//得出当前可显示出来的食物
		$day = date('w', $real_time) ? : '7';
		$selector = "food[reserve*=$day]";
		$foods = Q($selector);
		
		//获取锁定时间值			
		$hour = Config::get('food.lockhour');
		$min = Config::get('food.lockmin');
		$lock_time = mktime($hour, $min, 0, date('m', $real_time), date('d', $real_time), date('y', $real_time));
		
		//获取供应商
		$suppliers = [];
		foreach ($foods as $food) {
			if (!in_array($food->supplier, $suppliers)) {
				array_push($suppliers, $food->supplier);
			}
		}
		
		$content = V('index',[
						'foods' => $foods, 
						'form' => $form,
						'order'=>$fd_order->id ? $fd_order : $order,
						'lock_time'=>$lock_time,
						'suppliers'=>$suppliers
					]);
		
		$this->add_css('food:index');
		$this->add_css('preview');
		$this->add_js('preview');
		$this->layout->body->primary_tabs
				->select('index')
				->set('content', $content);
				
	}
	
}

class Index_AJAX_Controller extends AJAX_Controller {
	
	function index_preview_click() {
		$form = Input::form();
		$food = O('food', $form['id']);
		if (!$food->id) return FALSE;
		Output::$AJAX['preview'] = (string)V('food:preview', ['food'=>$food]);
	}
	
	
	function index_change_user_change() {
		
		$form = Input::form();
		$user = O('user', $form['user_id']);
		if(!$user->id) return FALSE;
		
		//查看订餐人是否有订餐记录
		$d_start = $form['time'] ? $form['time'] : mktime(0, 0, 0);
		$d_end = $d_start + 86400;
		$selector = "fd_order[user={$user}][ctime>=$d_start][ctime<$d_end]";
		$order = Q($selector)->current();
		
		if ($order->id) $checked = json_decode($order->foods);
		
		Output::$AJAX['value'] = $checked;
	}	
	
}
