<?php

class Account_Controller extends Base_Controller {

	function index() {
		URI::redirect('error/404');
	}
}

class Account_AJAX_Controller extends AJAX_Controller {

	function index_add_account_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$department = O('billing_department', Input::form('id'));
		if (!$department->id) return;
		if (!$me->is_allowed_to('添加财务帐号', $department)) return;

 		$selector = "lab:not(billing_account[department={$department}] lab)";

		if (!$me->is_allowed_to('添加财务帐号', 'billing_department')) {
			$selector = "{$me}<group tag[parent] " . $selector;
		}


		$selector .= ':sort(name_abbr)';
		$count_labs = Q($selector)->total_count();
		if (!$count_labs) {
			JS::alert(I18N::T('billing', '没有实验室需要添加帐号'));
			return;
		}

		JS::dialog(V('account/edit',['department'=>$department]), ['title' => I18N::T('billing', '添加财务帐号')]);
	}

	function index_delete_account_click() {
		$form = Form::filter(Input::form());
		if (!isset($form['account_id'])) return;

		$account = O('billing_account', $form['account_id']);
		if (!$account->id) return;

		if (!L('ME')->is_allowed_to('删除', $account)) return;

		if (!JS::confirm(I18N::T('billing', '您确定要删除该帐号吗？请谨慎操作！'))) return;

		$transactions = Q("billing_transaction[account={$account}][income!=0|outcome!=0]");
		if (count($transactions) == 0)  {
			//删除隐藏的明细
			$no_hide_transactions = TRUE;
			$hide_transactions = Q("billing_transaction[account={$account}]");
			foreach($hide_transactions as $transaction ) {
				if( $transaction->income !=0 || $transaction->outcome != 0 ){
					$no_hide_transactions = FALSE;
					break;
				} else {
					$transaction->delete();
				}
			}
			if ($no_hide_transactions && $account->delete()) {
				/* 记录日志 */
				Log::add(strtr('[billing] %user_name[%user_id]删除了%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务帐号[%account_id]', [
							'%user_name' =>  L('ME')->name,
							'%user_id' => L('ME')->id,
							'%lab_name' => $account->lab->name,
							'%lab_id' => $account->lab->id,
							'%department_name' => $account->department->name,
							'%department_id' => $account->department->id,
							'%account_id' => $account->id,
				]), 'journal');
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '成功删除财务帐号 %account !', [
					'%account'=>H($account->lab->name)
				]));
				JS::refresh();
			}
			else {
				JS::alert(I18N::T('billing','帐号删除失败!'));
			}
		}
		else {
			JS::alert(I18N::T('billing','该帐号下存在记录!您不能删除!'));
		}
	}

	function index_add_account_submit() {
		$form = Form::filter(Input::form());
		$department = O('billing_department', $form['department_id']);
		if (!$department->id) return;

		$lab = O('lab', $form['query_lab']);
		if(!$lab->id) {
			$form->set_error('query_lab', I18N::T('billing', '请选择正确的实验室！'));
			JS::dialog(V('account/edit',['department'=>$department, 'labs'=>$labs, 'form'=>$form]), ['title' => I18N::T('billing', '添加财务帐号')]);
			return;
		}
		$check_account = Q("billing_account[department={$department}][lab={$lab}]:limit(1)")->current();
		if (!$check_account->id) {
			$account = O('billing_account');
			$account->lab = $lab;
			$account->department = $department;

			if (!L('ME')->is_allowed_to('添加', $account)) return;
			if ($account->save()) {
				/* 记录日志 */
				Log::add(strtr('[billing] %user_name[%user_id]添加了%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务帐号[%account_id]', [

							'%user_name' => L('ME')->name,
							'%user_id' => L('ME')->id,
							'%lab_name' => $account->lab->name,
							'%lab_id' => $account->lab->id,
							'%department_name' => $account->department->name,
							'%department_id' => $account->department->id,
							'%account_id' => $account->id,
				]), 'journal');

				Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('billing', '为%account_name成功添加财务帐号！', ['%account_name'=>H($lab->name)]));
			}
			else {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '添加财务帐号失败！'));
			}

			JS::close_dialog();
			JS::refresh();
		}
		else {
			JS::alert(I18N::T('billing', '同一财务部门下每个实验室只允许有一个帐号!'));
		}
	}

	function index_preview_click() {
		$form = Input::form();
		$lab = O('lab',$form['lab_id']);
		if (!$lab->id) return;
		Output::$AJAX['preview'] = (string)V('billing:account/preview', ['lab'=>$lab]);
	}

	function index_refill_notif_click() {
		$form = Input::form();
		JS::dialog(V('account/remote_refill_notification', [
							'lab_id'=>$form['lab_id'],
			]), ['title'=>I18N::T('billing', '充值帮助'), 'width'=>800]);

	}

	function index_refill_redirect_click() {
		$http_url = Input::form('http_url');
		JS::redirect($http_url);
	}

	function index_account_credit_click() {

		$form = Input::form();
		$me = L('ME');
		$unique_billing_department = $GLOBALS['preload']['billing.single_department'];

		$account = O('billing_account', $form['account_id']);

		//如果账号存在远程账号,则不允许充值
		if($account->id && $account->source != 'local' && $account->voucher) return;

		$lab = $account->id ? $account->lab : O('lab', $form['lab_id']);

		$user_lab_refill_check = Billing_Account::user_lab_refill_check($me, $lab);
		if (!$user_lab_refill_check) return;

		if ($unique_billing_department) {
			$department = Billing_Department::get();
			$account = $account->id ? $account : O('billing_account', ['lab'=>$lab, 'department'=>$department]);
		}

		if (!$unique_billing_department && !$account->id) {
			JS::dialog(V('account/transaction_credit', [
										'form'=>$form,
										'lab'=>$lab
						]), ['title'=>I18N::T('billing', '财务帐号充值')]);
		}
		else {
			JS::dialog(V('account/credit', [
										'account'=>$account,
										'form'=>$form,
										'lab'=>$lab
						]), ['title'=>I18N::T('billing', '财务账号充值')]);
		}
	}

	function index_account_deduction_click() {

		$form = Input::form();
		$me = L('ME');
		$unique_billing_department = $GLOBALS['preload']['billing.single_department'];

		$account = O('billing_account', $form['account_id']);
		$lab = $account->id ? $account->lab : O('lab', $form['lab_id']);

		$user_lab_refill_check = Billing_Account::user_is_lab_deduction($me, $lab);
		if (!$user_lab_refill_check) return;

		if ($unique_billing_department) {
			$department = Billing_Department::get();
			$account = $account->id ? $account : O('billing_account', ['lab'=>$lab, 'department'=>$department]);
		}

		if (!$unique_billing_department && !$account->id) {
			JS::dialog(V('account/transaction_deduction', [
										'form'=>$form,
										'lab'=>$lab
						]), ['title'=>I18N::T('billing', '财务帐号扣费')]);
		}
		else {
			JS::dialog(V('account/deduction', [
										'account'=>$account,
										'form'=>$form,
										'lab'=>$lab
						]), ['title'=>I18N::T('billing', '财务帐号扣费')]);
		}
	}



	function index_refill_deduction_submit () {
		$form = Input::form();
		$me = L('ME');
		$unique_billing_department = $GLOBALS['preload']['billing.single_department'];

		if ($form['account_id']) {
			$account = O('billing_account', $form['account_id']);
		}
		else {
			$account = O('billing_account', [
				'lab_id' => $form['lab_id'],
				'department_id' => $form['department_id']
			]);
		}

		$lab = O('lab', $form['lab_id']);
		$department = O('billing_department', $form['department_id']);

		if (!$account->id && !O('billing_account', ['lab' => $lab, 'department' => $department])->id) {
			JS::alert(I18N::T('billing', '请选择财务部门!'));
			return FALSE;
		}

		if ($me->is_allowed_to('修改扣费人员', $account)) {
			$user = O('user', $form['user_id']);
			if (!$user->id) {
				if ($form['type'] == 'deduct') {
					JS::alert(I18N::T('billing', '请选择扣费人员!'));
				}
				else {
					JS::alert(I18N::T('billing', '请选择充值人员!'));
				}
				return;
			}
		}
		else {
			$user = $me;
		}

		$amount = round($form['amount'], 2);

		if ($form['type'] == 'refill') {
			if (!$me->is_allowed_to('充值', $account)) return;

			//如果存在远程账号，则不允许充值
			if($account->source !='local' && !$account->voucher) return;

		    if($amount <= 0) {
		        JS::alert(I18N::T('billing', '充值金额需大于零!'));
		        return FALSE;
		    }

			/*
			之所以没用 extra.form.validate 是因为怕 hook 出来得太多(例如eq_sample等，即使判断了类型，代码也是会运行到其中的)导致冲突
			这个地方是弹框显示，用的是 JS::alert 验证表单，所以只能在 trigger 前加 if 进行判断报错
			*/
			if (Event::trigger('billing.refill.extra.form.validate', $account, $form)) {
				return FALSE;
			}
			
			
            if (!in_array($form['credit_type'], [Billing_Account_Model::OPERATION_TRANSFER, Billing_Account_Model::OPERATION_CREDIT])) return FALSE;

            switch($form['credit_type']) {
				case Billing_Account_Model::OPERATION_CREDIT : //充值
					$requires = Config::get('form.billing_deduction')['requires'];
					foreach ($requires as $key => $val) {
						if (!$form[$key]) {
							JS::alert(I18N::T('billing', $val));
							return FALSE;
						}
					}

                    if (isset($form['code']) && !$form['code']) {
                        JS::alert(I18N::T('billing', '请填写转出项目代码!'));
                        return FALSE;
                    }

                    $confirm = I18N::T('billing', '你确定要充值吗? 请确保充值数据正确无误! \n实验室: %lab\n充值部门: %depart\n充值金额: %income\n凭证号: %certificate\n充值人员: %user\n', [
                        '%user' => $user->name,
                        '%lab' => $account->lab->name,
                        '%income' => Number::currency($amount),
                        '%depart' => ($account->department->name ?$account->department->name : NULL),
                        '%certificate' => $form['certificate'],
                    ]);

                    if (isset($form['code']) && isset($form['project_type'])) {
                        $confirm = I18N::T('billing', '你确定要充值吗? 请确保充值数据正确无误! \n实验室: %lab\n充值部门: %depart\n充值金额: %income\n凭证号: %certificate\n转出项目代码: %code\n项目类别: %project_type\n充值人员: %user\n', [
                            '%user' => $user->name,
                            '%lab' => $account->lab->name,
                            '%income' => Number::currency($amount),
                            '%depart' => ($account->department->name ?$account->department->name : NULL),
                            '%certificate' => $form['certificate'],
                            '%code' => trim($form['code']),
                            '%project_type' => Config::get('billing.refill_project_type')[$form['project_type']],
                        ]);
                    }

                    if (!JS::confirm($confirm)) return;

                    $transaction = O('billing_transaction');
                    $transaction->account = $account;
                    $transaction->user = $user;
                    $transaction->income = $amount;
                    $transaction->transaction_date = $form['transaction_date'];
                    $transaction->certificate = $form['certificate'];
                    if (isset($form['code']) && isset($form['project_type'])) {
                        $transaction->code = trim($form['code']);
                        $transaction->project_type = $form['project_type'];
                    }
                    $transaction->description = [
                        'module'=>'billing',
                        'template' => I18N::T('billing', '%user 对 %account 进行充值'),
                        '%user'=>Markup::encode_Q($user),
                        '%account'=>Markup::encode_Q($account->lab),
                        'amend'=>$form['description'],
                    ];
                    $type = I18N::T('billing', '充值');

                    $account_old_balance = $account->balance;

                    //手动操作增加manual flag
                    $transaction->manual = TRUE;

                    if ($transaction->save()) {
                        Log::add(strtr('[billing] %user_name[%user_id]对%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务帐号[%account_id]充值%charge, 充值前帐号余额%old_balance, 充值后帐号余额%balance', [
                            '%user_name' => $me->name,
                            '%user_id' => $me->id,
                            '%lab_name' => $account->lab->name,
                            '%lab_id' => $account->lab->id,
                            '%department_name' => $account->department->name,
                            '%department_id' => $account->department->id,
                            '%account_id' => $account->id,
                            '%charge' => sprintf('%.2f', $amount),
                            '%old_balance' => sprintf('%.2f', $account_old_balance),
                            '%balance' =>  sprintf('%.2f', $account->balance),
                        ]), 'journal');
						
						$notif_key = 'billing.account_credit';

						Notification::send($notif_key, $account->lab->owner, [
							'%user' => Markup::encode_Q($me),
							'%amount'=> Number::currency($amount),
							'%time'=> Date::format(Date::time(), 'Y/m/d H:i:s'),
							'%dept'=> H($account->department->name),
							'%balance'=> Number::currency($account->balance)
						]);

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '充值成功!'));
                    }

                    break;
                case Billing_Account_Model::OPERATION_TRANSFER : //调账
                    $from_account = O('billing_account', ['department'=> $account->department, 'id'=> $form['from_account']]);

                    //判断是否正常来源账号
                    if (!$from_account->id) {
                        JS::alert(I18N::T('billing', '请选择正确费用来源!'));
                        return FALSE;
                    }

                    $confirm = I18N::T('billing','你确定要调账吗? 请确保调账数据正确无误! \n实验室: %lab\n充值部门: %depart\n费用来源: %from_account\n充值金额: %income\n凭证号: %certificate\n充值人员: %user\n', [
                        '%user' => $user->name,
                        '%lab' => $account->lab->name,
                        '%income' => Number::currency($amount),
                        '%depart' => ($account->department->name ?$account->department->name : NULL),
                        '%certificate' => $form['certificate'],
                        '%from_account'=> $from_account->lab->name,
                    ]);

                    if (!JS::confirm($confirm)) return;

                    $from_account_old_balance = $from_account->balance;

                    //进行调账
                    $transfer_transaction = O('billing_transaction');
                    $transfer_transaction->account = $from_account;
                    $transfer_transaction->user = $user;
                    $transfer_transaction->outcome = $amount; //调账 进行扣费
                    $transfer_transaction->transaction_date = $form['transaction_date'];
                    $transfer_transaction->certificate = $form['certificate'];
                    $transfer_transaction->description = [
                        'module'=>'billing',
                        'template'=> I18N::T('billing', '%user 对 %from_account 进行扣费调账 (费用转向到 %account)'),
                        '%user'=>Markup::encode_Q($user),
                        '%account'=>Markup::encode_Q($account->lab),
                        'amend'=>$form['description'],
                        '%from_account'=> Markup::encode_Q($from_account->lab),
                    ];

                    //手动操作增加manual flag
                    $transfer_transaction->manual = TRUE;
                    //调账
                    $transfer_transaction->transfer = TRUE;

                    $transfer_success = $transfer_transaction->save();



                    //充值收费
                    $transaction = O('billing_transaction');
                    $transaction->account = $account;
                    $transaction->user = $user;
                    $transaction->income = $amount;
                    $transaction->transaction_date = $form['transaction_date'];
                    $transaction->certificate = $form['certificate'];
                    $transaction->description = [
                        'module'=>'billing',
                        'template' => I18N::T('billing', '%user 对 %account 进行充值调账 (费用来源于 %from_account)'),
                        '%user'=>Markup::encode_Q($user),
                        '%account'=>Markup::encode_Q($account->lab),
                        '%from_account'=> Markup::encode_Q($from_account->lab),
                        'amend'=>$form['description'],
                    ];

                    //手动操作增加manual flag
                    $transaction->manual = TRUE;
                    //调账
                    $transaction->transfer = TRUE;

                    $account_old_balance = $account->balance;

                    //进行充值操作
                    $credit_success = $transaction->save();


                    //均成功!
                    if ($transfer_success && $credit_success) {

                        //调账
                        Log::add(strtr('[billing] %user_name[%user_id]对%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务帐号[%account_id]调账%charge, 调账前帐号余额%old_balance, 调账后帐号余额%balance', [
                            '%user_name' => $me->name,
                            '%user_id' => $me->id,
                            '%lab_name' => $from_account->lab->name,
                            '%lab_id' => $from_account->lab->id,
                            '%department_name' => $from_account->department->name,
                            '%department_id' => $from_account->department->id,
                            '%account_id' => $from_account->id,
                            '%charge' => sprintf('%.2f', $amount),
                            '%old_balance' => sprintf('%.2f', $from_account_old_balance),
                            '%balance' =>  sprintf('%.2f', $from_account->balance),
                        ]), 'journal');

                        //充值
                        Log::add(strtr('[billing] %user_name[%user_id]对%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务帐号[%account_id]调账%charge, 调账前帐号余额%old_balance, 调账后帐号余额%balance', [
                            '%user_name' => $me->name,
                            '%user_id' => $me->id,
                            '%lab_name' => $account->lab->name,
                            '%lab_id' => $account->lab->id,
                            '%department_name' => $account->department->name,
                            '%department_id' => $account->department->id,
                            '%account_id' => $account->id,
                            '%charge' => sprintf('%.2f', $amount),
                            '%old_balance' => sprintf('%.2f', $account_old_balance),
                            '%balance' =>  sprintf('%.2f', $account->balance),
                        ]), 'journal');

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '调账成功!'));

                        //成功, 发送notification
                        $success = TRUE;
                    }
                    else {
                        $transfer_transaction->delete();

                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('billing', '调账失败!'));
                    }
                    break;
            }
		}
		else if ($form['type'] == 'deduct') {
			if (!$me->is_allowed_to('扣费', $account)) return;

			if ($amount <= 0) {
				JS::alert(I18N::T('billing', '扣除金额需大于零'));
				return;
			}

			$confirm = I18N::T('billing','你确定要扣费吗? 请确保扣费数据正确无误! \n实验室: %lab\n扣费部门: %depart\n扣除金额: %income\n凭证号: %certificate\n扣费人员: %user\n', [
                '%user' => $user->name,
                '%lab' => $account->lab->name,
                '%income' => Number::currency($amount),
                '%depart' => $account->department->name,
                '%certificate' => $form['certificate']
            ]);
			if (!JS::confirm($confirm)) return;
			$transaction = O('billing_transaction');
			$transaction->account = $account;
			$transaction->user = $user;
			$transaction->outcome = $amount;
			$transaction->certificate = $form['certificate'];

            //手动操作增加manual flag
            $transaction->manual = TRUE;

			$transaction->description = [
                'module'=>'billing',
                'template' => I18N::T('billing', '%user 对 %account 进行扣费'),
                '%user'=>Markup::encode_Q($user),
                '%account'=>Markup::encode_Q($account->lab),
                'amend'=>H($form['description'])
            ];

            $account_old_balance = $account->balance;

            if ($transaction->save()) {

                Log::add(strtr('[billing] %user_name[%user_id]对%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务帐号[%account_id]扣费%charge, 扣费前帐号余额%old_balance, 扣费后帐号余额%balance', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%lab_name' => $account->lab->name,
                    '%lab_id' => $account->lab->id,
                    '%department_name' => $account->department->name,
                    '%department_id' => $account->department->id,
                    '%account_id' => $account->id,
                    '%charge' => sprintf('%.2f', $amount),
                    '%old_balance' => sprintf('%.2f', $account_old_balance),
                    '%balance' =>  sprintf('%.2f', $account->balance),
                ]), 'journal');

                $notif_key = 'billing.account_deduction';

				Notification::send($notif_key, $account->lab->owner, [
					'%user' => Markup::encode_Q($me),
					'%amount'=> Number::currency($amount),
					'%time'=> Date::format(Date::time(), 'Y/m/d H:i:s'),
					'%dept'=> H($account->department->name),
					'%balance'=> Number::currency($account->balance)
				]);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '扣费成功!'));
            }
		}

        if ($success) {
            //notification start
            //多财务部门
            if ($form['type'] == 'refill') {
                $notif_key = 'billing.account_credit';
            }
            elseif ($form['type'] == 'deduct'){
                $notif_key = 'billing.account_deduction';
            }

            Notification::send($notif_key, $account->lab->owner, [
                '%user' => Markup::encode_Q($user),
                '%amount'=> Number::currency($amount),
                '%time'=> Date::format(Date::time(), 'Y/m/d H:i:s'),
                '%dept'=> H($account->department->name),
                '%balance'=> Number::currency($account->balance)
            ]);

            //notification end
		}
		JS::close_dialog();
		JS::refresh();
	}

	function index_lab_department_change() {
		$form = Input::form();
		$lab = O('lab', $form['lab_id']);
		$department = O('billing_department', $form['department_id']);
		$account = O('billing_account', ['lab'=>$lab, 'department'=>$department]);
		$user_credit_lab = L('ME')->is_allowed_to('修改充值人员', $account);
		Output::$AJAX = (float)$account->balance . '|' . $user_credit_lab;
	}

	function index_credit_line_click() {

		$form = Input::form();

		$account = O('billing_account', $form['account_id']);

		if (!$account->id) return;

		if (!L('ME')->is_allowed_to('充值', $account)) return;

		JS::dialog(V('account/set_credit_line', ['account'=>$account]), ['title' => I18N::T('billing', '信用额度')]);

	}

	function index_credit_line_submit() {

		$form = Input::form();
		$me = L('ME');

		$account = O('billing_account', $form['account_id']);
		if (!$account->id) return;

		$old_line = $account->credit_line;

		if (!$me->is_allowed_to('充值', $account)) return;

        $old_credit_line = $account->credit_line;

		$account->credit_line = floatval($form['credit_line']);
		if($account->save()){
			Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '信用额度设置成功!'));
            //notification start
            $notif_key = 'billing.edit_credit_line';

            Notification::send($notif_key, $account->lab->owner, [
                '%user' =>Markup::encode_Q($me),
                '%time'=>Date::format(Date::time(), 'Y/m/d H:i:s'),
                '%old_credit_line'=> Number::currency($old_credit_line),
                '%new_credit_line'=>Number::currency($account->credit_line),
                '%dept'=>H($account->department->name)
            ]);
            //notification end
		}
		JS::refresh();
	}
}
