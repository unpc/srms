<?php

class API_Billing_Geneegroup extends API_Common
{
    public function billing_stow($params = [])
    {
        $this->_ready();
        foreach ($params as $key => $val) {
            $this->add_transaction($key, $val['amount'], $val['user_id'], $val['certificate']);
        }
    }

    public function add_transaction($account_id, $amount, $user_id, $certificate)
    {
        $unique_billing_department = $GLOBALS['preload']['billing.single_department'];

        if ($account_id) {
            $account = O('billing_account', $account_id);
        }

        if (!$account->id) {
            return false;
        }
        $user = O('user', $user_id);

        $amount = round($amount, 2);

        $transaction = O('billing_transaction', [
            'account' => $account,
            'certificate' => $certificate
        ]);
        $transaction->account = $account;
        $transaction->user = $user;
        $transaction->income = $amount;
        $transaction->certificate = $certificate;
        $transaction->description = [
            'module'=>'billing',
            'template' => I18N::T('billing', '报销成功，%user 对 %account 进行充值'),
            '%user'=>Markup::encode_Q($user),
            '%account'=>Markup::encode_Q($account->lab)
        ];
        $account_old_balance = $account->balance;

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

            if ($GLOBALS['preload']['billing.single_department']) {
                $notif_key = 'billing.account_credit.unique';
            } else {
                $notif_key = 'billing.account_credit';
            }

            Notification::send($notif_key, $account->lab->owner, [
                '%user' => Markup::encode_Q($me),
                '%amount'=> Number::currency($amount),
                '%time'=> Date::format(Date::time(), 'Y/m/d H:i:s'),
                '%dept'=> H($account->department->name),
                '%balance'=> Number::currency($account->balance)
            ]);
        }
    }

    public function edit_charge($params = [], $status)
    {
        $this->_ready();
        foreach ($params as $v) {
            $charge = O('eq_charge', ['transaction_id' => $v]);
            if (!$charge->id) {
                continue;
            }

            switch ($status) {
                case 0:
                    $charge->bl_status = Billing_Geneegroup::STATUS_DRAFT;
                    break;
                case 1:
                    $charge->bl_status = Billing_Geneegroup::STATUS_PENDDING;
                    break;
                case 2:
                    $charge->bl_status = Billing_Geneegroup::STATUS_RECORD;
                    break;
            }
            $charge->save();
        }
    }
}
