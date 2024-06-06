<?php

class API_Lannister extends API_Common {

    function get_departments($start = 0, $step = 100) {
        $this->_ready();
		$departments = Q('billing_department')->limit($start, $step);
		$info = [];

		if (count($departments)) {
			foreach ($departments as $department) {
                $tag = $department->group;
                $group = $tag->id ? [$tag->name] : null ;
                while($tag->parent->id && $tag->parent->root->id){
                    array_unshift($group, $tag->parent->name);
                    $tag = $tag->parent;
                }

                $data = new ArrayIterator([
					'id' => $department->id,
					'source' => LAB_ID,
                    'name' => $department->name,
                    'nickname' => $department->nickname,
                    'group' => $group,
                    'group_id' => $department->group_id,
                    'mtime' => $department->mtime
                ]);
                $info[] = $data->getArrayCopy();
			}
        }
        return $info;
	}

    function get_accounts($start = 0, $step = 100, $input = []) {
        $this->_ready();

        if (isset($input['department'])) {
            $billing_department = o('billing_department', $input['department']);
            if (!$billing_department->id) return [];
            $accounts = Q("billing_account[department={$billing_department}]");
        } else {
            $accounts = Q("billing_account");
        }
        $accounts = $accounts->limit($start, $step);

        $info = [];

        if (count($accounts)) {
            foreach ($accounts as $account) {
                $data = new ArrayIterator([
                    'id' => $account->id,
                    'source' => LAB_ID,
                    "department" => $account->department_id,
                    "lab" => $account->lab_id,
                    "income_remote" => $account->income_remote,
                    "income_remote_confirmed" => $account->income_remote_confirmed,
                    "income_local" => $account->income_local,
                    "income_transfer" => $account->income_transfer,
                    "outcome_remote" => $account->outcome_remote,
                    "outcome_local" => $account->outcome_local,
                    "outcome_transfer" => $account->outcome_transfer,
                    "outcome_use" => $account->outcome_use,
                    "balance" => $account->balance,
                    "credit_line" => $account->credit_line,
                    "account_source" => $account->account_source,
                    "voucher" => $account->voucher,
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }

    function get_transactions($start = 0, $step = 100, $input = [])
    {
        $this->_ready();

        if (isset($input['account'])) {
            $billing_account = o('billing_account', $input['account']);
            if (!$billing_account->id) return [];
            $transactions = Q("$billing_account<account billing_transaction");
        } else {
            $transactions = Q("billing_transaction");
        }
        $transactions = $transactions->limit($start, $step);

        $info = [];

        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                if ($transaction->description['module'] == 'billing') {
                    $description_value = new Markup(I18N::T('billing', $transaction->description['template'], [
                        '%user'=>$transaction->description['%user'],
                        '%account'=>$transaction->description['%account'],
                        '%from_account'=> $transaction->description['%from_account'],
                    ]), FALSE);
                }
                if ($transaction->description['module'] == 'eq_charge') {
                    $description_value = new Markup(I18N::T('eq_charge', $transaction->description['template'], [
                        '%user'=>$transaction->description['%user'],
                        '%equipment'=>$transaction->description['%equipment'],
                    ]),FALSE);
                }
                $data = new ArrayIterator([
                    'id' => $transaction->id,
                    'source' => LAB_ID,
                    "account" => $transaction->account_id,
                    "user" => $transaction->user_id,
                    "reference" => $transaction->reference_id,
                    "status" => $transaction->status,
                    "income" => $transaction->income,
                    "outcome" => $transaction->outcome,
                    "ctime" => $transaction->ctime,
                    "mtime" => $transaction->mtime,
                    "certificate" => $transaction->certificate,
                    "voucher" => $transaction->voucher?:0,
                    "manual" => $transaction->manual,
                    "transfer" => $transaction->transfer,
                    'description' => $transaction->description,
                    "description_value" => (string)$description_value,
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;

    }

}
