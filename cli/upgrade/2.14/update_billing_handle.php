#!/usr/bin/env php
<?php

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$db = Database::factory();
$db->begin_transaction();
$db->query('UPDATE billing_transaction SET `handle` = %d WHERE `income` = 0', Billing_Account_Model::HANDLE_DEDUCTION);
$db->commit();

foreach (Q('billing_transaction[income<0]') as $transaction) {
    $income = 0 - $transaction->income;
    $transaction->income = $income;
    $transaction->handle = Billing_Account_Model::HANDLE_DEDUCTION;
    $transaction->save();
}

foreach (Q('billing_transaction[outcome<0]') as $transaction) {
    $outcome = 0 - $transaction->outcome;
    $transaction->outcome = $outcome;
    $transaction->handle = Billing_Account_Model::HANDLE_CREDIT;
    $transaction->save();
}