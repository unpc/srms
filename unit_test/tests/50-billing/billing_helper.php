<?php

//Billing辅助函数
class Billing_Helper {
    
    //获取account的amount（收入）
    static function get_amount($account) {
        if ($account instanceof Billing_Account_Model) {
            return $account->income_local + $account->income_remote_confirmed + $account->income_transfer;
        }
        else {
            return NULL;    
        }
    }

    //获取account的balance（余额）
    static function get_balance($account) {
        if ($account instanceof Billing_Account_Model) {
            return $account->balance; 
        } 
        else {
            return NULL;
        }
    }

    //获取account的credit_line（信用额度）
    static function get_credit_line($account) {
        if ($account instanceof Billing_Account_Model) {
            return $account->credit_line; 
        } 
        else {
            return NULL;
        }
    }

    //获取account的总支出
    static function get_outcome($account) {
        if ($account instanceof Billing_Account_Model) {
            return self::get_amount($account) - self::get_balance($account); 
        } 
        else {
            return NULL;
        }
    }

    //获取财务账号中财务明细所有的收入，包括未确认（总收入）
    static function get_total_income($account) {
        if ($account instanceof Billing_Account_Model) {
            return Q("billing_transaction[account=$account]")->sum('income');
        }
        else {
            return NULL;    
        }
    }
}
