<?php
/*
 * @file list.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器统计模块测试用例环境架设脚本 
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_stat/list
 */
if (!Module::is_installed('eq_stat')) return true;
define('DISABLE_NOTIFICATION', TRUE);

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:eq_stat_list\n\n";

$GLOBALS['preload']['billing.single_department'] = TRUE;

//测试环境初始化
Environment::init_site();

//获取超级管理员
$root_user = O('user', ['name'=>'技术支持']);

//创建系统所需用户
$user1 = Environment::add_user('刘成');
$user2 = Environment::add_user('马睿');
$user3 = Environment::add_user('吴凯');
$user4 = Environment::add_user('吴天放');

//创建新实验室，并设定系统用户的实验室为新创建实验室
$lab = Environment::add_lab('仪器统计测试实验室', $root_user);
Environment::set_lab($user1, $lab);
Environment::set_lab($user2, $lab);
Environment::set_lab($user3, $lab);
Environment::set_lab($user4, $lab);

//创建实验室项目
$project = Environment::add_lab_project($lab, '论文关联使用项目');

//获取财务部门，并为新增加实验室创建财务部门
$department = Billing_department::get();
$account = Environment::add_account($lab, $department);

//为财务帐号充值
Environment::add_transaction($account, $root_user, 20000);

//添加仪器
$equipment1 = Environment::add_equipment('仪器A', $user1, $user1);
$equipment2 = Environment::add_equipment('仪器B', $user1, $user1);
$equipment3 = Environment::add_equipment('仪器C', $user1, $user1);

//设定仪器计费方式和单位时间使用金额
$equipment1->charge_mode = EQ_Charge::CHARGE_MODE_DURATION;
$equipment1->unit_price = 100;
$equipment1->save();

$equipment2->charge_mode = EQ_Charge::CHARGE_MODE_DURATION;
$equipment2->unit_price = 200;
$equipment2->save();

$equipment3->charge_mode = EQ_Charge::CHARGE_MODE_DURATION;
$equipment3->unit_price = 300;
$equipment3->save();

//为仪器创建用户使用记录
$re1 = Environment::add_eq_record_by_dtstart_and_dtend($equipment1, $user1, 1332229896, 1332237095);
$re2 = Environment::add_eq_record_by_dtstart_and_dtend($equipment1, $user1, 1334908506, 1334926505, 6);

$re3 = Environment::add_eq_record_by_dtstart_and_dtend($equipment1, $user2, 1331798325, 1331809124, 20);
$charge1 = O('eq_charge', ['equipment'=>$equipment1, 'user'=>$user2, 'samples'=>20]);
$charge1->dtstart = $re3->dtstart;
$charge1->save();

$re4 = Environment::add_eq_record_by_dtstart_and_dtend($equipment1, $user2, 1334390400, 1334397599);
$charge2 = O('eq_charge', ['equipment'=>$equipment1, 'user'=>$user2, 'samples'=>1]);
$charge2->dtstart = $re4->dtstart;
$charge2->save();

$re5 = Environment::add_eq_record_by_dtstart_and_dtend($equipment2, $user1, 1332230548, 1332237747);
$re6 = Environment::add_eq_record_by_dtstart_and_dtend($equipment2, $user1, 1334887389, 1334894588, 2);

$re7 = Environment::add_eq_record_by_dtstart_and_dtend($equipment2, $user2, 1331525074, 1331543073, 10);
$charge3 = O('eq_charge', ['equipment'=>$equipment2, 'user'=>$user2, 'samples'=>10]);
$charge3->dtstart = $re7->dtstart;
$charge3->save();

$re8 = Environment::add_eq_record_by_dtstart_and_dtend($equipment2, $user2, 1335337539, 1335351938, 5);
$charge4 = O('eq_charge', ['equipment'=>$equipment2, 'user'=>$user2, 'samples'=>5]);
$charge4->dtstart = $re8->dtstart;
$charge4->save();

$re9 = Environment::add_eq_record_by_dtstart_and_dtend($equipment3, $user1, 1332230891, 1332241690, 3);
$re10 = Environment::add_eq_record_by_dtstart_and_dtend($equipment3, $user2, 1334909334, 1334920133, 2);
$charge5 = O('eq_charge', ['equipment'=>$equipment3, 'user'=>$user2, 'samples'=>2]);
$charge5->dtstart = $re10->dtstart;
$charge5->save();

//反馈使用记录并关联实验室项目
foreach(Q('eq_record') as $e) {
    $e->status = 1;
    $e->project = $project;
    $e->save();
}

//创建论文，并关联项目、仪器
$pub1 = O('publication');
$pub1->title = '论文1';
$pub1->lab= $lab;
$pub1->date = 1333013245;
$pub1->save();
$pub1->connect($equipment1);
$pub1->connect($equipment2);
$pub1->connect($project);

$pub2 = O('publication');
$pub2->title = '论文2';
$pub2->lab = $lab;
$pub2->date = 1335213245;
$pub2->save();
$pub2->connect($equipment2);
$pub2->connect($project);

$pub3 = O('publication');
$pub3->title = '论文3';
$pub3->lab = $lab;
$pub3->date = 1333013245;
$pub3->save();
$pub3->connect($equipment1);
$pub3->connect($equipment2);
$pub3->connect($equipment3);
$pub3->connect($project);

//设定仪器需要培训授权
foreach(Q('equipment') as $e) {
    $e->require_training = TRUE;    
    $e->save();
}

$ue1 = O('ue_training');
$ue1->user = $user3;
$ue1->equipment = $equipment1;
$ue1->atime = 0;
$ue1->ctime = 1333013245;
$ue1->mtime = 1333013245;
$ue1->status = UE_Training_Model::STATUS_APPROVED;
$ue1->save();

$ue2 = O('ue_training');
$ue2->user = $user4;
$ue2->equipment = $equipment2;
$ue2->atime = 0;
$ue2->ctime = 1335213245;
$ue2->mtime = 1335213245;
$ue2->status = UE_Training_Model::STATUS_APPROVED;
$ue2->save();

$ue3 = O('ue_training');
$ue3->user = $user3;
$ue3->equipment = $equipment3;
$ue3->atime = 0;
$ue3->ctime = 1333013245;
$ue3->mtime = 1333013245;
$ue3->status =  UE_Training_Model::STATUS_APPROVED;
$ue3->save();

$ue4 = O('ue_training');
$ue4->user = $user4;
$ue4->equipment = $equipment3;
$ue4->atime = 0;
$ue4->ctime = 1333013245; 
$ue4->mtime = 1333013245; 
$ue4->status = UE_Training_Model::STATUS_APPROVED;
$ue4->save();

$ge1 = O('ge_training');
$ge1->user = $root_user;
$ge1->equipment = $equipment1;
$ge1->ntotal = 10;
$ge1->napproved = 6;
$ge1->date = 1334851245;
$ge1->save();

$ge2 = O('ge_training');
$ge2->user = $root_user;
$ge2->equipment = $equipment2;
$ge2->ntotal = 10;
$ge2->napproved = 10;
$ge2->date = 1332172845;
$ge2->save();

$ge3 = O('ge_training');
$ge3->user = $root_user;
$ge3->equipment = $equipment3;
$ge3->ntotal = 10;
$ge3->napproved = 2;
$ge3->date = 1332172845;
$ge3->save();

$ge4 = O('ge_training');
$ge4->user = $root_user;
$ge4->equipment = $equipment3;
$ge4->ntotal = 10;
$ge4->napproved = 6;
$ge4->date = 1334851245;
$ge4->save();

echo "\n环境生成完毕\n";
