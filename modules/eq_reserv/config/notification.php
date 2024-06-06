<?php

$config['classification']['user']["equipments\004仪器预约的相关消息提醒"][] = 'eq_reserv.user_confirm_reserv';
$config['classification']['user']["equipments\004仪器预约的相关消息提醒"][] = 'eq_reserv.user_confirm_edit_reserv';
$config['classification']['user']["equipments\004仪器预约的相关消息提醒"][] = 'eq_reserv.user_confirm_delete_reserv';

$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_reserv.misstime.self';
$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_reserv.overtime.self';
$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_reserv.leave_early.self';

/*迟到消息提醒
*$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_reserv.late.self';
*/

$config['classification']['user']["equipments\004仪器状态的相关消息提醒"][] = 'eq_reserv.out_of_service';
$config['classification']['user']["equipments\004仪器状态的相关消息提醒"][] = 'eq_reserv.in_service';

$config['classification']['equipment_incharge']["equipments\004负责仪器预约相关的消息提醒"][] = 'eq_reserv.contact_confirm_reserv';
$config['classification']['equipment_incharge']["equipments\004负责仪器预约相关的消息提醒"][] = 'eq_reserv.contact_confirm_edit_reserv';
$config['classification']['equipment_incharge']["equipments\004负责仪器预约相关的消息提醒"][] = 'eq_reserv.contact_confirm_delete_reserv';

$config['classification']['lab_pi']["labs\004实验室成员预约相关的消息提醒"][] = 'eq_reserv.pi_confirm_reserv';
$config['classification']['lab_pi']["labs\004实验室成员预约相关的消息提醒"][] = 'eq_reserv.pi_confirm_edit_reserv';
$config['classification']['lab_pi']["labs\004实验室成员预约相关的消息提醒"][] = 'eq_reserv.pi_confirm_delete_reserv';

$config['classification']['equipment_incharge']["equipments\004负责仪器预约及使用相关的消息提醒"][] = 'eq_reserv.misstime';
$config['classification']['equipment_incharge']["equipments\004负责仪器预约及使用相关的消息提醒"][] = 'eq_reserv.overtime';
$config['classification']['equipment_incharge']["equipments\004负责仪器预约及使用相关的消息提醒"][] = 'eq_reserv.leave_early';

/*迟到消息提醒
//以前的设计中, 爽约和超时不发送给PI, 在迟到中发送给PI, 并且归类为"仪器使用记录的相关消息提醒" 
$config['classification']['lab_pi']["equipments\004仪器使用记录的相关消息提醒"][] = 'eq_reserv.member_late.to_pi';
*/


