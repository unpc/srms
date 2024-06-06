<?php

class Billing_Check {

    const ERROR_IN_CSV = 1;
    const ERROR_IN_DB = 2;
    
    static function setup($e) {
        Event::bind('billing_check.check_by_certificate', 'Billing_Check::billing_check_not_in_db', 10);
        Event::bind('billing_check.check_by_certificate', 'Billing_Check::billing_check_multi_in_db', 20);
        Event::bind('billing_check.check_by_certificate', 'Billing_Check::billing_check_income_diff', 30);

        /*
          TODO
          - DB中凭证号在CSV不存在的数据
          (xiaopei.li@2011.11.07)
        */
        Event::bind('billing_check.other_certificate_error', 'Billing_Check::billing_check_no_certificate');
    }
    
    // 检查不在DB中的CSV记录
    static function billing_check_not_in_db($e, $certificate, $income, $dtstart, $dtend, $department) {
        $query = "billing_account[department={$department}]<account billing_transaction[certificate={$certificate}]";

        if ($dtstart) {
            $query .= "[ctime>{$dtstart}]";
        }

        if ($dtend) {
            $query .= "[ctime<{$dtend}]";
        }
        
        $transactions = Q($query);

        if (!$transactions->total_count()) {
            $error = ['type' => self::ERROR_IN_CSV, 'certificate' => $certificate, 'income' => $income, 'msg' => I18N::T('billing', "无凭证号为 %certificate 的充值", ['%certificate' => $certificate])];
            $e->return_value = $error;
            return FALSE;
        }

        return TRUE;
    }

    // DB中凭证号在CSV中存在, 但金额不符的数据
    static function billing_check_multi_in_db($e, $certificate, $income, $dtstart, $dtend, $department) {
        $query = "billing_account[department={$department}]<account " .
            "billing_transaction[certificate={$certificate}]";

        if ($dtstart) {
            $query .= "[ctime>{$dtstart}]";
        }

        if ($dtend) {
            $query .= "[ctime<{$dtend}]";
        }
        
        $transactions = Q($query);

        if ($transactions->total_count() > 1) {
            $error = ['type' => self::ERROR_IN_DB, 'warning_transactions' => $transactions, 'msg' => I18N::T('billing', "有多条凭证号为 %certificate 的充值", ['%certificate' => $certificate])];
            $e->return_value = $error;
            return FALSE;
        }

        return TRUE;
    }

    // DB中凭证号在CSV中存在, 但金额不符的数据
    static function billing_check_income_diff($e, $certificate, $income, $dtstart, $dtend, $department) {
        $query = "billing_account[department={$department}]<account " .
            "billing_transaction[certificate={$certificate}]:limit(1)";

        if ($dtstart) {
            $query .= "[ctime>{$dtstart}]";
        }

        if ($dtend) {
            $query .= "[ctime<{$dtend}]";
        }

        $transactions = Q($query);
        
        if ($transactions->current()->income != $income) {
            $error = ['type' => self::ERROR_IN_DB, 'warning_transactions' => $transactions, 'msg' => I18N::T('billing', "充值金额不符")];
            $e->return_value = $error;
            return FALSE;
        }

        return TRUE;
    }

    // DB中无凭证号的数据
    static function billing_check_no_certificate($e, $dtstart, $dtend, $department) {
        $query = "billing_account[department={$department}]<account " .
            "billing_transaction[certificate=\"\"]";

        if ($dtstart) {
            $query .= "[ctime>{$dtstart}]";
        }

        if ($dtend) {
            $query .= "[ctime<{$dtend}]";
        }
        
        $query = 'billing_account[department=billing_department#5]<account billing_transaction[certificate=""]';
        
        $transactions = Q($query);

        $errors = [];

        foreach ($transactions as $transaction) {
            $errors[] = ['type' => self::ERROR_IN_DB, 'warning_transactions' => [$transaction], 'msg' => I18N::T('billing', "无凭证号的充值")];
        }

        $e->return_value = $errors;
    }
    
    static function cannot_access_equipment($e, $equipment, $params) {

        $me = $params[0];
        $now = $params[1];

        if ($equipment->charge_script['record']) {

            $department =$equipment->billing_dept;
            if (!$department->id) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '该仪器未指定财务部门, 不可使用该仪器'));
                $e->return_value = TRUE;
                return FALSE;
            }

            $accounts = Q("$me lab billing_account[department={$department}]");
            if (!$accounts->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '您实验室在该设备指定的财务部门无帐号'));
                $e->return_value = TRUE;
                return FALSE;
            }

            if ($accounts->total_count() == 1 && $account = $accounts->current()) {
                if(($account->balance + $account->credit_line) < ($equipment->record_limit ? $equipment->record_balance_required : 0)  && !Config::get('billing.ignore_lab_balance_limit')){
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '实验室余额不足, 您目前无法使用该设备.'));
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }
    }

    static function cannot_reserv_equipment ($e, $equipment, $params) {
        $me = L('ME');
        if ($equipment->charge_script['reserv']) {
            $department = $equipment->billing_dept;
            if (!$department->id) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '该设备未指定财务部门, 您目前无法预约该设备。'));
                $e->return_value = TRUE;
                return FALSE;
            }
            $accounts = Q("$me lab billing_account[department={$department}]");
            if (!$accounts->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '实验室在该设备指定财务部门内无帐号, 您目前无法预约该设备。'));
                $e->return_value = TRUE;
                return FALSE;
            }

            if ($accounts->total_count() == 1 && $account = $accounts->current()) {
                if (($account->balance + $account->credit_line) < ($equipment->reserv_limit ? $equipment->reserv_balance_required : 0) && !Config::get('billing.ignore_lab_balance_limit')) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '实验室余额不足, 您目前无法预约该设备。'));
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }
    }

    static function cannot_sample_equipment ($e, $equipment, $params) {
        $user = $params[0];
        $sample = $params[1];

        $me = L('ME');
        if ($equipment->charge_template['sample']) {
            // 获取送样申请人本实验室在仪器收费中心的账户
            $department = $equipment->billing_dept;
            $department = Billing_Department::get($department->id);
            $lab = $sample->lab;
            if (!$lab->id && $GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
            }

            //该仪器无财务部门
            if (!$department->id) {
                Lab::message('sample',I18N::T('eq_sample', '该仪器暂无财务部门管理, 您目前无法申请送样!'));
                $e->return_value = TRUE;
                return FALSE;
            }

            $accounts = Q("$me lab billing_account[department={$department}]");
            if (!$accounts->total_count()) {
                Lab::message('sample',I18N::T('eq_sample', '您的实验室在该仪器所属的财务部门还没有账户, 目前无法申请送样!'));
                $e->return_value = TRUE;
                return FALSE;
            }

            if ($accounts->total_count() == 1 && $account = $accounts->current()) {
                $balance = round(floatval($account->balance + $account->credit_line),2);
                $require = round((int)$equipment->sample_balance_required,2);

                if (intval($balance * 100) < intval($require*100)) {
                    Lab::message('sample',I18N::T('eq_sample', '您的实验室在该仪器所属的财务部门余额不足, 目前无法申请送样!'));
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }
    }
}
