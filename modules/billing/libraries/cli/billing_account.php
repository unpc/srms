<?php
class CLI_Billing_Account{
    static function update_balance () {
        $caculate_lock = Lab::get('account.caculate_lock');
        if ($caculate_lock) {
            return;
        }

        $caculate_queue = array_unique(Lab::get('account.caculate_queue') ? : []);
        if (!$caculate_queue) return;
        Lab::set('account.caculate_lock', TRUE);
        Lab::set('account.caculate_queue', []);
        foreach ($caculate_queue as $account_id) {
            $account = O('billing_account', $account_id);
            $account->id && $account->update_balance();
        }
        Lab::set('account.caculate_lock', FALSE);
    }

    static function update_all_balance () {
        Lab::set('account.caculate_lock', TRUE);
        foreach (Q("billing_account") as $account) {
            $account->update_balance();
        }
        Lab::set('account.caculate_lock', FALSE);
    }

	//检查课题组的余额是否与实际情况相符
	static function check () {
		$accounts = Q('billing_account');
		$ids = [];
		foreach ($accounts as $index => $account) {
			if ($account->lab->hidden == 1) {
				continue;
			}

			$confirmed_status = Billing_Transaction_Model::STATUS_CONFIRMED;

			$db = Database::factory();
			//有效远程充值
			$income_remote_confirmed = (double) $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND income > 0 AND status = %d AND source != 'local'", $account->id, $confirmed_status);
			//远程充值
			$income_remote = (double) $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND income > 0 AND source != 'local'", $account->id);
			//有效本地充值
			$income_local = (double) $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND income > 0 AND source = 'local' AND transfer = 0", $account->id) + 
				(double) $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND income < 0 AND source = 'local' AND transfer = 0", $account->id);
			//转入调账
			$income_transfer = (double) $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND transfer > 0", $account->id);
			//远程扣费
			$outcome_remote = (double) $db->value("SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d AND outcome > 0 AND status = %d AND source != 'local' AND transfer = 0", $account->id, $confirmed_status);
			//本地扣费
			$outcome_local = (double) $db->value("SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d AND outcome > 0 AND source = 'local' AND transfer = 0 AND manual > 0", $account->id);
			//使用
			$outcome_use = (double) $db->value("SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d AND outcome > 0 AND source = 'local' AND transfer = 0 AND manual = 0", $account->id);
			//转出调账
			$outcome_transfer = (double) $db->value("SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d AND outcome > 0 AND transfer > 0 ", $account->id);
			//平账操作脏数据
			$balance_income_transfer = (double) $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND income < 0 AND transfer = 0 ", $account->id);
			$balance_outcome_transfer = (double) $db->value("SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d AND outcome < 0 AND transfer = 0 ", $account->id);

			$balance = (double) (($income_remote_confirmed +
					$income_local + $income_transfer) -
					($outcome_remote + $outcome_local +
					$outcome_use + $outcome_transfer)
				);

			if (round($balance, 2) != round($account->balance, 2)) {
                $ids[$account->id] = I18N::T('billing_account', '提醒: 财务账号[%id]余额错误! 目前余额[%now] 计算余额[%balance]', [
					'%id' => $account->id,
					'%now' => round($balance, 2),
					'%balance' => round($account->balance, 2),
				]);
			}
		}
		if (count($ids)) {
			$subject = Config::get('page.title_default') . '财务检查报告' . '[' . LAB_ID . ']';
			$body = I18N::T('billing_account', "提醒: 财务检查报告错误!\n%ids", [
					'%ids' => join("\r\n", $ids)
				]);
			$mail = new Email();
			$mail->to('support@geneegroup.com');
			$mail->subject($subject);
			$mail->body(NULL, $body);
			$mail->send();
		}
	}

