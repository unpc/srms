#!/usr/bin/env php
<?php
    /*
     * file modify_billing.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-06-10
     *
     * useage SITE_ID=cf-lite LAB_ID=nankai_sky php modify_billing.php
     * brief
        1、添加一条负值的充值记录, 让所有的财务账号清0
        2、给所有的实验室添加财务账号
        3、给所有的实验室财务账号设定2000元的信用额度
     */

require dirname(dirname(__FILE__)). '/base.php';

//disable notification
define('DISABLE_NOTIFICATION', TRUE);

//使用support账号进行清0操作
$user = O('user', 1);

//1、财务账号清0
//遍历account
foreach(Q('billing_account') as $account) {

    //update balance
    $db = ORM_Model::db($account->name());

    $account->amount = $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d and (status = %d or source = 'local')", $account->id,                       Billing_Transaction_Model::STATUS_CONFIRMED) ?: 0;
    $outcome = $db->value('SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d', $account->id) ?: 0;
    $account->balance = $account->amount - $outcome;

    $account->save();
    

    //获取balance
    $balance = $account->balance;

    //balance不为0 进行更新
    if ($balance) {

        //增加充值transaction
        $t = O('billing_transaction');

        //基本属性设定
        $t->account = $account;
        $t->user = $user;

        //充值
        $t->income = - $balance;

        $t->description = ['module'=>'billing',
            'template' => I18N::T('billing', '%user 对 %account 进行充值'),
            '%user'=>Markup::encode_Q($user),
            '%account'=>Markup::encode_Q($account->lab),
            'amend'=> '统一对财务账号清0处理',
        ];

        $t->save();
    }
}

//2、给所有的实验室添加财务账号
foreach(Q('billing_department') as $department) {

    foreach(Q('lab') as $lab) {

        //如果无对应的财务账号, 则创建对应的财务账号
        if (!O('billing_account', ['lab'=> $lab, 'department'=> $department])->id) {

            $account = O('billing_account');
            $account->department = $department;
            $account->lab = $lab;

            //创建account
            $account ->save();
        }
    }
}

//3、给所有的财务账号设定信用额度为2000
foreach(Q('billing_account') as $account) {
    $account->credit_line = 2000;
    $account->save();
}
