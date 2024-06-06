<?php

class Grants {

	static function grant_ACL($e, $user, $perm_name, $grant, $opt) {
		
		switch($perm_name) {
			case '列表':
			case '查看':
				$e->return_value = TRUE;
				return FALSE;
				break;
			case '添加':
			case '修改':
			case '删除':
			case '添加支出':
			case '修改支出':
				if ($user->access('管理所有经费')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '导出':
				$e->return_value = TRUE;
				return false;
				break;
		}
	}
	
	static function grant_expense_ACL($e, $user, $perm_name, $expense) {
		switch($perm_name) {
			case '修改':
				if ($expense->is_locked()) {
					$e->return_value = FALSE;
					return FALSE;
				}
				
				if ($user->is_allowed_to('修改支出', $expense->grant)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
		}
	}
	
	
	static function expense_is_locked($e, $expense, $params) {
		if (Q("order[expense={$expense}]")->total_count()) {
			$e->return_value = TRUE;
			return TRUE;
		}
	}
	
	static function user_general_sections($e, $user, $sections) {
	
		$grants = Q("grant[user={$user}]");
		
		if (count($grants)) {
			$sections[] = V('grants:grant/user_general_section', ['grants'=>$grants]);
		}	
	}

	static function grant_newsletter_content($e, $user) {

		$templates = Config::get('newsletter.template');
		$db = Database::factory();

		$template = $templates['finance']['grant_collect'];
		$sql = "SELECT id,project,balance FROM `grant`";
		$query = $db->query($sql);
		if ($query) {
			$grants = $query->rows();
			$str .= V('grants:newsletter/grant_collect', [
				'grants' => $grants,
				'template' => $template,
			]);
		}

        //今天过期
        $today_past_due = $templates['finance']['grant_today_past_due'];

        $today = mktime(0, 0, 0);
        $midnight = mktime(23, 59, 59);

        $grants = Q("grant[dtend={$today}~$midnight]");

        $str .= V('grants:newsletter/today_past_due', [
            'grants'=> $grants,
            'template' => $today_past_due
        ]);

        $near_remind_template = $templates['finance']['grant_near_remind_time'];

        $g = [];
        foreach(Q('grant') as $grant) {
            if (($grant->dtend - $today > 0) && ($grant->dtend - $today < $grant->remind_time * 86400)) {
                $g[] = $grant;
            }
        }

        $str .= V('grants:newsletter/near_remind_template', [
            'grants'=> $g,
            'template'=> $near_remind_template
        ]);

		if (strlen($str) > 0) {
			$view = V('grants:newsletter/view', [
					'str' => $str,
			]);
			$e->return_value .= $view;
		}
	}

}