//发送给用户的提醒
$config['eq_reserv.user_confirm_reserv'] = [
	'description'=>'设置用户预约仪器成功发送给用户的通知信息',
	'title'=>'提醒: 您预约了仪器%equipment',
	'body'=>'%user, 您好:\n\n您预约了仪器 %equipment.\n\n预约时间为 %dtstart - %dtend.\n\n备注信息:\n%description\n\n如需查看, 详细地址链接如下:\n%link',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
        '%description' => '备注信息',
        '%link'=>'链接地址',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.user_confirm_edit_reserv'] = [
	'description'=>'设置用户修改预约后发送给用户的通知信息',
	'title'=>'提醒: 您在仪器%equipment中的预约被修改',
	'body'=>'%user, 您好:\n\n您在仪器 %equipment 中的预约于 %time 被 %edit_user 修改, 修改信息如下:\n%edit_content\n\n备注信息:\n%description\n\n如需查看, 详细地址链接如下:\n%link\n\n%other_content',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%time' => '修改时间',
        '%edit_user' => '修改者姓名',
        '%edit_content' => '修改信息',
        '%description' => '备注信息',
        '%link'=>'链接地址',
        'other_content' => '其他信息',

	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.user_confirm_delete_reserv'] = [
	'description'=>'设置用户删除预约后发送给用户的通知信息',
	'title'=>'提醒: 您在仪器%equipment中的预约被删除',
	'body'=>'%user, 您好:\n\n您在仪器 %equipment 中的预约于 %time 被 %edit_user 删除.\n原预约时间为 %old_dtstart - %old_dtend.\n\n%other_content',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%time' => '删除时间',
        '%old_dtstart' => '原开始时间',
        '%old_dtend' => '原结束时间',
        '%other_content' => '其他信息',

	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

//发送给仪器联系人
$config['eq_reserv.contact_confirm_reserv'] = [
	'description'=>'设置用户预约成功发送给仪器联系人的通知信息',
	'title'=>'提醒: 有人预约了仪器%equipment',
	'body'=>'%contact, 您好:\n\n用户 %user 预约了您负责的仪器 %equipment.\n\n预约时间为 %dtstart - %dtend.\n\n备注信息:\n%description\n\n如需查看, 详细地址链接如下:\n%link\n\n用户 %user 的联系方式:\n电话: %user_phone \nEmail: %user_email \n',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'%contact' => '仪器联系人姓名',
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%dtstart' => '开始时间',
		'%dtend' => '结束时间',
		'%description' => '备注信息',
		'%link' => '链接地址',
		'%user_phone' => '用户电话',
		'%user_email' => '用户电子信箱',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.contact_confirm_edit_reserv'] = [
	'description'=>'设置用户修改预约后发送给仪器联系人的通知信息',
	'title'=>'提醒: 用户%user在仪器%equipment中的预约被修改',
	'body'=>'%contact, 您好:\n\n用户 %user 在您负责的仪器 %equipment 中的预约于 %time 被 %edit_user 修改. 修改信息如下:\n%edit_content\n\n备注信息:\n%description\n\n如需查看, 详细地址链接如下:\n%link\n\n用户 %user 的联系方式:\n电话: %user_phone\nEmail: %user_email\n',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%contact' => '仪器联系人',
		'%time' => '修改时间',
		'%edit_user' => '修改人姓名',
		'%edit_content' => '修改信息',
		'%description' => '备注信息',
        '%link' => '链接地址',
		'%user_phone' => '用户电话',
		'%user_email' => '用户电子信箱',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.contact_confirm_delete_reserv'] = [
	'description'=>'设置用户删除预约后发送给仪器联系人的通知信息',
	'title'=>'提醒: 用户%user在仪器%equipment中的预约被删除',
	'body'=>'%contact, 您好:\n\n用户 %user 在您负责的仪器 %equipment 中的预约于 %time 被 %edit_user 删除.\n\n原预约时间为 %old_dtstart - %old_dtend.\n\n备注信息:\n%description\n\n用户 %user 的联系方式:\n电话: %user_phone\nEmail: %user_email\n',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'%contact' => '仪器联系人姓名',
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%time' => '删除时间',
        '%edit_user' => '删除人姓名',
        '%old_dtstart' => '原开始时间',
        '%old_dtend' => '原结束时间',
		'%user_phone' => '用户电话',
		'%user_email' => '用户电子信箱',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

//发送给课题组PI的消息提醒
$config['eq_reserv.pi_confirm_reserv'] = [
	'description'=>'设置用户预约仪器成功发送给实验室PI的通知信息',
	'title'=>'提醒: 您实验室的成员预约了仪器%equipment',
	'body'=>'%pi, 您好:\n\n您实验室中的用户 %user 预约了仪器 %equipment.\n\n预约时间为 %dtstart - %dtend.\n\n备注信息:\n%description\n\n如需查看,详细地址链接如下:\n%link\n\n用户 %user 的联系方式:\n电话: %user_phone\nEmail: %user_email\n',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'%pi' => '实验室P.I.姓名',
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
		'%description' => '备注信息',
        '%link' => '链接地址',
		'%user_phone' => '用户电话',
		'%user_email' => '用户电子信箱',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.pi_confirm_edit_reserv'] = [
	'description'=>'设置用户修改预约后发送给实验室PI的通知信息',
	'title'=>'提醒: 您实验室中的用户%user在仪器%equipment中的预约被修改',
	'body'=>'%pi, 您好:\n\n您实验室中的用户 %user 在仪器 %equipment 中的预约于 %time 被 %edit_user 修改, 修改信息如下:\n%edit_content\n\n备注信息:\n%description\n\n如需查看, 详细地址链接如下:\n%link',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'pi' => '实验室P.I.姓名',
        '%user' => '用户姓名',  
        '%equipment' => '设备名称',
        '%time' => '修改时间',
		'%edit_user' => '修改人姓名',
		'%edit_content' => '修改信息',
		'%description' => '备注信息',
        '%link' => '链接地址',

	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.pi_confirm_delete_reserv'] = [
	'description'=>'设置用户删除预约发送给实验室PI的通知信息',
	'title'=>'提醒: 您实验室中的用户%user在仪器%equipment中的预约被删除',
	'body'=>'%pi, 您好:\n\n您实验室中的用户 %user 在仪器 %equipment中的预约于 %time 被 %edit_user 删除.\n\n原预约时间为 %old_dtstart - %old_dtend.\n\n备注信息:\n%description',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'pi' => '实验室P.I.姓名',
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%time' => '删除时间',
		'%edit_user' => '删除人姓名',
        '%old_dtstart' => '原开始时间',
        '%old_dtend' => '原结束时间',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];


$config['eq_reserv.overtime'] = [
	'description' => '设置用户使用仪器超时后向仪器负责人发送提醒消息',
	'title' => '提醒: 有人使用仪器%equipment超时',
	'body' => '%contact, 您好:\n\n%user使用您负责的仪器%equipment超时了.\n\n该用户累计超时%times次.',
	'i18n_module' => 'eq_reserv',
	'strtr' => [
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%contact' => '管理员姓名',
		'%times' => '累计超时次数'
	],
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.leave_early'] = [
	'description' => '设置用户使用仪器早退后向仪器负责人发送提醒消息',
	'title' => '提醒: 有人使用仪器%equipment早退',
	'body' => '%contact, 您好:\n\n%user使用您负责的仪器%equipment早退了.\n\n该用户累计早退%times次.',
	'i18n_module' => 'eq_reserv',
	'strtr' => [
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%contact' => '管理员姓名',
		'%times' => '累计早退次数'
	],
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.misstime'] = [
	'description' => '设置用户使用仪器爽约后向仪器负责人发送提醒消息',
	'title' => '提醒: 有人使用仪器%equipment爽约',
	'body' => '%contact, 您好:\n\n%user使用您负责的仪器%equipment爽约了.\n\n该用户累计爽约%times次.',
	'i18n_module' => 'eq_reserv',
	'strtr' => [
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%contact' => '管理员姓名',
		'%times' => '累计爽约次数'
	],
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.misstime.self'] = [
	'description' => '设置用户使用仪器爽约后向爽约用户发送提醒消息',
	'title' => '提醒: 您使用仪器%equipment爽约',
	'body' => '%user, 您好:\n\n您使用仪器%equipment爽约了.\n\n您累计爽约%times次.',
	'i18n_module' => 'eq_reserv',
	'strtr' => [
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%times' => '累计爽约次数'
	],
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];
$config['eq_reserv.overtime.self'] = [
	'description' => '设置用户使用仪器超时后向超时用户发送提醒消息',
	'title' => '提醒: 您使用仪器%equipment超时',
	'body' => '%user, 您好:\n\n您使用仪器%equipment超时了.\n\n您累计超时%times次.',
	'i18n_module' => 'eq_reserv',
	'strtr' => [
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%times' => '累计超时次数'
	],
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.leave_early.self'] = [
	'description' => '设置向用户发送使用仪器早退的提醒消息',
	'title' => '提醒: 您使用仪器%equipment早退',
	'body' => '%user, 您好:\n\n您使用仪器%equipment早退了.\n\n您累计早退%times次.',
	'i18n_module' => 'eq_reserv',
	'strtr' => [
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%times' => '累计早退次数'
	],
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

/*迟到消息提醒
$config['eq_reserv.late.self'] = array(
	'description' => '设置用户使用仪器迟到后向迟到用户发送提醒消息',
	'title' => '提醒: 您使用仪器%equipment迟到',
	'body' => '%user, 您好:\n\n您使用仪器%equipment迟到了.\n\n您累计迟到%times次.',
	'i18n_module' => 'eq_reserv',
	'strtr' => array(
		'%user' => '用户姓名',
		'%equipment' => '设备名称',
		'%times' => '累计迟到次数'
	),
	'send_by' => array(
		'email' => array('通过电子邮件发送', 0),
		'messages' => array('通过消息中心发送', 1),
	),
);
*/

$config['eq_reserv.out_of_service'] = [
	'description'=>'设置仪器故障时, 给预约用户发送的消息提醒',
	'title'=>'提醒: 仪器 %equipment 出现故障!',
	'body'=>'%user, 您好:\n\n仪器 %equipment 出现故障, 具体修复时间未知, 特此通知, 以免影响您的工作.',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'%equipment'=>'设备名称',
		'%user'=>'用户名称',
	],
	'I18N'=>'equipments',
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.in_service'] = [
	'description'=>'设置仪器故障修复后, 给预约用户发送的消息提醒',
	'title'=>'提醒: 仪器 %equipment 的故障已经被修复!',
	'body'=>'%user, 您好:\n\n仪器 %equipment 的故障已经被修复, 特此通知.',
	'i18n_module' => 'eq_reserv',
	'strtr'=>[
		'%equipment'=>'仪器名称',
		'%user'=>'用户名称',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

/*迟到消息提醒
$config['eq_reserv.member_late.to_pi'] = array(
    'description'=>'设置用户使用仪器迟到后向课题组PI发送提醒消息',
    'title'=>'提醒: 您课题组的成员使用仪器%equipment迟到了',
    'body'=>'%pi, 您好:\n\n您课题组的用户 %user 使用仪器%equipment迟到了.\n\n%user 累计迟到%times次.',
    'i18n_module' => 'eq_reserv',
    'strtr'=>array(
        '%pi' => '课题组P.I姓名',
        '%user' => '用户姓名',
        '%equipment' => '仪器名称',
        '%times' => '累计迟到次数',
    ),
    'send_by'=>array(
		'email' => array('通过电子邮件发送', 0),
		'messages' => array('通过消息中心发送', 1),
    )
);
*/

$config['equipments_conf'][] = 'notification.eq_reserv.leave_early.self';

/*
 * 2020年03月23日xian.zhou
 */
$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_reserv.violation.exceed_preset';

$config['eq_reserv.violation.exceed_preset'] = [
    'description' => '设置用户违规总次数超过阈值后向用户发送提醒消息',
    'title' => '警告: 您即将被加入仪器使用黑名单',
    'body' => '%user, 您好:\n\n您目前违规次数已达 %number 次，即将因%reason 原因被加入仪器使用黑名单!\n\n请注意规范使用！\n\n（注：一旦被加入仪器黑名单，您将无法在使用系统内仪器, 您可至个人详情页点击违规数据查看详细违规记录。）',
    'i18n_module' => 'eq_reserv',
    'strtr' => [
        '%user' => '用户姓名',
        '%number' => '违规次数',
        '%reason' => '用户最接近阈值的违规原因'
    ],
    'send_by' => [
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];
/* if (!Module::is_installed('credit')) {
    $config['equipments_conf'][] = 'notification.eq_reserv.violation.exceed_preset';
} */