	static function refill_notification() {
		$now = time();

		if($GLOBALS['preload']['billing.single_department']){
			$type = 'notification.billing.refill.unique';
			$notif_key = 'billing.refill.unique';
		}
		else{
			$type = 'notification.billing.refill';
			$notif_key = 'billing.refill';
		}

		$settings = (array) Lab::get($type) + (array) Config::get($type);
        $enable_notification = $settings['enable_notification'];
        $enable_min_credit_per = $settings['enable_min_credit_per'];
        $enable_balance = $settings['enable_balance'];

		if ($enable_notification) {
			$balance = $settings['balance'];
			$period = $settings['period'] * 86400; // 24 * 60 * 60;

			$labs = Q('lab');
			$departments = Q('billing_department');
			foreach ($labs as $lab) {
				if (!$lab->owner->id) continue;
				foreach ($departments as $dept) {
					$account = O('billing_account', ['lab'=>$lab, 'department'=>$dept]);
                    // 最后一次触发消息提醒的充值明细
                    $transaction = Q("billing_transaction[account={$account}][income>0]:sort(id D):limit(1)")->current();

					if (!$account->id) continue;

					if (
					    // 最小余额判断的逻辑
					    ($enable_balance && (float)$account->balance < (float)$balance) ||
                        // 按百分比判断
                        ($enable_min_credit_per && ($account->balance * 100 / $account->credit_line) < $settings['min_credit_per'])
                    ) {
                        $sent_date = (int) $account->last_refill_notification_date;
                        // 上一次消息提醒最近一次的充值记录 = 本次消息提醒时最近一次的充值记录(也就是说上一次发过消息后没有进行过充值)
                        if ($account->last_income_transaction == $transaction->id) {
                            // 这一周期（一周）内不再发送消息提醒
                            if ($sent_date && $sent_date + $period >= $now) continue;
                        }
		                Notification::send($notif_key, $lab->owner, [
		                	'%lab'=>Markup::encode_Q($lab),
		                    '%department'=>Markup::encode_Q($dept),
		                    '%balance'=>$account->balance,
		                    '%alert_line'=>Number::currency($balance),
                            '%min_credit_per'=>$settings['min_credit_per'] . '%',
		                ]);
                        // 存在 $account 中
                        $account->last_refill_notification_date = $now;
                        $account->last_income_transaction = $transaction->id; // 触发消息提醒的最后一次充值明细
                        $account->save();
					}

				}
			}
		}
	}

	//为所有课题组充值
	static function deposit_for_labs(){
		$income = null;
		while (is_null($income))
		{
			fwrite(STDOUT, '请输入默认的充值金额：');
			$income = fgets(STDIN);
		}

		$departments = Q('billing_department')->to_assoc('id', 'name');
		if (count($departments)<1)
		{
			fwrite(STDOUT, '系统中尚未创建财务中心！');
			exit;
		}
		foreach ($departments as $id=>$name)
		{
			fwrite(STDOUT, "{$id}\t{$name}\n");
		}

		$id = null;
		while (!isset($departments[$id]))
		{
			fwrite(STDOUT, '请选择一个财务中心：');
			$id = (int)fgets(STDIN);
		}

		$department = O('billing_department', $id);

		$failed_labs = [];
		$labs = Q('lab');
		foreach ($labs as $lab)
		{
			$account = Q("billing_account[department={$department}][lab={$lab}]:limit(1)")->current();
			if (!$account->id)
			{
				$account = O('billing_account');
				$account->lab = $lab;
				$account->department = $department;
				$account->save();
			}
			if (!$account->id)
			{
				$failed_labs[] = $lab;
			}
			else
			{
				$transaction = O('billing_transaction');
				$transaction->account = $account;
				if ($income < 0)
				{
					$transaction->outcome = abs($income);
					$transaction->description = "每个实验室扣费{$income}元。";
				}
				else
				{
					$transaction->income = $income;
					$transaction->description = "每个实验室充值{$income}元。";
				}
				$transaction->save();
				fwrite(STDOUT, "\t{$lab->name}\t充值成功。\n");
			}
		}

		foreach ($failed_labs as $lab)
		{
			fwrite(STDOUT, "以下实验室充值失败：\n");
			fwrite(STDOUT, "{$lab->id}\t{$lab->name}\n");
		}
	}

	//为没有财务账号的课题组充值
	static function deposit_for_new_labs() {
		$income = null;
		while (is_null($income))
		{
			fwrite(STDOUT, '请输入默认的充值金额：');
			$income = fgets(STDIN);
		}

		$departments = Q('billing_department')->to_assoc('id', 'name');
		if (count($departments)<1)
		{
			fwrite(STDOUT, '系统中尚未创建财务中心！');
			exit;
		}
		foreach ($departments as $id=>$name)
		{
			fwrite(STDOUT, "{$id}\t{$name}\n");
		}

		$id = null;
		while (!isset($departments[$id]))
		{
			fwrite(STDOUT, '请选择一个财务中心：');
			$id = (int)fgets(STDIN);
		}

		$department = O('billing_department', $id);

		$failed_labs = [];
		$labs = Q('lab');
		foreach ($labs as $lab) {
			$account = Q("billing_account[department={$department}][lab={$lab}]:limit(1)")->current();
			if (!$account->id) {
				$account = O('billing_account');
				$account->lab = $lab;
				$account->department = $department;
				if ($account->save()) {

					$transaction = O('billing_transaction');
					$transaction->account = $account;
					if ($income < 0)
					{
						$transaction->outcome = abs($income);
						$transaction->description = "每个实验室扣费{$income}元。";
					}
					else
					{
						$transaction->income = $income;
						$transaction->description = "每个实验室充值{$income}元。";
					}
					$transaction->save();
					fwrite(STDOUT, "\t{$lab->name}\t充值成功。\n");
				}
				else {
					$failed_labs[] = $lab;
				}
			}
		}

		foreach ($failed_labs as $lab)
		{
			fwrite(STDOUT, "以下实验室充值失败：\n");
			fwrite(STDOUT, "{$lab->id}\t{$lab->name}\n");
		}
	}

