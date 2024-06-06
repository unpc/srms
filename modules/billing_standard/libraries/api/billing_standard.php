<?php

class API_Billing_Standard extends API_Common
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
            'template' => I18N::T('billing', '报销成功，系统 对 %account 进行充值'),
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

    public function edit_charge($params = [], $updateData)
    {
        $status2Billing = [
            Billing_Standard::STATUS_DRAFT => 0,//未报销
            Billing_Standard::STATUS_PENDDING => 1,//报销中
            Billing_Standard::STATUS_RECORD => 2,//已报销
            Billing_Standard::STATUS_CONFIRM => 3,//待确认
        ];
        $this->_ready();
        foreach ($params as $v) {
            if (Module::is_installed('billing')) {
                $selector = "eq_charge[transaction_id=$v]";
            } else {
                $selector = "eq_charge[id={$v}]";
            }
            $charge = Q($selector)->current();
            if (!$charge->id) {
                continue;
            }
            if(isset($updateData['status'])){
                $charge->bl_status = array_search($updateData['status'],$status2Billing);
            }

            if(isset($updateData['serial_number'])){
                $charge->serialnum = $updateData['serial_number'];
            }
            if(isset($updateData['evidence'])){
                $charge->vouchernum = $updateData['evidence'];
            }
            if(isset($updateData['complete_time'])){
                $charge->completetime = strtotime($updateData['complete_time']);
            }
            if(isset($updateData['fund_card_no'])){
                $charge->source->fund_card_no = $updateData['fund_card_no'];
                $charge->source->save();
            }
            $charge->save();
        }
    }
}
