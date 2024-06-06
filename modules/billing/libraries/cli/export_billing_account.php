<?php

class CLI_Export_Billing_Account {

    static function export() {

        $params = func_get_args();
        $accounts_selector = $params[0];
        $valid_columns = json_decode($params[3], true);
        $visible_columns = json_decode($params[4], true);
        $object_name = $params[2];
        $form = json_decode($params[5], true);
        $accounts = Q($accounts_selector);

        $start = 0;
        $per_page = 100;

        $excel = new Excel($params[1]);

		if ($accounts->total_count()) {
			$ids = join(', ', array_keys($accounts->to_assoc('id', 'id')));

            $has_remote_billing = count(Config::get('billing.sources'));
            $statics = Event::trigger('billing.account.statics.row', $accounts, $has_remote_billing, $form);
            if (isset($statics['tol_amount']) && isset($statics['tol_amount_confirmed']) && isset($statics['tol_use']) && isset($statics['tol_balance'])) {
                $tol_amount = (double)$statics['tol_amount'];
                $tol_amount_confirmed = (double)$statics['tol_amount_confirmed'];
                $tol_use = (double)$statics['tol_use'];
                $tol_balance = (double)$statics['tol_balance'];
                $tol_credit_line = $accounts->sum('credit_line');
            } else {
		 	$tol_balance = $accounts->sum('balance');
		 	$tol_use = $accounts->sum('outcome_use');
		 	$tol_credit_line = $accounts->sum('credit_line');


            //如果有远程billng
            if ($has_remote_billing) {
                //总收入
                $tol_amount =
                    $accounts->sum('income_remote') //远程充值
                    -
                    $accounts->sum('outcome_remote') //远程扣费
                    +
                    $accounts->sum('income_local') //本地充值
                    -
                    $accounts->sum('outcome_local'); //本地扣费

                $tol_amount_confirmed =
                    $accounts->sum('income_remote_confirmed') //远程充值confirmed
                    -
                    $accounts->sum('outcome_remote') //远程扣费
                    +
                    $accounts->sum('income_local') //本地充值
                    -
                    $accounts->sum('outcome_local'); //本地扣费
            }
            else {
                //总收入
                //本地收入 - 本地扣费
                $tol_amount = $accounts->sum('income_local') - $accounts->sum('outcome_local');
            }
            }
		}

		if ($object_name == 'billing_department') {

            if ($has_remote_billing) {
                $statis = [
                    I18N::T('billing', '当前所有实验室的总收入为%tol_amount; 有效收入为%tol_amount_confirmed; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_amount_confirmed'=> Number::currency($tol_amount_confirmed),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
            else {
                $statis = [
                    I18N::T('billing', '当前所有实验室的总收入为%tol_amount; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
		}
		elseif ($object_name == 'lab') {
            if ($has_remote_billing) {
                $statis = [
                    I18N::T('billing', '当前总收入为%tol_amount; 总有效收入%tol_amount_confirmed; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_amount_confirmed'=> Number::currency($tol_amount_confirmed),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
            else {
                $statis = [
                    I18N::T('billing', '当前总收入为%tol_amount; 总费用%tol_use; 总余额%tol_balance; 总信用额度%tol_credit_line。',[
                        '%tol_amount' => Number::currency($tol_amount),
                        '%tol_use' => Number::currency($tol_use),
                        '%tol_balance' => Number::currency($tol_balance),
                        '%tol_credit_line' => Number::currency($tol_credit_line)
                ])];
            }
		}

        $excel->write($statis);
        $valid_columns_key = array_search('实验室', $valid_columns);
        if ($valid_columns_key) {
            $valid_columns[$valid_columns_key] = '课题组';
        }
        foreach ($valid_columns as $p => $p_name ) {
            if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        $excel->write(array_values($valid_columns));

		if ($accounts->total_count()) {
			foreach ($accounts as $account) {
                Event::trigger('billing.account.table_list.row', $account, $has_remote_billing, $form);
				$data = [];

				if(array_key_exists('billing_department', $valid_columns)){
					$data[] = $account->department->name?:'-';
				}

				if(array_key_exists('lab', $valid_columns)){
					$data[] = $account->lab->name?:'-';

				}
                if(array_key_exists('income_remote', $valid_columns)) {
                    $data[] = $account->income_remote?:'-';
                }
                if(array_key_exists('income_remote_confirmed', $valid_columns)) {
                    $data[] = $account->income_remote_confirmed ?:'-';
                }
                if(array_key_exists('income_local', $valid_columns)) {
                    $data[] = $account->income_local?:'-';
                }
                if(array_key_exists('income_transfer', $valid_columns)) {
                    $data[] = $account->income_transfer?:'-';
                }
                if(array_key_exists('outcome_remote', $valid_columns)) {
                    $data[] = $account->outcome_remote?:'-';
                }
                if(array_key_exists('outcome_local', $valid_columns)) {
                    $data[] = $account->outcome_local?:'-';
                }

                if(array_key_exists('outcome_use', $valid_columns)) {
                    $data[] = $account->outcome_use?:'-';
                }

                if(array_key_exists('outcome_transfer', $valid_columns)) {
                    $data[] = $account->outcome_transfer?:'-';
                }

				if(array_key_exists('balance', $valid_columns)){
					$data[] =  $account->balance ? : '-';
				}

				if(array_key_exists('credit_line', $valid_columns)){
					$data[] = $account->credit_line ? : '-';
                }
                
                $new_data =  Event::trigger('billing.export_columns.extra.billings.data',  $valid_columns, $account, $data);
                if ($new_data) $data = $new_data;
                
                $excel->write($data);
			}

		}

        $excel->save();
    }
}
