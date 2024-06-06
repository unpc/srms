<?php

require 'base.php';

$db = Database::factory();

$result = $db->query("select u.name as uname, u.ref_no, l.name as labname, e.name as ename, from_unixtime(ue.ctime) as time from user u join  ue_training ue on ue.user_id = u.id join _r_user_lab r on r.id1 = u.id join lab l on l.id = r.id2 join equipment e on e.id = ue.equipment_id 
where ue.status in (2,4) and u.member_type = 0");
while ($r = $result->row()) {
    echo join(',', [$r->uname, $r->ref_no, $r->labname, $r->ename, $r->time]) . "\n";
}


// $db = Database::factory();

// $r = $db->query("select t.id, t.note, d.admin from billing_dlut.`transaction` t join billing_dlut.distribution d on d.id = t.distribution where t.ctime >= '2022-10-01 00:00:00'");

// while ($row = $r->row()) {
//     echo "{$row->id},{$row->note},{$row->admin}\n";
// }



// // 上科大历史数据更新
// // 只有校内支付方式才需要推送到报销管理
// $config = Config::get('billing_rest')['billing'];

// $confirm = EQ_Charge_Confirm_Model::CONFIRM_INCHARGE;
// $verify = EQ_Charge_Model::VERIFY_PENDING;
// $charges = Q("(eq_sample<source | eq_reserv<source | eq_record<source) eq_charge[confirm={$confirm}][verify={$verify}]");
// $refs = [];
// foreach ($charges as $charge) {
//     $c = P($charge);
//     $c->task_id = 0;
//     $c->save();

//     $assignments = [];
//     $receiver = O('user', ['ref_no' => $charge->source->card_owner_ref_no]);
//     if ($receiver->ref_no) {
//         $assignments[] = [
//             'assign_dept' => $receiver->group->name,
//             'assign_id' => $receiver->ref_no,
//             'assign_name' => $receiver->name
//         ];
//     }

//     if (!$receiver->task_id) {
//         $subject = '您有待审核的仪器共享收费记录';
//         $url = $config['transaction_url'];
//         $sourceKey = Insert_Task::NEW_VERIFY_EQ_CHARGE;
//         $timeLine = date("YmdH");
//         $id = intval("{$receiver->id}{$timeLine}");
//         // 没必要非得hook一套，都已经在定制代码了
//         // Event::trigger('send_egate', $charge, $assinments, $subject, $url, $sourceKey);
//         $res = Egate_Helper::sendNewTask(
//             $id,
//             $url,
//             $assignments,
//             $subject,
//             $sourceKey
//         );

//         if ($res['res']['push_result'] == 'SUCCESS' && $res['task_id']) {
//             $receiver->task_id = $res['task_id'];
//             $receiver->save();
//         }

//         echo '.';
//     }
// }

// $headers = [
//     'appId' => $appId,
//     'accessToken' => $accessToken,
//     'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
// ];

// $taskId = "100000999";
// $realId = "100000999";
// $taskinfo = [];
// $taskinfo['updatetasks'][] = [
//     'app_id' => 'v',
//     'task_id' => $taskId,
//     'status' => 'COMPLETE',
//     'process_instance_id' => "{$realId}",
//     'process_instance_status' => 'COMPLETE',
//     'actual_owner_id' => '100315',
//     'actual_owner_name' => '钟桂生'
// ];
// try {
//     $client = new \GuzzleHttp\Client([
//         'verify' => false
//     ]);
//     $rpcurl = $url . '?' . http_build_query([
//         'appId' => 'v',
//         'taskInfo' => json_encode($taskinfo)
//     ]);
//     var_dump(json_encode($taskinfo));
//     var_dump($headers);
//     $res = $client->post($rpcurl, [
//         'headers' => $headers
//     ])->getBody()->getContents();
//     $r = json_decode($res, true);
//     var_dump($r);
// } catch (Exception $e) {
//     $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();
//     var_dump("[exception] $message", 'rest');
// }



// $subject = '您有待审核的仪器共享收费记录';
// $redirectURL = 'https://testeshare.shanghaitech.edu.cn/lims/';
// $sourceKey = Insert_Task::NEW_VERIFY_EQ_CHARGE;
// $task_id = '100000999';

// $taskinfo = [];
// $taskinfo['inserttasks'][] = [
//     'app_id' => 'v',
//     'subject' => $subject,
//     'task_id' => $task_id,
//     'priority' => 0,
//     'appName' => '大型仪器管理系统',
//     'biz_key' => $task_id,
//     'biz_domain' => '大型仪器共享平台',
//     'process_instance_id' => $task_id,
//     'process_id' => 'regist_' . $task_id,
//     'assignments' => [
//         [
//             'assign_dept' => '化学院',
//             'assign_id' => '100315',
//             'assign_name' => '钟桂生'
//         ]
//     ],
//     'created_by_depts' => '设备处', // todo
//     'created_by_ids' => 'admin', // todo 不确定应当是大仪的账号还是申请人的，不过无所谓
//     'created_by_names' => '系统管理员', // todo
//     'created_on' => '2024-01-17 16:30:00',
//     'form_url' => $redirectURL,
//     'form_url_view' => $redirectURL,
//     'form_mobile_url' => $redirectURL,
//     'form_mobile_url_view' => $redirectURL,
//     'node_id' => '1',
//     'node_name' => $subject,
//     'process_instance_image_url' => $redirectURL,//先注释掉看看
//     'process_instance_initiator' => 'admin',//先注释掉看看
//     'process_instance_initiator_dp' => '用户管理部门',//先注释掉看看
//     'process_instance_initiator_id' => 'ampadmin',//先注释掉看看
//     'process_instance_status' => 'RUNNING',
//     'process_instance_subject' => $subject,
//     'process_name' => $subject,
//     'process_version' => '1.0',
//     'status' => 'ACTIVE'
// ];

// try {
//     $client = new \GuzzleHttp\Client([
//         'verify' => false
//     ]);
//     $rpcurl = $url . '?' . http_build_query([
//         'appId' => 'v',
//         'taskInfo' => json_encode($taskinfo)
//     ]);
//     var_dump(json_encode($taskinfo));
//     var_dump($headers);
//     $res = $client->post($rpcurl, [
//         'headers' => $headers
//     ])->getBody()->getContents();
//     $r = json_decode($res, true);
//     var_dump($r);
// } catch (Exception $e) {
//     $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();
//     var_dump("[exception] $message", 'rest');
// }






// $taskId = "100000999"
// $realId = "100000999";
// $taskinfo = [];
// $taskinfo['updatetasks'][] = [
//     'app_id' => 'v',
//     'task_id' => $taskId,
//     'status' => 'COMPLETE',
//     'process_instance_id' => "{$realId}",
//     'process_instance_status' => 'COMPLETE',
// ];
// try {
//     $client = new \GuzzleHttp\Client([
//         'verify' => false
//     ]);
//     $rpcurl = $url . '?' . http_build_query([
//         'appId' => 'v',
//         'taskInfo' => json_encode($taskinfo)
//     ]);
//     var_dump($taskinfo);
//     var_dump($headers);
//     $res = $client->post($rpcurl, [
//         'headers' => $headers
//     ])->getBody()->getContents();
//     $r = json_decode($res, true);
//     if ($r['push_result'] == 'SUCCESS') {
//         $charge->task_id = '';
//         $charge->save();
//     }
//     var_dump($r);
// } catch (Exception $e) {
//     $message = __CLASS__ . '::' . __FUNCTION__ . ' ' . $e->getMessage();
//     var_dump("[exception] $message", 'rest');
// }

// $db = Database::factory();
// $r = $db->query("SELECT * FROM `lims2`.`user` WHERE `token` LIKE '%database%' AND `ctime` <= 1696089600 AND `atime` = 0");
// while ($row = $r->row()) {
//     $uid = $row->id;
//     $sample_count = (int)$db->value("SELECT COUNT(id) FROM `eq_sample` WHERE `sender_id` = {$uid}");
//     $record_count = (int)$db->value("SELECT COUNT(id) FROM `eq_record` WHERE `user_id` = {$uid}");
//     $reserv_count = (int)$db->value("SELECT COUNT(id) FROM `eq_reserv` WHERE `user_id` = {$uid}");
//     $charge_count = (int)$db->value("SELECT COUNT(id) FROM `eq_charge` WHERE `user_id` = {$uid}");
//     if ($sample_count || $record_count || $reserv_count || $charge_count) {
//         $user = O('user', $uid);
//         if ($user->id) continue;
//         $user->id = $uid;
//         $user->token = $row->token;
//         $user->email = $row->email;
//         $user->name = $row->name;
//         $user->card_no = $row->card_no;
//         $user->card_no_s = $row->card_no_s;
//         $user->dfrom = $row->dfrom;
//         $user->dto = $row->dto;
//         $user->weight = $row->weight;
//         $user->atime = 0;
//         $user->ctime = $row->ctime;
//         $user->mtime = $row->mtime;
//         $user->hidden = $row->hidden;
//         $user->phone = $row->phone;
//         $user->address = $row->address;
//         $user->group = O('tag', $row->group_id);
//         $user->member_type = $row->member_type;
//         $user->creator = O('user', $row->creator_id);
//         $user->auditor = O('user', $row->auditor_id);
//         $user->ref_no = $row->ref_no ?: NULL;
//         $user->binding_email = $row->binding_email;
//         $user->gapper_id = $row->gapper_id;
//         $arr = json_decode(($row->_extra ?: '{}'), TRUE);
//         foreach ($arr as $k => $value ) {
//             $user->$k = $value;
//         }
//         $user->binding_phone = $row->binding_phone;
//         if ($user->save()) {
//             $rr = $db->query("SELECT * FROM `lims2`.`_r_user_lab` WHERE `id1` = {$user->id}");
//             while ($x = $rr->row()) {
//                 $user->connect(O('lab', $x->id2));
//             }
//             echo '.';
//         }
//         else {
//             echo "{$uid}x";
//         }
//     }
// }





// $users = Q("user[token*=ids]");
// $count = 0;

// foreach($users as $user) {
//     $record_count = Q("eq_record[user={$user}]")->total_count();
//     $sample_count = Q("eq_sample[sender={$user}]")->total_count();
//     $reserv_count = Q("eq_reserv[user={$user}]")->total_count();
//     $charge_count = Q("eq_charge[user={$user}]")->total_count();
//     $transaction_count = Q("billing_transaction[user={$user}]")->total_count();
//     $dc_record_count = Q("dc_record[user={$user}]")->total_count();
//     $training_count = Q("ue_training[user={$user}]")->total_count();
//     if (!$record_count && !$sample_count && !$reserv_count && !$charge_count && !$transaction_count && !$dc_record_count && !$training_count) {
//         // PI
//         $lab = O('lab', ['owner' => $user]);
//         if ($lab->id) {
//             $rcount = Q("{$lab} user eq_record")->total_count();
//             $scount = Q("{$lab} user<sender eq_sample")->total_count();
//             $recount = Q("{$lab} user eq_reserv")->total_count();
//             $ccount = Q("{$lab} user eq_charge")->total_count();
//             $tcount = Q("{$lab} user billing_transaction")->total_count();
//             $dc_count = Q("{$lab} user dc_record")->total_count();
//             $trcount = Q("{$lab} user ue_training")->total_count();
//             if ($rcount || $scount || $recount || $ccount || $tcount || $dc_count || $trcount) {
//                 continue;
//             }
//         }
//         // 机主及其更大权限
//         if (Q("{$user}<incharge equipment")->total_count()) {
//             continue;
//         }
//         $roles = join(',', [1,8,7,9]); 
//         if (Q("{$user} role[id={$roles}]")->total_count()) {
//             continue;
//         }
//         $user->delete();
//         if ($lab->id) {
//             $lab->delete();
//         }
//         echo '.'; 
//         // $roles = join(',', Q("{$user} role")->to_assoc('id', 'name'));
//         // $labs = join(',', Q("{$user} lab")->to_assoc('id', 'name'));
//         // echo "{$user->name}, {$roles}, {$labs}\n";
//         // $count++;
//     }
// }


// $users = Q("user[token*=database][ctime<1696089600][!atime]");
// foreach ($users as $user) {
//     $labs = Q("{$user} lab");
//     foreach ($labs as $lab) {
//         if (!$lab->atime && Q("{$lab} user") <= 0) {
//             $lab->delete();
//         }
//     }
//     $user->delete();
//     echo '.';
// }

// Q("user[!token]")->delete_all();

// // 检查中间库数据
// $users = Q("user[token*=ids][atime]");
// $rpc_conf = Config::get('rpc.gateway');
// $url = $rpc_conf['url'];
// $rpc = new RPC($url);
// if (!$rpc->Gateway->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
//     throw new RPC_Exception;
// }
// foreach ($users as $user) {
//     list($token, $backend) = Auth::parse_token($user->token);
//     try {
//         $remote_user = $rpc->Gateway->People->GetUser($token);
//     } catch(RPC_Exception $e) {
//         continue;
//     }
//     if (!$remote_user['ref_no']) {
//         $user->atime = 0;
//         $user->save();
//         echo "{$user->name}[$user->id][{$remote_user['ref_no']}] - delete\n";
//     }
//     else {
//         echo "{$user->name}[$user->id][{$remote_user['ref_no']}]\n";
//     }
// }
// echo "\n";



// $targetLabId = 717;
// $targetLabPIId = 2270;
// $sourceLabId = 124;
// $db = Database::factory();

// $mergelabs = [1261, 717];

// $rlab = $db->query("SELECT * FROM `lims2`.`lab` WHERE `id` = {$targetLabId}")->row();
// $slab = O('lab', $targetLabId);
// $sUser = O('user', $targetLabPIId);
// if (!$slab->id) {
//     $slab->id = $targetLabId;
// }
// $slab->name = $rlab->name;
// $slab->ref_no = $slab->ref_no ?: $rlab->ref_no;
// $slab->rank = $slab->rank ?: $rlab->rank;
// $slab->description = $slab->description ?: $rlab->description;
// $slab->contact = $slab->contact ?: $rlab->contact;
// $slab->enable_reg_notif = true;
// $slab->ignore_PI_reg_notif = true;
// $slab->type = $slab->type ?: $rlab->type;
// $slab->subject = $slab->subject ?: $rlab->subject;
// $slab->util_area = $slab->util_area ?: $rlab->util_area;
// $slab->location = $slab->location ?: $rlab->location;
// $slab->location2 = $slab->location2 ?: $rlab->location2;
// $slab->owner = $sUser;
// $slab->group = O('tag', $rlab->group_id);
// $slab->save();

// foreach (Q("{$sUser} lab") as $lab) {
//     $sUser->disconnect($lab);
// }
// $sUser->connect($slab, 'pi');

// $mergelabs = join(',', $mergelabs);

// // 用户全部转移过来
// $uresult = $db->query("SELECT * FROM `lims2`.`_r_user_lab` WHERE `id2` IN ({$mergelabs})");
// while ($r = $uresult->row()) {
//     $db->query("UPDATE `_r_user_lab` SET `id2` = {$targetLabId} WHERE `id2` = {$sourceLabId} AND `id1` = {$r->id1}");
// }
// // 用户记录全部转移过来

// $db->query("UPDATE `lims2_whu`.`lab_project` `whu` JOIN `lims2`.`lab_project` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`eq_reserv` `whu` JOIN `lims2`.`eq_reserv` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`eq_record` `whu` JOIN `lims2`.`eq_record` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`eq_sample` `whu` JOIN `lims2`.`eq_sample` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`approval` `whu` JOIN `lims2`.`approval` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`eq_banned` `whu` JOIN `lims2`.`eq_banned` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`eq_banned_record` `whu` JOIN `lims2`.`eq_banned_record` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`fund_task_apply` `whu` JOIN `lims2`.`fund_task_apply` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`fund_task_apply_transaction` `whu` JOIN `lims2`.`fund_task_apply_transaction` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`fund_task_apply_closing_information` `whu` JOIN `lims2`.`fund_task_apply_closing_information` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// $db->query("UPDATE `lims2_whu`.`eq_charge` `whu` JOIN `lims2`.`eq_charge` AS `bak` ON `bak`.`id` = `whu`.`id` SET `whu`.`lab_id` = {$targetLabId} WHERE `bak`.`lab_id` IN ({$mergelabs})");
// foreach (Q("eq_charge[lab_id={$mergelabs}]") as $charge) {
//     $charge->lab = $slab;
//     $charge->save();
// }

// // 成果管理调整
// $uresult = $db->query("SELECT * FROM `lims2`.`_r_lab_award` WHERE `id1` IN ({$mergelabs})");
// while ($r = $uresult->row()) {
//     $db->query("UPDATE `_r_lab_award` SET `id1` = {$targetLabId} WHERE `id2` = {$r->id2}");
// }

// $uresult = $db->query("SELECT * FROM `lims2`.`_r_patent_lab` WHERE `id2` IN ({$mergelabs})");
// while ($r = $uresult->row()) {
//     $db->query("UPDATE `_r_patent_lab` SET `id2` = {$targetLabId} WHERE `id1` = {$r->id1}");
// }

// $uresult = $db->query("SELECT * FROM `lims2`.`_r_publication_lab` WHERE `id2` IN ({$mergelabs})");
// while ($r = $uresult->row()) {
//     $db->query("UPDATE `_r_publication_lab` SET `id2` = {$targetLabId} WHERE `id1` = {$r->id1}");
// }

// // 财务数据调整
// $uresult = $db->query("SELECT * FROM `lims2`.`billing_account` WHERE `lab_id` IN ({$mergelabs})");
// while($row = $uresult->row()) {
//     $department = O('billing_department', $row->department_id);
//     if ($department->id) {
//         $saccount = O('billing_account', ['department'=>$department, 'lab'=>$slab]);
//         if (!$saccount->id) {
//             $saccount = $department->add_account_for_lab($slab);
//         }
//         $saccount->credit_line = max($saccount->credit_line, $row->credit_line);
//         $saccount->save();

//         // 主要是充值和扣费记录
//         $result = $db->query("SELECT * FROM `lims2`.`billing_transaction` `t` WHERE `account_id` = {$row->id}");
//         while ($rr = $result->row()) {
//             $transaction = O('billing_transaction', $rr->id);
//             if ($transaction->id) {
//                 $transaction->account = $saccount;
//                 $description = (array)$transaction->description;
//                 $description['%account'] = Markup::encode_Q($slab);
//                 $transaction->description = $description;
//                 $transaction->save();
//             }
//         }

//         Billing_Account::update_balance($saccount);
//     }
// }

// foreach (Q("{$slab} user[id!=15052] eq_record[ctime>=1697731200]") as $record) {
//     $record->lab = $slab;
//     echo $record->save() ? '.' : 'x';
// }

// foreach (Q("{$slab} user[id!=15052] eq_reserv[ctime>=1697731200]") as $record) {
//     $record->lab = $slab;
//     echo $record->save() ? '.' : 'x';
// }

// foreach (Q("{$slab} user[id!=15052]<sender eq_sample[ctime>=1697731200]") as $record) {
//     $record->lab = $slab;
//     echo $record->save() ? '.' : 'x';
// }

// foreach (Q("{$slab} user[id!=15052] eq_charge[ctime>=1697731200]") as $record) {
//     $record->lab = $slab;
//     echo $record->save() ? '.' : 'x';
// }
// echo "\n";



/*
$db = Database::factory();
$result = $db->query("SELECT * FROM `merge_lab` WHERE `selected` = 1");
while ($slabdata = $result->row()) {
    
    $merge = $slabdata->merge;
    $labsdata = $db->query("SELECT * FROM `merge_lab` WHERE `merge` = {$merge}")->rows();
    // 1. 设定选中课题组，其他课题组基础信息合并，以所选课题组为主要信息来源，课题组名称设定为 PI姓名+课题组
    // 6. 消息提醒和送样预约审核配置以所选课题组为主 
    //    6.1. 复合课题组配置信息合并，以所选课题组为主要信息来源
    $slab = O('lab', $slabdata->id);
    $slab->name = "{$slab->owner->name}课题组";
    $slab->group = $slab->owner->group;
    echo "======== 开始更新课题组{$slab->name} =======\n";
    echo "======== 1. 设定选中课题组，其他课题组基础信息合并，以所选课题组为主要信息来源，课题组名称设定为 PI姓名+课题组 =======\n";
    foreach ($labsdata as $data) {
        if ($data->selected) continue;
        $lab = O('lab', $data->id);
        if (!$lab->id) continue;
        $slab->ref_no = $slab->ref_no ?: $lab->ref_no;
        $slab->rank = $slab->rank ?: $lab->rank;
        $slab->description = $slab->description ?: $lab->description;
        $slab->contact = $slab->contact ?: $lab->contact;
        $slab->enable_reg_notif = $slab->enable_reg_notif ?: $slab->enable_reg_notif;
        $slab->ignore_PI_reg_notif = $slab->ignore_PI_reg_notif ?: $slab->ignore_PI_reg_notif;
        $slab->type = $slab->type ?: $lab->type;
        $slab->subject = $slab->subject ?: $lab->subject;
        $slab->util_area = $slab->util_area ?: $lab->util_area;
        $slab->location = $slab->location ?: $lab->location;
        $slab->location2 = $slab->location2 ?: $lab->location2;
        $slab->helper = $slab->helper->id ? $slab->helper : $lab->helper;
    }
    $slab->save();
    $owner = $slab->owner;

    echo "\n======== 2. 保留唯一校内PI用户 =======\n";
    // 2. 保留唯一校内PI用户
    //    2.1. 不同ID的PI需要进行智能判断是否为之前的未激活用户，去掉PI身份，通过随机码账号来判断，设定为未激活，通过四记录来源的课题组信息来推断出之前课题组，做实际课题组还原 
    //    2.1. 课题组组织机构按照设定的PI的组织机构进行更新 -- 1. 已经按需更新了
    foreach ($labsdata as $data) {
        if ($data->selected) continue;
        $lab = O('lab', $data->id);
        $pi = O("user", $data->uid);
        if (!$pi->id || !$lab->id) continue;
        if ($pi->id == $owner->id) {
            // 相同用户仅解除与课题组关系即可
            $pi->disconnect($lab);
            continue;
        }
        if ($data->ref_no && strlen($data->ref_no) > 10) {
            // 乱码用户设定为未激活用户，解除与课题组关系，根据记录定位真实课题组进行关联
            $pi->atime = 0;
            $pi->save();
            $pi->disconnect($lab);
            $findlab = Q("eq_record[user={$pi}] lab:limit(1)")->current();
            if (!$findlab->id) $findlab = Q("eq_sample[user={$pi}] lab:limit(1)")->current();
            if (!$findlab->id) $findlab = Q("eq_charge[user={$pi}] lab:limit(1)")->current();
            if (!$findlab->id) $findlab = $slab;
            $pi->connect($findlab);
        }
        else {
            // 非系统自行转换课题组用户，解除与课题组关系且更新至新课题组，激活状态不动
            $pi->disconnect($lab);
            $pi->connect($slab);
        }
    }

    echo "\n======== 3. 记录（使用、预约、送样、收费、门禁进出）叠加合并 =======\n";
    // 3. 记录（使用、预约、送样、收费、门禁进出）叠加合并
    //    3.1. 使用记录为复合课题组ID的均更新为选定课题组
    //    3.2. 预约记录为复合课题组ID的均更新为选定课题组
    //    3.3. 送样记录为复合课题组ID的均更新为选定课题组
    //    3.4. 收费记录为复合课题组ID的均更新为选定课题组
    //    3.5. 预约审批记录为复合课题组ID的均更新为选定课题组
    //    3.6. 黑名单记录为复合课题组ID的均更新为选定课题组
    //    3.7. 基金数据为复合课题组ID的均更新为选定课题组
    foreach ($labsdata as $data) {
        if ($data->selected) continue;
        $lab = O('lab', $data->id);
        if (!$lab->id) continue;
        $db->query("UPDATE `eq_record` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        
        $db->query("UPDATE `eq_sample` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `eq_reserv` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `approval` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `eq_banned` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `eq_banned_record` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `fund_task_apply` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `fund_task_apply_transaction` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `fund_task_apply_closing_information` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        $db->query("UPDATE `eq_charge` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");
        foreach (Q("eq_charge[lab={$lab}]") as $charge) {
            $charge->lab = $slab;
            $charge->save();
        }
        echo ".";
    }

    echo "\n======== 4. 成员信息叠加合并, 5. 关联项目合并 =======\n";
    // 4. 成员信息叠加合并
    //    4.1. 将复合课题组的成员均更新在选定课题组名下（确认下是否必须用数据库更新操作）
    // 5. 关联项目合并
    //    5.1. 将复合课题组的项目均合并到选定课题组名下
    //    5.2. 各种成果与课题组的关联进行调整
    foreach ($labsdata as $data) {
        if ($data->selected) continue;
        $lab = O('lab', $data->id);
        if (!$lab->id) continue;
        foreach (Q("{$lab} user") as $user) {
            $user->disconnect($lab);
            $user->connect($slab);
        }

        $db->query("UPDATE `lab_project` SET `lab_id` = {$slab->id} WHERE `lab_id` = {$lab->id}");

        foreach (Q("{$lab} publication") as $publication) {
            $publication->disconnect($lab);
            $publication->connect($slab);
        }

        foreach (Q("{$lab} award") as $award) {
            $award->disconnect($lab);
            $award->connect($slab);
        }

        foreach (Q("{$lab} patent") as $patent) {
            $patent->disconnect($lab);
            $patent->connect($slab);
        }
        echo ".";
    }

    echo "\n======== 7. 财务账号数据合并 =======\n";
    // 7. 财务账号数据合并
    //    7.1. 选定课题组建立复合数据中合集的各财务账号
    //    7.2. 选定课题组各财务账号的信用额度取复合数据集中的最大值
    //    7.3.1 财务明细记录为复合课题组ID的均课题组更新为选定课题组
    //    7.3.2 财务明细记录为复合课题组ID的财务账号Account信息更新为选定课题组新同类财务账号Account信息
    foreach ($labsdata as $data) {
        if ($data->selected) continue;
        $lab = O('lab', $data->id);
        if (!$lab->id) continue;
        foreach(Q("billing_account[lab={$lab}]") as $account) {
            $department = $account->department;
            $saccount = O('billing_account', ['department'=>$department, 'lab'=>$slab]);
            if (!$saccount->id) {
                $saccount = $department->add_account_for_lab($slab);
            }
            
            $saccount->credit_line = max($saccount->credit_line, $account->credit_line);
            $saccount->save();

            // 主要是充值和扣费记录
            foreach (Q("billing_transaction[account={$account}]") as $transaction) {
                $transaction->account = $saccount;
                $description = (array)$transaction->description;
                $description['%account'] = Markup::encode_Q($slab);
                $transaction->description = $description;
                $transaction->save();
            }

            Billing_Account::update_balance($saccount);
        }
        echo ".";
    }

    echo "\n======== 8. 非选中课题组数据清理(如有报错会抛出异常课题组信息，待后续处理) =======\n";
    // 8. 非选中课题组数据清理
    //    8.1. 确保课题组项目在选中课题组存在，不存在报错提醒
    //    8.2. 确保课题组名下无任何记录信息（使用、预约、送样、收费、财务明细），如有则进行报错提醒
    //    8.3. 确保课题组名下无任何人员信息，如有则进行报错提醒
    //    8.4. 确保课题组名下的财务账号下无任何财务明细，如有则进行报错提醒
    //    8.5. 确保无误后进行数据顺序清理（报销明细?? => 财务账号 => 课题组删除）
    foreach ($labsdata as $data) {
        if ($data->selected) continue;
        $lab = O('lab', $data->id);
        if (!$lab->id) continue;
        if (Q("lab_project[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 lab_project 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("eq_record[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 eq_record 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("eq_sample[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 eq_sample 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("eq_reserv[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 eq_reserv 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("eq_charge[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 eq_charge 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("billing_transaction[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 billing_transaction 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("approval[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 approval 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("eq_banned[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 eq_banned 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("eq_banned_record[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 eq_banned_record 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("fund_task_apply[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 fund_task_apply 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("fund_task_apply_transaction[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 fund_task_apply_transaction 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("fund_task_apply_closing_information[lab={$lab}]")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 fund_task_apply_closing_information 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("{$lab} publication")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 publication 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("{$lab} award")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 award 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("{$lab} patent")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 patent 未转移!\n";
            // 报错处理
            continue;
        }
        if (Q("{$lab} user")->total_count()) {
            echo "{$lab->id} => {$lab->name} 存在 user 未转移!\n";
            // 报错处理
            continue;
        }
        foreach(Q("billing_account[lab={$lab}]") as $account) {
            if (Q("billing_transaction[account={$account}]")->total_count()) {
                echo "{$lab->id} => {$lab->name} 存在 billing_transaction 未转移!\n";
                // 报错处理
                continue;
            }
        }

        // 财务账号 => 课题组删除
        foreach(Q("billing_account[lab={$lab}]") as $account) {
            $account->delete();
        }
        $lab->delete();
    }
}

// 9. 清理剩余课题组（无用户、无记录、无财务账号）



// 武大合并脚本思路演进
// 1. 设定选中课题组，其他课题组基础信息合并，以所选课题组为主要信息来源，课题组名称设定为 PI姓名+课题组
// 2. 保留唯一校内PI用户
//    2.1. 不同ID的PI需要进行智能判断是否为之前的未激活用户，去掉PI身份，通过随机码账号来判断，设定为未激活，通过四记录来源的课题组信息来推断出之前课题组，做实际课题组还原 
//    2.1. 课题组组织机构按照设定的PI的组织机构进行更新
// 3. 记录（使用、预约、送样、收费、门禁进出）叠加合并
//    3.1. 使用记录为复合课题组ID的均更新为选定课题组
//    3.2. 预约记录为复合课题组ID的均更新为选定课题组
//    3.3. 送样记录为复合课题组ID的均更新为选定课题组
//    3.4. 收费记录为复合课题组ID的均更新为选定课题组
//    3.5. 预约审批记录为复合课题组ID的均更新为选定课题组
//    3.6. 黑名单记录为复合课题组ID的均更新为选定课题组
//    3.7. 基金数据为复合课题组ID的均更新为选定课题组
// 4. 成员信息叠加合并
//    4.1. 将复合课题组的成员均更新在选定课题组名下（确认下是否必须用数据库更新操作）
// 5. 关联项目合并
//    5.1. 将复合课题组的项目均合并到选定课题组名下
//    5.2. 各种成果与课题组的关联进行调整
// 6. 消息提醒和送样预约审核配置以所选课题组为主 
//    6.1. 复合课题组配置信息合并，以所选课题组为主要信息来源
// 7. 财务账号数据合并
//    7.1. 选定课题组建立复合数据中合集的各财务账号
//    7.2. 选定课题组各财务账号的信用额度取复合数据集中的最大值
//    7.3.1 财务明细记录为复合课题组ID的均课题组更新为选定课题组
//    7.3.2 财务明细记录为复合课题组ID的财务账号Account信息更新为选定课题组新同类财务账号Account信息
// 8. 非选中课题组数据清理
//    8.1. 确保课题组项目在选中课题组存在，不存在报错提醒
//    8.2. 确保课题组名下无任何记录信息（使用、预约、送样、收费、财务明细），如有则进行报错提醒
//    8.3. 确保课题组名下无任何人员信息，如有则进行报错提醒
//    8.4. 确保课题组名下的财务账号下无任何财务明细，如有则进行报错提醒
//    8.5. 确保无误后进行数据顺序清理（报销明细?? => 财务账号 => 课题组删除）
// 9. 清理剩余课题组（无用户、无记录、无财务账号）
*/
