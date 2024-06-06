<?php

class Transaction_Controller extends Base_Controller {

}

class Transaction_AJAX_Controller extends AJAX_Controller {

	function index_edit_transaction_click() {
		$form = Form::filter(Input::form());
		$transaction = O('billing_transaction', $form['transaction_id']);
		if (!$transaction->id) return;
		if (!L('ME')->is_allowed_to('修改', $transaction)) return;

		if ($transaction->status == Billing_Transaction_Model::STATUS_PENDING) {
			$view = V('billing:transaction/edit', ['transaction'=>$transaction]);
		}
		else {
			$view = V('billing:transaction/refund', ['transaction'=>$transaction]);
		}

		JS::dialog($view, ['title' => I18N::T('billing', '修改充值/扣费记录')]);
	}

	function index_edit_transaction_submit() {
		$form = Form::filter(Input::form());
		$form->validate('id', 'is_numeric', I18N::T('billing', '请求出错！'));

		$transaction = O('billing_transaction', $form['id']);

        if ($transaction->income) {
            if (!is_numeric($form['income']) || $form['income'] <= 0) {
                $form->set_error('income', I18N::T('billing', '收入填写有误!'));
            }
        }

        if ($transaction->outcome) {
            if (!is_numeric($form['outcome']) || $form['outcome'] <= 0) {
                $form->set_error('outcome', I18N::T('billing', '支出填写有误!'));
            }
        }

        if (isset($form['transaction_date'])) {
            if (!$form['transaction_date']) {
                $form->set_error('code', I18N::T('billing', '请选择财务转账日期!'));
            }
        }

        if (isset($form['code'])) {
            if (!$form['code']) {
                $form->set_error('code', I18N::T('billing', '请填写转出项目代码!'));
            }
        }

		if ($form['certificate'] && (mb_strlen($form['certificate']) > (Config::get('billing.default_certificate_length', 32)))) {
			$form->set_error('certificate',
				 I18N::T('billing', '凭证单号字数不得超过%num字', [
				 	'%num' => Config::get('billing.default_certificate_length', 32)
				 ]));
		}

		if (!L('ME')->is_allowed_to('修改', $transaction)) return;

		if($form->no_error && $transaction->id && $transaction->status == Billing_Transaction_Model::STATUS_PENDING) {

			$transaction_old_outcome = $transaction->outcome;
			$transaction_old_income = $transaction->income;
			$form['description'] = $form['description'];
			$description = trim($form['description']);
			
			/*
				不属于其他transation的明细，因为存在系统默认的description值，所以需要进行匹配来显示。
				而其他附属的transaction备注都属于自己填写，所以不需要进行判断，该怎么显示还是怎么显示的，后台处理也一样。
				TODO 美中不足的是该处的正则匹配语句没有那么完美，望有能力者补充！
			*/
			if (!$transaction->reference->id) {
				//preg_match('/([^-\<]*)(\<br\>-----\<br\>)?(.*)/', $transaction->description, $match);
				//if ($match[1]) {
					/*if (!$match[2]) {
						$description = $transaction->description . "<br>-----<br>" . $form['description'];
					}
					else {
						$description = $form['description'] ? $match[1].$match[2].$form['description'] : $match[1];
						$transaction->description = $description;
					}*/
					//	$description = $match[1] . "<br>-----<br>" . $description;				
					//} else {
					//	$description = $match[1];
                    $transaction_description = $transaction->description;
                    $transaction_description['amend'] = $description;
                    $transaction->description = $transaction_description;
				//}
			}

			
			if($form['income'] >= 0){
				$transaction->income = round($form['income'], 2);
			}
			if($form['outcome'] >= 0 ){
				$transaction->outcome = round($form['outcome'], 2);
			}
            if (isset($form['code']) && $form['code']) {
				$transaction->code = trim($form['code']);
            }
            if (isset($form['project_type'])) {
				$transaction->project_type = $form['project_type'];
            }

            if (isset($form['transaction_date'])) {
                $transaction->transaction_date = $form['transaction_date'];
            }

			$transaction->certificate = $form['certificate'];

			if ($transaction->save()) {
				/* 记录日志 */
				Log::add(strtr('[billing] %user_name[%user_id]修改了%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务明细[%transaction_id], 原支出为%old_outcome, 修改为%outcome, 原收入为%old_income, 修改为%income', [
							'%user_name' => L('ME')->name,
							'%user_id' => L('ME')->id,
							'%lab_name' => $transaction->account->lab->name,
							'%lab_id' => $transaction->account->lab->id,
							'%department_name' => $transaction->account->department->name,
							'%department_id' => $transaction->account->department->id,
							'%transaction_id' => $transaction->id,
							'%old_outcome' => sprintf('%.2f', $transaction_old_outcome),
							'%outcome' => sprintf('%.2f',  $transaction->outcome),
							'%old_income' => sprintf('%.2f',  $transaction_old_income),
							'%income' => sprintf('%.2f', $transaction->income),
				]), 'journal');
				
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('billing', '财务明细修改成功!'));

                //发生财务变化，发送Notification
                if (($transaction_old_income != $transaction->income) || ($transaction_old_outcome != $transaction->outcome)) {
                    $notif_key = 'billing.edit_transaction';

                    Notification::send($notif_key, $transaction->account->lab->owner, [
                        '%user' => Markup::encode_Q(L('ME')),
                        '%time'=>Date::format(Date::time(), 'Y/m/d H:i:s'),
                        '%id'=> Number::fill($transaction->id),
                        '%old_income'=>Number::currency($transaction_old_income),
                        '%old_outcome'=>Number::currency($transaction_old_outcome),
                        '%new_income'=>Number::currency($transaction->income),
                        '%new_outcome'=>Number::currency($transaction->outcome),
                        '%balance'=>Number::currency($transaction->account->balance),
                        '%dept'=>H($transaction->account->department->name)
                    ]);
                }

                //如果是转账修正, 需要把对应的转账明细也修改了
                if ($transaction->transfer) {
                    $id = $transaction->id;
                    $start = $id - 10;
                    $end = $id + 10;
                    //如果为充值
                    if ($transaction->income) {
                        $outcome = $transaction_old_income;
                        //粗判断获取transfer对应的billing_transaction
                        //找对应的调账扣费
                        $ot = Q("billing_transaction[transfer=1][outcome={$outcome}][id={$start}~{$end}]")->current();

                        //找到对应的后, 进行同步
                        if ($ot->id) {
                            //
                            $ot->outcome = $transaction->income;
                            $ot->save();

                            //Log
                            Log::add(strtr('[billing] %user_name[%user_id]修改了%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务明细[%transaction_id], 原支出为%old_outcome, 修改为%outcome, 原收入为%old_income, 修改为%income 该次修正为系统转账自动同步修正', [
                                '%user_name' => L('ME')->name,
                                '%user_id' => L('ME')->id,
                                '%lab_name' => $ot->account->lab->name,
                                '%lab_id' => $ot->account->lab->id,
                                '%department_name' => $ot->account->department->name,
                                '%department_id' => $ot->account->department->id,
                                '%transaction_id' => $ot->id,
                                '%old_outcome' => sprintf('%.2f', $outcome),
                                '%outcome' => sprintf('%.2f',  $transaction->outcome),
                                '%old_income' => sprintf('%.2f',  0),
                                '%income' => sprintf('%.2f', $transaction->income),
                            ]), 'journal');
                        }
                    }
                    //如果为扣费
                    else {

                        $income = $transaction_old_outcome;
                        //粗判断获取transfer对应的billing_transaction
                        //找对应的充值调账
                        $ot = Q("billing_transaction[transfer=1][income={$income}][id={$start}~{$end}]")->current();

                        if ($ot->id) {
                            $ot->income = $transaction->outcome;
                            $ot->save();

                            //Log
                            Log::add(strtr('[billing] %user_name[%user_id]修改了%lab_name[%lab_id]在财务部门%department_name[%department_id]的财务明细[%transaction_id], 原支出为%old_outcome, 修改为%outcome, 原收入为%old_income, 修改为%income 该次修正为系统转账自动同步修正', [
                                '%user_name' => L('ME')->name,
                                '%user_id' => L('ME')->id,
                                '%lab_name' => $ot->account->lab->name,
                                '%lab_id' => $ot->account->lab->id,
                                '%department_name' => $ot->account->department->name,
                                '%department_id' => $ot->account->department->id,
                                '%transaction_id' => $ot->id,
                                '%old_outcome' => sprintf('%.2f', 0),
                                '%outcome' => sprintf('%.2f',  $transaction->outcome),
                                '%old_income' => sprintf('%.2f',  $income),
                                '%income' => sprintf('%.2f', $transaction->income),
                            ]), 'journal');
                        }
                    }
                }
			}

			JS::refresh();
		}
		else{
			JS::dialog(V('billing:transaction/edit', ['transaction'=>$transaction, 'form'=>$form]));
		}

	}

	function index_refund_transaction_submit() {
		$form = Form::filter(Input::form())
					->validate('op_type', 'not_empty', I18N::T('billing', '请选择“返还”或者“补交”'))
					->validate('money', 'number(>0)', I18N::T('billing', '操作金额不能为 0'))
					->validate('description', 'not_empty', I18N::T('billing', '请填写描述信息'));

		$transaction = O('billing_transaction', $form['id']);
		if (!L('ME')->is_allowed_to('修改', $transaction)) return;
		if($form->no_error){
			if(
				$transaction->id
				&&
				$transaction->status==Billing_Transaction_Model::STATUS_CONFIRMED
			){
				$new_transaction = O('billing_transaction');
				$new_transaction->account = $transaction->account;
				$new_transaction->reference = $transaction;
				$new_transaction->description = $form['description'];
				if ($form['op_type']=='refund'){
					$new_transaction->income = round($form['money'], 2);
				}
				elseif ($form['op_type']=='arrears'){
					$new_transaction->outcome = round($form['money'], 2);
				}

				$new_transaction->save();
			}
			JS::refresh();
		}
		else{
			JS::dialog(V('billing:transaction/refund', ['transaction'=>$transaction, 'form'=>$form]));
		}
	}
}
