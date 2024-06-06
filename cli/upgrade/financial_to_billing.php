#!/usr/bin/env php
<?php
  /**
   * @file   financial_to_billing.php
   * @author Rui Ma <rui.ma@geneegroup.com>
   * @date   2012.07.06
   *
   * @brief  系统中financial修改为billing后，相关数据库table更新脚本 
   *
   * @usage: SITE_ID=cf LAB_ID=test php ./financial_to_billing.php
   *
   */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader();

//备份数据
$u->backup = function() {

    $dbfile = LAB_PATH.'private/backup/before_financial_to_billing.sql';
    File::check_path($dbfile);

    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库表");

    $db = Database::factory();
    return $db->snapshot($dbfile);
};

// 检查是否升级
$u->check = function() {
    $db = Database::factory();
    $fin_department_existed =  $db->value("SHOW TABLES LIKE 'fin_department'");
    $fin_account_existed = $db->value("SHOW TABLES LIKE 'fin_account'");
    $fin_transaction_existed = $db->value("SHOW TABLES LIKE 'fin_transaction'");
    $_p_fin_department_existed = $db->value("SHOW TABLES LIKE '_p_fin_department'");
    $_p_fin_transaction_existed = $db->value("SHOW TABLES LIKE '_p_fin_transaction'");
    $_r_user_fin_department_existed = $db->value("SHOW TABLES LIKE '_r_user_fin_department'");
    $_r_tag_fin_department_existed = $db->value("SHOW TABLES LIKE '_r_tag_fin_department'");

    if ($fin_department_existed || $fin_account_existed || $fin_transaction_existed || $_p_fin_department_existed || $_p_fin_transaction_existed || $_r_user_fin_department_existed || $_r_tag_fin_department_existed) return TRUE;
    return FALSE;

};

//升级
$u->upgrade = function() {

    $db = Database::factory();
    $financial_to_billing_tables = [
        'fin_department'=>'billing_department',
        'fin_account'=>'billing_account',
        'fin_transaction'=>'billing_transaction',
        '_p_fin_department'=>'_p_billing_department',
        '_p_fin_transaction'=>'_p_billing_transaction',
        '_r_user_fin_department'=>'_r_user_billing_department',
        '_r_tag_fin_department'=>'_r_tag_billing_department'
    ];

    foreach($financial_to_billing_tables as $financial => $billing) {

        if ($db->value("SHOW TABLES LIKE '%s'", $financial)) {

            $rename_sql = "RENAME TABLE %s TO %s";

            if ($db->query($rename_sql, $financial, $billing)) {
                Upgrader::echo_success(strtr('TABLE从 %financial 修改为 %billing 成功！', ['%financial'=>$financial, '%billing'=>$billing]));
            }
            else {
                Upgrader::echo_fail(strtr('TABLE从 %financial 修改为 %billing 失败！', ['%financial'=>$financial, '%billing'=>$billing]));
            }
        }
        else {
            Upgrader::echo_success(strtr('TABLE %financial 不存在，无需进行修改!', ['%financial'=>$financial]));     
        }
    }

    $alter_equipment_column_sql = "ALTER TABLE  `equipment` CHANGE  `fin_dept_id`  `billing_dept_id` BIGINT( 20 ) NOT NULL DEFAULT  '0'";

    if ($db->query($alter_equipment_column_sql)) {
        Upgrader::echo_success('equipment TABLE 中fin_dept_id 修改为billing_dept_id 成功！');
    }
    else {
        Upgrader::echo_fail('equipment TABLE 中fin_dept_id 修改为billing_dept_id 失败！');
    }

    $alter_equipment_pri_key_sql = 'ALTER TABLE  `equipment` DROP INDEX  `fin_dept` , ADD INDEX  `billing_dept` ( `billing_dept_id` )';

    if ($db->query($alter_equipment_pri_key_sql)) {
        Upgrader::echo_success('equipment TABLE中主键修改成功！');
    }
    else {
        Upgrader::echo_fail('equipment TABLE中主键修改失败！');
    }

    Lab::set('notificaiton.billing.refill', Lab::get('notificaiton.financial.refill'));
};

//升级检测
$u->verify = function() {

    $db = Database::factory();
    $fin_department_existed =  $db->value("SHOW TABLES LIKE 'fin_department'");
    $fin_account_existed = $db->value("SHOW TABLES LIKE 'fin_account'");
    $fin_transaction_existed = $db->value("SHOW TABLES LIKE 'fin_transaction'");
    $_p_fin_department_existed = $db->value("SHOW TABLES LIKE '_p_fin_department'");
    $_p_fin_transaction_existed = $db->value("SHOW TABLES LIKE '_p_fin_transaction'");
    $_r_user_fin_department_existed = $db->value("SHOW TABLES LIKE '_r_user_fin_department'");
    $_r_tag_fin_department_existed = $db->value("SHOW TABLES LIKE '_r_tag_fin_department'");

    if ($fin_department_existed || $fin_account_existed || $fin_transaction_existed || $_p_fin_department_existed || $_p_fin_transaction_existed || $_r_user_fin_department_existed
    || $_r_tag_fin_department_existed) return FALSE;

    return TRUE;
};

//恢复数据
$u->restore = function() {

    $dbfile = LAB_PATH.'private/backup/before_financial_to_billing.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库表");
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();