	//对文件中的课题组进行充值
	static function deposit_for_some_labs($file) {
		$f = fopen($file, 'r') or die("usage: SITE_ID=cf LAB_ID=nankai cli.php deposit_for_some_labs ~/lims2/cli/labs.txt\n");
		$lab_names = explode(',', fgets($f));
		$labs = [];
		$not_exist_labs = [];
		foreach ($lab_names as $lab_name) {
			$lab = O('lab', ['name' => trim($lab_name)]);
			if (!$lab->id) {
				$not_exist_labs[] = $lab_name;
				continue;
			}
			$labs[] = $lab;
		}

		$income = null;
		while (is_null($income))
		{
			fwrite(STDOUT, '请输入默认的充值金额：');
			$income = fgets(STDIN);
		}

		$departments = Q('billing_department')->to_assoc('id', 'name');
		if (count($departments)<1)
		{
			fwrite(STDOUT, '系统中尚未创建财务中心！');
			exit;
		}
		foreach ($departments as $id=>$name)
		{
			fwrite(STDOUT, "{$id}\t{$name}\n");
		}

		$id = null;
		while (!isset($departments[$id]))
		{
			fwrite(STDOUT, '请选择一个财务中心：');
			$id = (int)fgets(STDIN);
		}

		$department = O('billing_department', $id);

		$failed_labs = [];
		foreach ($labs as $lab) {
			$account = Q("billing_account[department={$department}][lab={$lab}]:limit(1)")->current();
			if (!$account->id) {
				$account = O('billing_account');
				$account->lab = $lab;
				$account->department = $department;
				if ($account->save()) {

					$transaction = O('billing_transaction');
					$transaction->account = $account;
					if ($income < 0)
					{
						$transaction->outcome = abs($income);
						$transaction->description = "每个实验室扣费{$income}元。";
					}
					else
					{
						$transaction->income = $income;
						$transaction->description = "每个实验室充值{$income}元。";
					}
					$transaction->save();
					fwrite(STDOUT, "\t{$lab->name}\t充值成功。\n");
				}
				else {
					$failed_labs[] = $lab;
				}
			}
		}

		foreach ($failed_labs as $lab)
		{
			fwrite(STDOUT, "以下实验室充值失败：\n");
			fwrite(STDOUT, "{$lab->id}\t{$lab->name}\n");
		}
	}

    /**
     * 为当前已激活的课题组在指定的财务中心下添加账号
     * 设置默认信用额度
     */
	public static function createAccountForLab(){

        $departments = Q('billing_department')->to_assoc('id', 'name');
        if (count($departments)<1)
        {
            fwrite(STDOUT, '系统中尚未创建财务中心！');
            exit;
        }
        foreach ($departments as $id=>$name)
        {
            fwrite(STDOUT, "{$id}\t{$name}\n");
        }

        $departmentId = null;
        while (!isset($departments[$departmentId]))
        {
            fwrite(STDOUT, '请选择一个财务中心：');
            $departmentId = (int)fgets(STDIN);
        }

        $credit = 1000;
        $i = 0;
        while ($i == 0)
        {
            fwrite(STDOUT, '请输入充值信用额度,默认¥1000：');
            $credit = fgets(STDIN);
            $i++;
        }

        //获取已激活的课题组
        $success = $failed = [];
        $labs = Q("lab[atime>0]");
        $department = O('billing_department', $id);
        foreach($labs as $lab) {
            $check_account = Q("billing_account[department={$department}][lab={$lab}]:limit(1)")->current();
            if (!$check_account->id) {
                $account = O('billing_account');
                $account->lab = $lab;
                $account->department = $department;
                $account->credit_line = floatval($credit);
                if ($account->save()) {
                    $success[] = $lab;
                    /* 记录日志 */
                    Log::add(strtr('[billing] %user_name[%user_id]添加了%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务帐号[%account_id]', [

                        '%user_name' => 'system_cli',
                        '%user_id' => 0,
                        '%lab_name' => $account->lab->name,
                        '%lab_id' => $account->lab->id,
                        '%department_name' => $account->department->name,
                        '%department_id' => $account->department->id,
                        '%account_id' => $account->id,
                    ]), 'journal');
                } else {
                    $failed[] = $lab;
                }
            }
        }
        foreach ($failed as $l)
        {
            fwrite(STDOUT, "以下实验室充值失败：\n");
            fwrite(STDOUT, "{$l->id}\t{$l->name}\n");
        }
        fwrite(STDOUT, "[done]");
    }
}
