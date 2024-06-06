<?php
	
class Food {

	static function operate_food_is_allowed($e, $user, $perm, $food, $params ) {
		switch ($perm) {
		case '添加' :
		case '修改' :
		case '删除' :
			if ($user->access('添加/修改菜式')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;	
		case '指定人员' :
			if ($user->access('指定订餐人员')) {
				$e->return_value = TRUE;
				return FALSE;
			}	
			break;
		}
	}

	static function setup_profile() {
		Event::bind('profile.view.tab', 'Food::index_profile_tab', 100);
		Event::bind('profile.view.content', 'Food::index_profile_content', 10, 'food');
	}

	static function index_profile_tab($e, $tabs) {
		$user = $tabs->user;
		$me = L('ME');

		if ($me->id == $user->id || $me->access('查看订单记录')) {
			$tabs->add_tab('food', [
				'url' => $user->url('food'),
				'title' => I18N::HT('food', '订餐情况'),
			]);
		}
	}

	static function index_profile_content($e, $tabs) {
		$user = $tabs->user;
		$tabs->content = V('food:profile', ['user'=>$user]);
	}


}
