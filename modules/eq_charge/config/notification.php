<?php
$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'eq_charge.reserv_add_charge_to_pi';
$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'eq_charge.reserv_edit_charge_to_pi';
$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'eq_charge.reserv_delete_charge_to_pi';

$config['classification']['user']["equipments\004仪器使用计费相关的消息提醒"][] = 'eq_charge.reserv_add_charge_to_user';
$config['classification']['user']["equipments\004仪器使用计费相关的消息提醒"][] = 'eq_charge.reserv_edit_charge_to_user';
$config['classification']['user']["equipments\004仪器使用计费相关的消息提醒"][] = 'eq_charge.reserv_delete_charge_to_user';

$config['classification']['user']["equipments\004仪器送样收费相关的消息提醒"][] = 'eq_charge.add_sample.sender';

$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'eq_charge.record_add_charge_to_pi';
$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'eq_charge.record_edit_charge_to_pi';
$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'eq_charge.record_delete_charge_to_pi';
$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'eq_charge.add_sample.pi';


$config['classification']['user']["equipments\004仪器使用计费相关的消息提醒"][] = 'eq_charge.record_add_charge_to_user';
$config['classification']['user']["equipments\004仪器使用计费相关的消息提醒"][] = 'eq_charge.record_edit_charge_to_user';
$config['classification']['user']["equipments\004仪器使用计费相关的消息提醒"][] = 'eq_charge.record_delete_charge_to_user';

$config['classification']['user']["eq_sample\004仪器送样的相关消息提醒"][] = 'eq_charge.edit_sample_charge.sender';
$config['classification']['user']["eq_sample\004仪器送样的相关消息提醒"][] = 'eq_charge.delete_sample_charge.sender';
$config['classification']['lab_pi']["labs\004实验室成员送样相关消息提醒"][] = 'eq_charge.edit_sample_charge.pi';
$config['classification']['lab_pi']["labs\004实验室成员送样相关消息提醒"][] = 'eq_charge.delete_sample_charge.pi';

//预约变动导致的使用计费变动发送的消息提醒
$config['eq_charge.reserv_add_charge_to_user'] = [
    'description'=>'设置仪器新增预约导致使用计费变动给使用者发送的消息提醒',
    'title'=>'提醒: 您的预约产生了新的计费',
    'body'=>'%user, 您好:\n\n您预约了仪器 %equipment\n预约时间为 %dtstart - %dtend\n由此产生的使用收费(#%id)为: %amount\n\n如需查看, 详细地址链接如下: \n%link',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%id'=>'使用收费id',
        '%amount'=>'使用收费金额',
        '%link'=>'链接地址',
        '%dtstart'=>'预约开始时间',
        '%dtend'=>'预约结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.reserv_add_charge_to_pi'] = [
    'description'=>'设置仪器新增预约导致预约收费超标给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的用户%user超额预约了仪器',
    'body'=>'%pi, 您好:\n\n您实验室中的用户 %user 通过大型仪器共享管理系统预约了 %equipment\n预约时间为 %dtstart - %dtend\n该次仪器预约产生了新的预约收费(#%id)\n由此产生的预约收费为: %amount, 超出了您设置的标准: %min_notification_fee, 特此提醒!\n\n祝您: 工作顺利, 心情愉快!',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        'pi'=>'实验室P.I.姓名',
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%id'=>'使用收费id',
        '%amount'=>'使用收费金额',
        '%min_notification_fee' => '需提醒的金额上限',
        '%dtstart'=>'预约开始时间',
        '%dtend'=>'预约结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.reserv_edit_charge_to_user'] = [
    'description'=>'设置仪器预约被修改导致预约收费变动给预约者发送的消息提醒',
    'title'=>'提醒: 您的使用收费发生改变',
    'body'=>'%user, 您好:\n\n您在 %equipment 中的预约被 %edit_user 修改\n预约时间为 %dtstart - %dtend\n导致预约收费(#%id)变动如下: \n 修改前预约收费为: %old_amount, 修改后预约收费为: %new_amount\n\n如需查看, 详细地址链接如下:\n%link',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'修改者姓名',
        '%id'=>'预约收费id',
        '%old_amount'=>'修改前的计费',
        '%new_amount'=>'修改后的计费',
        '%link'=>'链接地址',
        '%dtstart'=>'预约开始时间',
        '%dtend'=>'预约结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.reserv_edit_charge_to_pi'] = [
    'description'=>'设置仪器预约被修改导致预约收费超标给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的用户%user超额预约了仪器',
    'body'=>'%pi, 您好:\n\n您实验室中的用户 %user 在 %equipment 中的预约被 %edit_user 修改\n预约时间为 %dtstart - %dtend\n导致预约收费(#%id)变动如下: \n 修改前收费为: %old_amount, 修改后收费为: %new_amount\n修改后的预约收费超出了您设置的标准: %min_notification_fee, 特此提醒!\n\n祝您: 工作顺利, 心情愉快!',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        'pi'=>'实验室P.I.姓名',
        '%user'=>'用户名称',
        '%edit_user'=>'修改者姓名',
        '%id'=>'预约收费id',
        '%old_amount'=>'修改前的计费',
        '%new_amount'=>'修改后的计费',
        '%min_notification_fee' => '需提醒的金额上限',
        '%dtstart'=>'预约开始时间',
        '%dtend'=>'预约结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.reserv_delete_charge_to_user'] = [
    'description'=>'设置仪器预约被删除导致预约收费被删除给预约者发送的消息提醒',
    'title'=>'提醒: 您的预约收费被删除',
    'body'=>'%user, 您好:\n\n您在 %equipment 中的预约被 %edit_user 删除\n原预约时间为 %dtstart - %dtend\n导致预约收费(#%id)被删除: \n 原预约收费为: %old_amount',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'修改者姓名',
        '%id'=>'预约收费id',
        '%old_amount'=>'修改前的计费',
        '%dtstart'=>'预约开始时间',
        '%dtend'=>'预约结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.reserv_delete_charge_to_pi'] = [
    'description'=>'设置仪器预约被删除导致预约收费被删除给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的用户%user的预约收费被删除',
    'body'=>'%pi, 您好:\n\n您实验室中的用户 %user 在 %equipment 中的预约被 %edit_user 删除\n原预约时间为 %dtstart - %dtend\n导致预约收费(#%id)被删除:\n 原预约收费为: %old_amount.',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        'pi'=>'实验室P.I.姓名',
        '%edit_user'=>'修改者姓名',
        '%id'=>'预约收费id',
        '%old_amount'=>'原预约收费金额',
        '%dtstart'=>'预约开始时间',
        '%dtend'=>'预约结束时间',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

//使用记录变动导致的计费变动发送的消息提醒
$config['eq_charge.record_add_charge_to_user'] = [
    'description'=>'设置仪器新增使用记录导致使用计费变动给使用者发送的消息提醒',
    'title'=>'提醒: 您的使用记录产生了新的计费',
    'body'=>'%user, 您好:\n\n%edit_user 为您添加了 %equipment的使用记录(#%record_id) \n由此产生的使用收费(#%transaction_id)为: %amount\n\n如需查看, 详细地址链接如下: \n%link',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'添加人姓名',
        '%record_id'=>'使用记录id',
        '%transaction_id'=>'使用收费id',
        '%amount'=>'使用收费金额',
        '%link'=>'链接地址',

    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.record_add_charge_to_pi'] = [
    'description'=>'设置仪器新增使用记录导致使用收费超标给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的用户%user超额预约了仪器',
    'body'=>'%pi, 您好:\n\n%edit_user 为您实验室中的用户 %user 添加了 %equipment的使用记录(#%record_id) \n由此产生的使用收费(#%transaction_id)为: %amount, 超出了您设置的标准: %min_notification_fee, 特此提醒!\n\n祝您: 工作顺利, 心情愉快!',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        'pi'=>'实验室P.I.姓名',
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'添加人姓名',
        '%id'=>'使用收费id',
        '%amount'=>'使用收费金额',
        '%min_notification_fee' => '需提醒的金额上限',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.record_edit_charge_to_user'] = [
    'description'=>'设置修改仪器使用记录导致使用计费变动给使用者发送的消息提醒',
    'title'=>'提醒: 您的使用收费发生改变',
    'body'=>'%user, 您好:\n\n%edit_user 修改了您在 %equipment 中的使用记录(#%record_id), 导致使用收费(#%transaction_id)变动如下: \n修改前使用收费为: %old_amount, 修改后使用收费为: %new_amount. \n\n如需查看, 详细地址链接如下: \n%link',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'修改人姓名',
        '%record_id'=>'使用记录id',
        '%transaction_id'=>'使用收费id',
        '%amount'=>'使用收费金额',
        '%link'=>'链接地址',

    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.record_edit_charge_to_pi'] = [
    'description'=>'设置修改仪器使用记录导致使用收费超标给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的用户%user超额预约了仪器',
    'body'=>'%pi, 您好:\n\n%edit_user 修改了您实验室中的用户 %user 在 %equipment 中的使用记录(#%record_id) 导致使用收费(#%transaction_id)变动如下: \n修改前使用收费为: %old_amount, 修改后使用收费为: %new_amount\n修改后的使用收费超出了您设置的标准: %min_notification_fee, 特此提醒!\n\n祝您: 工作顺利, 心情愉快!',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        'pi'=>'实验室P.I.姓名',
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'修改人姓名',
        '%id'=>'使用收费id',
        '%amount'=>'使用收费金额',
        '%min_notification_fee' => '需提醒的金额上限',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.record_delete_charge_to_user'] = [
    'description'=>'设置仪器使用记录被删除导致使用收费被删除给使用者发送的消息提醒',
    'title'=>'提醒: 您的使用收费被删除',
    'body'=>'%user, 您好:\n\n您在 %equipment 中的使用记录(#%record_id)被 %edit_user 删除, 导致使用收费(#%transaction_id)被删除\n 原使用收费为: %old_amount',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'删除人姓名',
        '%record_id'=>'使用记录id',
        '%transaction_id'=>'使用收费id',
        '%old_amount'=>'修改前的计费',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.record_delete_charge_to_pi'] = [
    'description'=>'设置仪器使用记录被删除导致使用收费被删除给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的用户%user的使用收费被删除',
    'body'=>'%pi, 您好:\n\n您实验室中的用户 %user 在 %equipment 中的使用记录(#%record_id)被 %edit_user 删除, 导致使用收费(#%transaction_id)被删除\n 原使用收费为: %old_amount',
    'i18n_module' => 'eq_charge',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        'pi'=>'实验室P.I.姓名',
        '%edit_user'=>'删除人姓名',
        '%record_id'=>'使用记录id',
        '%transaction_id'=>'使用收费id',
        '%old_amount'=>'原使用收费金额',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

//sample
$config['eq_charge.edit_sample_charge.sender'] =  [
    'description'=>'设置用户送样记录被修改的仪器收费变更消息提醒',
    'title'=> '仪器收费变更提醒',
    'body'=> '您好! \n\n您对仪器%eq_name的送样预约记录(#%id)于%time被%user修改. \n该送样记录关联的仪器收费(#%transaction_id)金额发生变更, 变更如下:\n修改前: %old_amount\n修改后: %new_amount',
    'i18n_module'=> 'eq_charge',
    'strtr'=>[
        '%eq_name'=> '送样申请仪器',
        '%id'=>'送样申请编号',
        '%time'=> '送样记录修改时间',
        '%user'=> '送样记录修改人员',
        '%old_amount'=> '修改前收费金额',
        '%new_amount'=> '修改后收费金额',
        '%transaction_id'=>'送样收费id',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.edit_sample_charge.pi'] =  [
    'description'=>'设置实验室成员送样记录被修改的仪器收费变更消息提醒',
    'title'=> '仪器收费变更提醒',
    'body'=>'%pi, 您好:\n\n%edit_user 修改了您实验室中的用户 %sender 在 %eq_name 中的送样预约记录(#%id) 导致使用收费(#%transaction_id)变动如下: \n修改前送样收费为: %old_amount, 修改后送样收费为: %new_amount\n修改后的使用收费超出了您设置的标准: %min_notification_fee, 特此提醒!\n\n祝您: 工作顺利, 心情愉快!',
    'i18n_module'=> 'eq_charge',
    'strtr'=>[
        '%pi'=>'实验室P.I.姓名',
        '%sender'=> '实验室成员',
        '%eq_name'=> '送样申请仪器',
        '%id'=>'送样申请编号',
        '%time'=> '送样记录修改时间',
        '%edit_user'=> '送样记录修改人员',
        '%old_amount'=> '修改前收费金额',
        '%new_amount'=> '修改后收费金额',
        '%transaction_id'=>'送样收费id',
        '%min_notification_fee' => '需提醒的金额上限',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.delete_sample_charge.sender'] = [
    'description'=>'设置用户送样记录被删除的仪器收费消息提醒',
    'title'=> '送样记录删除提醒',
    'body'=> '您好:\n\n您对仪器%eq_name的送样预约记录(#%id)于%time被%user删除.\n该送样记录关联的仪器收费(#%transaction_id)同时被删除.\n删除收费金额: %amount',
    'i18n_module'=> 'eq_charge',
    'strtr'=>[
        '%eq_name'=> '送样申请仪器',
        '%id'=>'送样申请编号',
        '%time'=> '送样申请删除时间',
        '%user'=> '送样申请删除人员',
        '%amount'=> '送样收费金额',
        '%transaction_id'=>'送样收费id',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_charge.delete_sample_charge.pi'] = [
    'description'=>'设置实验室成员送样记录被删除的仪器收费消息提醒',
    'title'=> '仪器收费被删除提醒',
    'body'=> '您好:\n\n您实验室成员%sender对仪器%eq_name的送样预约记录(#%id)于%time被%user删除.\n该送样记录关联的仪器收费(#%transaction_id)同时被删除.\n删除收费金额: %amount',
    'i18n_module'=> 'eq_charge',
    'strtr'=>[
        '%sender'=> '实验室成员',
        '%eq_name'=> '送样申请仪器',
        '%id'=>'送样申请编号',
        '%time'=> '送样申请删除时间',
        '%user'=> '送样申请删除人员',
        '%amount'=> '送样收费金额',
        '%transaction_id'=>'送样收费id',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

//设定新添加送样后收费发送消息提醒
$config['eq_charge.add_sample.sender'] = [
    'description'=>'设置新添送样记录后收费的提醒消息',
    'title'=>'用户仪器送样收费消息提醒',
    'body'=>'%sender: \n\n您好, 您对仪器%eq_name的送样预约(#%id)收费%amount元!',
    'i18n_module'=>'eq_charge',
    'strtr'=>[
        '%sender'=> '送样人',
        '%eq_name'=> '仪器名称',
        '%id'=> '送样申请编号',
        '%amount'=> '收费金额',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

//设定新添加送样后收费发送给pi的消息提醒
$config['eq_charge.add_sample.pi'] = [
    'description'=>'设置新添送样记录后收费的提醒消息',
    'title'=>'您的送样产生了新的计费',
    'body'=>'%pi, 您好, 您实验室程成员 %sender 对仪器 %eq_name 的送样预约(#%id)收费为 %amount 元\n送样收费(#%transaction_id)超出了您设置的标准: %min_notification_fee, 特此提醒!\n\n祝您: 工作顺利, 心情愉快!',
    'i18n_module'=>'eq_charge',
    'strtr'=>[
        '%pi'=>'实验室P.I.姓名',
        '%sender'=> '送样人',
        '%eq_name'=> '仪器名称',
        '%id'=> '送样申请编号',
        '%amount'=> '收费金额',
        '%transaction_id'=> '财务明细编号',
        '%min_notification_fee' => '需提醒的金额上限',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];
